<?php
//
// Description
// -----------
// This function returns the array of status text for ciniki_sapos_invoices.status.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_maps($ciniki) {

	$maps = array();
	$maps['invoice'] = array(
		'invoice_type'=>array(
			'10'=>'Invoice',
			'20'=>'Cart',
			'30'=>'POS',
			'40'=>'Order',
			),
		'status'=>array(
			'10'=>'Entered',
			'20'=>'Pending Manufacturing',
			'30'=>'Pending Shipping',
			'40'=>'Payment Required',
			'50'=>'Fulfilled',
			'55'=>'Refunded',
			'60'=>'Void',
			),
		'typestatus'=>array(
			'10.10'=>'Payment Required',
			'10.20'=>'Processing',
			'10.30'=>'Processing',
			'10.40'=>'Payment Required',
			'10.50'=>'Paid',
			'10.55'=>'Refunded',
			'10.60'=>'Void',
			'20.10'=>'Incomplete',
			'20.20'=>'Pending Manufacturing',
			'20.30'=>'Pending Shipping',
			'20.40'=>'Payment Required',
			'20.50'=>'Fulfilled',
			'20.55'=>'Refunded',
			'20.60'=>'Void',
			'30.10'=>'Entered',
			'30.20'=>'Pending Manufacturing',
			'30.30'=>'Pending Shipping',
			'30.40'=>'Payment Required',
			'30.50'=>'Fulfilled',
			'30.55'=>'Refunded',
			'30.60'=>'Void',
			'40.10'=>'Entered',
			'40.20'=>'Pending Manufacturing',
			'40.30'=>'Pending Shipping',
			'40.40'=>'Payment Required',
			'40.50'=>'Fulfilled',
			'40.55'=>'Refunded',
			'40.60'=>'Void',
			),
		'payment_status'=>array(
			'10'=>'Payment Required',
			'40'=>'Deposit',
			'50'=>'Paid',
			'55'=>'Refunded',
			),
		'shipping_status'=>array(
			'0'=>'',		// No shipping
			'10'=>'Shipping Required',
			'30'=>'Partial Shipment',
			'50'=>'Shipped',
			),
		'manufacturing_status'=>array(
			'0'=>'',		// No shipping
			'10'=>'Manufacturing Required',
			'30'=>'Manufacturing In Progress',
			'50'=>'Manufactured',
			),
		);
	$maps['transaction'] = array(
		'source'=>array(
			'10'=>'Paypal',
			'20'=>'Square',
			'50'=>'Visa',
			'55'=>'Mastercard',
			'60'=>'Discover',
			'65'=>'Amex',
			'90'=>'Interac',
			'100'=>'Cash',
			'105'=>'Check',
			'110'=>'Email Transfer',
			'120'=>'Other',
			)
		);
	$maps['shipment'] = array(
		'status'=>array(
			'10'=>'Packing',
			'20'=>'Packed',
			'30'=>'Shipped',
			'40'=>'Received',
			)
		);
	
	return array('stat'=>'ok', 'maps'=>$maps);
}
?>