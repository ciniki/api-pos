<?php
//
// Description
// ===========
// This method will add a new item to an invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_shipmentItemUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'sitem_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Shipment Item'),
        'quantity'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Quantity'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.shipmentItemUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Check if quantity is <= 0
    //
    if( isset($args['quantity']) && $args['quantity'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.124', 'msg'=>'Quantity must be specified and cannot be zero.'));
    }

    //
    // Get the existing details
    //
    $strsql = "SELECT id, shipment_id, item_id, quantity "
        . "FROM ciniki_sapos_shipment_items "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['sitem_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.125', 'msg'=>'Item does not exist.'));
    }
    $item = $rc['item'];

    //
    // Get the details of the shipment
    //
    $strsql = "SELECT id, invoice_id, status, shipment_number "
        . "FROM ciniki_sapos_shipments "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $item['shipment_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'shipment');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['shipment']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.126', 'msg'=>'Shipment does not exist.'));
    }
    $shipment = $rc['shipment'];

    //
    // Reject if shipment is already shipped
    //
    if( $shipment['status'] > 20 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.127', 'msg'=>'Shipment has already been shipped.'));
    }

    //
    // Get the details of the item from the invoice
    //
    $strsql = "SELECT id, invoice_id, object, object_id, quantity, shipped_quantity "
        . "FROM ciniki_sapos_invoice_items "
        . "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $shipment['invoice_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['item_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.128', 'msg'=>'Invoice does not exist.'));
    }
    $invoice_item = $rc['item'];

    //
    // Load the invoice/order number
    //
    $strsql = "SELECT invoice_number "
        . "FROM ciniki_sapos_invoices "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $shipment['invoice_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['invoice']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.232', 'msg'=>'Invoice does not exist.'));
    }
    $invoice = $rc['invoice'];

    $history_notes = 'Order #' . $invoice['invoice_number'] . '-' . $shipment['shipment_number'];

    //
    // Quantity is the same, nothing to do
    //
//    if( $item['quantity'] == $args['quantity'] ) {
//        return array('stat'=>'ok');
//    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'shipmentUpdateStatus');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // New quantity is less
    //
    if( isset($args['quantity']) && $args['quantity'] < $item['quantity'] ) {
        // The amount removed from the quantity
        $quantity_removed = $item['quantity'] - $args['quantity'];

        $new_shipped_quantity = $invoice_item['shipped_quantity'] - $quantity_removed;
        if( $new_shipped_quantity < 0 ) {
            $new_shipped_quantity = 0;
        }
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sapos.invoice_item', $invoice_item['id'], array('shipped_quantity'=>$new_shipped_quantity), 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.129', 'msg'=>'Unable to update the invoice.'));
        }

        //
        // Replace the quantity in inventory
        //
        if( $invoice_item['object'] != '' && $invoice_item['object_id'] != '' ) {
            list($pkg,$mod,$obj) = explode('.', $invoice_item['object']);
            $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'inventoryReplace');
            if( $rc['stat'] == 'ok' ) {
                $fn = $rc['function_call'];
                $rc = $fn($ciniki, $args['tnid'], array(
                    'object'=>$invoice_item['object'],
                    'object_id'=>$invoice_item['object_id'],
                    'quantity'=>$quantity_removed,
                    'history_notes'=>(float)$quantity_removed . " replaced from " . $history_notes,
                    ));
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.130', 'msg'=>'Unable to replace inventory', 'err'=>$rc['err']));
                }
            }
        }
    }

    //
    // New quantity is more, adjust inventory
    //
    elseif( isset($args['quantity']) && $args['quantity'] > $item['quantity'] ) {
        // The amount added to the quantity
        $quantity_added = $args['quantity'] - $item['quantity'];

        //
        // Check that shipped quantity will not be greater than quantity
        //
        if( ($invoice_item['quantity'] - $invoice_item['shipped_quantity'] - $quantity_added) < 0 ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.131', 'msg'=>'The quantity is more than was ordered.'));
        }

        // 
        // Check the new shipped quantity for invoice item is valid
        //
        $new_shipped_quantity = $invoice_item['shipped_quantity'] + $quantity_added;
        if( $new_shipped_quantity < 0 ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.132', 'msg'=>'The new shipped quantity for the invoice item will be less than zero.'));
        }
        if( $new_shipped_quantity > $invoice_item['quantity'] ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.133', 'msg'=>'The new shipped quantity for the invoice item will be less than zero.'));
        }

        //
        // Replace the quantity in inventory
        //
        if( $invoice_item['object'] != '' && $invoice_item['object_id'] != '' ) {
            list($pkg,$mod,$obj) = explode('.', $invoice_item['object']);
            $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'inventoryRemove');
            if( $rc['stat'] == 'ok' ) {
                $fn = $rc['function_call'];
                $rc = $fn($ciniki, $args['tnid'], array(
                    'object'=>$invoice_item['object'],
                    'object_id'=>$invoice_item['object_id'],
                    'quantity'=>$quantity_added,
                    'history_notes'=>(float)$quantity_added . " shipped on " . $history_notes,
                    ));
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.134', 'msg'=>'Unable to replace inventory', 'err'=>$rc['err']));
                }
            }
        }

        //
        // Update the shipped quantity for the invoice item after adjusting inventory to make sure 
        // inventory is available
        //
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sapos.invoice_item', $invoice_item['id'], array('shipped_quantity'=>$new_shipped_quantity), 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.135', 'msg'=>'Unable to update the invoice.'));
        }

    }

    //
    // Update the item
    //
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sapos.shipment_item', $args['sitem_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // Update the shipment status
    //
    $rc = ciniki_sapos_shipmentUpdateStatus($ciniki, $args['tnid'], $item['shipment_id']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // Update the invoice status
    //
    $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $args['tnid'], $shipment['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

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

    return array('stat'=>'ok');
}
?>
