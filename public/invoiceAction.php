<?php
//
// Description
// ===========
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_invoiceAction(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'), 
        'action'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Action',
            'validlist'=>array('submit', 'discount', 'packed', 'pickedup')),
        'unit_discount_percentage'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Discount Percent'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    if( isset($args['unit_discount_percentage']) ) {
        $args['unit_discount_percentage'] = preg_replace('/[^0-9\.]/', '', $args['unit_discount_percentage']);
    }

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.invoiceAction'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Load the settings
    //
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 
        'tnid', $args['tnid'], 'ciniki.sapos', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = isset($rc['settings'])?$rc['settings']:array();

    //
    // Check the discount
    //
    if( !isset($args['unit_discount_percentage']) || $args['unit_discount_percentage'] == '' ) {
        $args['unit_discount_percentage'] = 0;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    //
    // Start the transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    $update_args = array();
    if( isset($args['action']) && $args['action'] == 'submit' ) {
        $strsql = "SELECT po_number, customer_id, invoice_type, status, shipping_status, submitted_by "
            . "FROM ciniki_sapos_invoices "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return $rc;
        }
        if( !isset($rc['invoice']) ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.57', 'msg'=>'Unable to find invoice'));
        }
        $invoice = $rc['invoice'];
        //
        // Only allow orders to be submitted if still in incomplete status
        //
        if( ($invoice['invoice_type'] == 40 || $invoice['invoice_type'] == 20) && $invoice['status'] == 10 ) {
            if( isset($settings['rules-invoice-submit-require-po_number']) 
                && $settings['rules-invoice-submit-require-po_number'] == 'yes' 
                && (!isset($invoice['po_number']) || $invoice['po_number'] == '') 
                ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.58', 'msg'=>'The order must have a PO Number before it can be submitted.'));
            }
            //
            // Check if customer is on hold
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerStatus');
            $rc = ciniki_customers_hooks_customerStatus($ciniki, $args['tnid'],
            array('customer_id'=>$invoice['customer_id']));
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
                return $rc;
            }
            if( !isset($rc['customer']) ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.59', 'msg'=>'Customer does exist for this invoice'));
            }
            $customer = $rc['customer'];
            if( $customer['status'] > 10 ) {
                $update_args['status'] = 15;    // On hold
            } else {
                $update_args['status'] = 30;    // Pending shipping
                //
                // Check if cart should be turned into order
                //
                if( $invoice['invoice_type'] == 20 ) {
                    $update_args['invoice_type'] = 40;
                }
            }
            $update_args['submitted_by'] = $ciniki['session']['user']['display_name'];
        }
    } elseif( isset($args['action']) && $args['action'] == 'discount' && isset($args['unit_discount_percentage']) ) {
        $strsql = "SELECT id, uuid, quantity, unit_amount, unit_discount_amount, unit_discount_percentage, unit_preorder_amount "
            . "FROM ciniki_sapos_invoice_items "
            . "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return $rc;
        }
        if( isset($rc['rows']) ) {
            $items = $rc['rows'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'itemCalcAmount');
            foreach($items as $item) {
                if( $item['unit_discount_percentage'] != $args['unit_discount_percentage'] ) {
                    $item_args['unit_discount_percentage'] = $args['unit_discount_percentage'];
                    $rc = ciniki_sapos_itemCalcAmount($ciniki, array(
                        'quantity'=>$item['quantity'],
                        'unit_amount'=>$item['unit_amount'],
                        'unit_discount_amount'=>$item['unit_discount_amount'],
                        'unit_discount_percentage'=>$args['unit_discount_percentage'],
                        'unit_preorder_amount'=>$args['unit_preorder_amount'],
                        ));
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
                        return $rc;
                    }
                    $item_args['subtotal_amount'] = $rc['subtotal'];
                    $item_args['discount_amount'] = $rc['discount'];
                    $item_args['total_amount'] = $rc['total'];

                    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sapos.invoice_item', $item['id'], $item_args, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
                        return $rc;
                    }
                }
            }
        }
    } elseif( isset($args['action']) && $args['action'] == 'packed' ) {
        //
        // Load the invoice
        //
        $strsql = "SELECT po_number, customer_id, invoice_type, status, shipping_status, submitted_by "
            . "FROM ciniki_sapos_invoices "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return $rc;
        }
        if( !isset($rc['invoice']) ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.57', 'msg'=>'Unable to find invoice'));
        }
        $invoice = $rc['invoice'];
       
        //
        // Load the items
        //
        $strsql = "SELECT id, uuid, flags, quantity, shipped_quantity, unit_amount, unit_discount_amount, unit_discount_percentage, unit_preorder_amount "
            . "FROM ciniki_sapos_invoice_items "
            . "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return $rc;
        }
        if( isset($rc['rows']) ) {
            $items = $rc['rows'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'itemCalcAmount');
            foreach($items as $item) {
                if( ($item['flags']&0x40) == 0x40 && $item['shipped_quantity'] < $item['quantity'] ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sapos.invoice_item', $item['id'], array(
                        'shipped_quantity' => $item['quantity'],
                        ), 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
                        return $rc;
                    }
                }
            }
        }

/*        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sapos.invoice', $invoice['id'], array(
            'shipping_status' => 55,
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return $rc;
        } */

//        return array('stat'=>'ok');

    } elseif( isset($args['action']) && $args['action'] == 'pickedup' ) {
        
        //
        // Load the invoice
        //
        $strsql = "SELECT po_number, customer_id, invoice_type, status, shipping_status, submitted_by "
            . "FROM ciniki_sapos_invoices "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return $rc;
        }
        if( !isset($rc['invoice']) ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.57', 'msg'=>'Unable to find invoice'));
        }
        $invoice = $rc['invoice'];

        $update_args = array();
        if( $invoice['shipping_status'] == 55 ) {
            $update_args['shipping_status'] = 60;
            if( $invoice['status'] < 50 ) {
                $update_args['status'] = 50;
            }
        }
    
    } else {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.60', 'msg'=>'No action specified'));
    }

    //
    // Update the invoice
    //
    if( count($update_args) > 0 ) {
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sapos.invoice', 
            $args['invoice_id'], $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return $rc;
        }
    }

    //
    // Return the invoice record
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
    $rc = ciniki_sapos_invoiceLoad($ciniki, $args['tnid'], $args['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $invoice = $rc['invoice'];

    //
    // Check for callbacks
    //
    if( isset($invoice['items']) ) {
        foreach($invoice['items'] as $iid => $item) {
            $item = $item['item'];
            if( $item['object'] != '' && $item['object_id'] != '' ) {
                list($pkg,$mod,$obj) = explode('.', $item['object']);
                $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'invoiceUpdate');
                if( $rc['stat'] == 'ok' ) {
                    $fn = $rc['function_call'];
                    $rc = $fn($ciniki, $args['tnid'], $invoice['id'], $item);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                }
            }
        }
    }

    //
    // Update the taxes/shipping incase something relavent changed
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
    $rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $args['tnid'], $args['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
    $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $args['tnid'], $args['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Reload the invoice record incase anything has changed
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
    $rc = ciniki_sapos_invoiceLoad($ciniki, $args['tnid'], $args['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $invoice = $rc['invoice'];

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'sapos');

    return array('stat'=>'ok', 'invoice'=>$invoice);
}
?>
