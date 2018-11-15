<?php
//
// Description
// -----------
// This function will update the status of an invoice based on the payments
// made.
//
// Arguments
// ---------
// ciniki:
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $tnid, $invoice_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    //
    // Apply any rules to the invoice
    //
    

    //
    // Get the invoice details
    //
    $strsql = "SELECT customer_id, invoice_type, status, "
        . "receipt_number, "
        . "payment_status, shipping_status, manufacturing_status, "
        . "ROUND(total_amount, 2) AS total_amount, "
        . "ROUND(paid_amount, 2) AS paid_amount, "
        . "ROUND(balance_amount, 2) AS balance_amount "
        . "FROM ciniki_sapos_invoices "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    if( !isset($rc['invoice']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.27', 'msg'=>'Unable to locate the invoice'));
    }
    $invoice = $rc['invoice'];

    //
    // Get the customer status
    //
    if( $invoice['customer_id'] > 0 ) {
        $strsql = "SELECT status "
            . "FROM ciniki_customers "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $invoice['customer_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['customer']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.28', 'msg'=>'Unable to find customer'));
        }
        $customer = $rc['customer'];
    }

    //
    // Get the invoice transactions
    //
    $strsql = "SELECT id, transaction_type, "
        . "ROUND(customer_amount, 2) AS customer_amount, "
        . "ROUND(transaction_fees, 2) AS transaction_fees, "
        . "ROUND(tenant_amount, 2) AS tenant_amount "
        . "FROM ciniki_sapos_transactions "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    if( isset($rc['rows']) ) {
        $transactions = $rc['rows'];
        $amount_paid = 0;
        foreach($transactions as $rid => $ta) {
            if( $ta['transaction_type'] == 10 || $ta['transaction_type'] == 20 ) {
                $amount_paid = bcadd($amount_paid, $ta['customer_amount'], 2);
            } elseif( $ta['transaction_type'] == 60 ) {
                $amount_paid = bcsub($amount_paid, $ta['customer_amount'], 2);
            }
        }
    } else {
        $transactions = array();
    }

    //
    // In the future, check the manufacturing status
    //
    $new_manufacturing_status = $invoice['manufacturing_status'];

    //
    // Determine the new shipping status for the order
    //
    $new_shipping_status = $invoice['shipping_status'];
    if( $invoice['shipping_status'] > 0 
        && ($ciniki['tenant']['modules']['ciniki.sapos']['flags']&0x40) > 0
        ) {
        $remaining_quantity = 'none';
        //
        // Get the items, to see if there is any quantity left of anything to ship
        //
        $strsql = "SELECT id, quantity-shipped_quantity AS remaining_quantity "
            . "FROM ciniki_sapos_invoice_items "
            . "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "HAVING remaining_quantity > 0 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }   
        if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
            $remaining_quantity = 'some';
        }
        //
        // Check for the shipments
        //
        $shipments = 'none';
        $strsql = "SELECT id, status "
            . "FROM ciniki_sapos_shipments "
            . "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }   
        if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
            // Since there are shipments, assume all have been shipped  
            foreach($rc['rows'] as $rid => $row) {
                if( $row['status'] < 30 && $shipments == 'all' ) { 
                    $shipments = 'some';
                } elseif( $row['status'] >= 30 && $shipments == 'none' ) {
                    $shipments = 'all';
                }
            }
        }
        
        //
        // Decide what the new status should be
        //
        if( $remaining_quantity == 'none' && $shipments == 'all' ) {
            // Nothing remaining to be shipped, and all shipments have been sent
            $new_shipping_status = 50;
        }
        elseif( $remaining_quantity = 'some' && $shipments == 'some' ) {
            // Some items have shipped, but not all
            $new_shipping_status = 30;
        }
        elseif( $remaining_quantity = 'some' && $shipments == 'none' ) {    
            // Nothing has been shipped
            $new_shipping_status = 10;
        }
    }

    //
    // Check if invoice should be updated status
    //
    $new_payment_status = $invoice['payment_status'];
    //
    // Check if status should change for invoice, but only if payments are enabled
    //
    if( ($ciniki['tenant']['modules']['ciniki.sapos']['flags']&0x800200) > 0 || $invoice['payment_status'] > 0 ) {
        if( $amount_paid > 0 && $amount_paid < $invoice['total_amount'] ) {
            if( $invoice['payment_status'] == 10 || $invoice['payment_status'] == 50 ) {
                $new_payment_status = 40;
            }
        } elseif( $amount_paid > 0 && $amount_paid == $invoice['total_amount'] ) {
            if( $invoice['payment_status'] < 50 ) {
                $new_payment_status = 50;
            }
        } elseif( $amount_paid > 0 && $amount_paid > $invoice['total_amount'] ) {
            if( $invoice['payment_status'] < 55 ) {
                $new_payment_status = 55;
            }
        } elseif( $amount_paid == 0 ) {
            $new_payment_status = 10;
        }
    }

    //
    // Check if status should change for invoice
    //
    $new_status = $invoice['status'];
    if( $invoice['invoice_type'] == '10' ) {
        if( $new_payment_status != $invoice['payment_status'] ) {
            if( $new_payment_status > 0 && $new_payment_status < 50 ) {
                $new_status = 40;
            } elseif( $new_payment_status == 50 ) {
                $new_status = 50;
            } elseif( $new_payment_status == 55 ) {
                $new_status = 55;
            }
        }
    }
    elseif( $invoice['invoice_type'] == '40' && $invoice['status'] > 15 && $invoice['status'] < 65 ) {
        //
        // Only update order status if status > 10, otherwise it's considered still entered
        //
        if( isset($customer) && isset($customer['status']) && $customer['status'] == '40' 
            && $new_status < 50 ) {
            // Customer is on hold, and order is not fulfilled, make the invoice on hold as well
            $new_status = 15;
        }
        elseif( isset($customer) && isset($customer['status']) && $customer['status'] == '41'  // Secondary hold
            && $new_status < 50 ) {
            // Customer is on hold, and order is not fulfilled, make the invoice warehouse hold as well
            $new_status = 14;
        }
        elseif( $new_manufacturing_status > 0 && $new_manufacturing_status < 50 ) {
            $new_status = 20;
        }
        elseif( $new_shipping_status > 0 && $new_shipping_status < 50 ) {   
            $new_status = 30;
        }
        elseif( $new_payment_status > 0 && $new_payment_status < 50 ) {
            $new_status = 40;
        } 
        // Each status is either ignored, or completed
        elseif( ($new_manufacturing_status == 0 || $new_manufacturing_status >= 50) 
            && ($new_shipping_status == 0 || $new_shipping_status >= 50) 
            && ($new_payment_status == 0 || $new_payment_status >= 50) 
            ) {
            $new_status = 50;
        }
    }
    elseif( $invoice['invoice_type'] == '30') {
        if( $new_manufacturing_status > 0 && $new_manufacturing_status < 50 ) {
            $new_status = 20;
        }
        elseif( $new_shipping_status > 0 && $new_shipping_status < 50 ) {   
            $new_status = 30;
        }
        elseif( $new_payment_status > 0 && $new_payment_status < 50 ) {
            $new_status = 40;
        } 
        // Each status is either ignored, or completed
        elseif( ($new_manufacturing_status == 0 || $new_manufacturing_status >= 50) 
            && ($new_shipping_status == 0 || $new_shipping_status >= 50) 
            && ($new_payment_status == 0 || $new_payment_status >= 50) 
            ) {
            $new_status = 50;
        }
    }

//  if( $invoice['status'] > 10 && $invoice['status'] < 40 && $invoice['total_amount'] != 0 ) {
//      if( $amount_paid > 0 && $amount_paid < $invoice['total_amount'] ) {
//          $new_status = 40;
//      } elseif( $amount_paid >= $invoice['total_amount'] ) {
//          $new_status = 50;
//      }
//  }
//  elseif( $invoice['status'] == 40 ) {
//      if( $amount_paid >= $invoice['total_amount'] ) {
//          $new_status = 50;
//      }
//  }
//  elseif( $invoice['status'] == 50 ) {
//      if( $amount_paid > 0 && $amount_paid < $invoice['total_amount']) {
//          $new_status = 40;
//      }
//      if( $amount_paid == 0 ) {
//          $new_status = 55;
//      }
//  }
//  elseif( $invoice['status'] == 55 ) {
//      if( $amount_paid > 0 && $amount_paid < $invoice['total_amount']) {
//          $new_status = 40;
//      }
//      if( $amount_paid >= $invoice['total_amount'] ) {
//          $new_status = 50;
//      }
//  }
    // If status is currently 60 (Void) then don't change.

    //
    // Check if any values have changed
    //
    $args = array();
    if( $new_status != $invoice['status'] ) {
        $args['status'] = $new_status;
        if( $args['status'] == 50 ) {
            //
            // Payment received
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoicePaymentReceived');
            $rc = ciniki_sapos_invoicePaymentReceived($ciniki, $tnid, $invoice_id);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.236', 'msg'=>'Error updating the invoice', 'err'=>$rc['err']));
            }
        }
    }
    if( $new_payment_status != $invoice['payment_status'] ) {
        $args['payment_status'] = $new_payment_status;
    }
    if( $new_shipping_status != $invoice['shipping_status'] ) {
        $args['shipping_status'] = $new_shipping_status;
    }
    if( $amount_paid != $invoice['paid_amount'] ) { 
        $args['paid_amount'] = $amount_paid;
    }
    $balance_amount = bcsub($invoice['total_amount'], $amount_paid, 2);
    if( $balance_amount != $invoice['balance_amount'] ) {
        $args['balance_amount'] = $balance_amount;
    }

    if( count($args) > 0 ) {
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice', 
            $invoice_id, $args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.29', 'msg'=>'Unable to update invoice', 'err'=>$rc['err']));
        }
    }

    return array('stat'=>'ok');
}
?>
