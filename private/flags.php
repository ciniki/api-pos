<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_flags($ciniki, $modules) {
	$flags = array(
		// 0x01
		array('flag'=>array('bit'=>'1', 'name'=>'Invoices')),
		array('flag'=>array('bit'=>'2', 'name'=>'Expenses')),
		array('flag'=>array('bit'=>'3', 'name'=>'Quick Invoices')),
		array('flag'=>array('bit'=>'4', 'name'=>'Shopping Cart')),
		// 0x10
		array('flag'=>array('bit'=>'5', 'name'=>'POS')),
		array('flag'=>array('bit'=>'6', 'name'=>'Purchase Orders')),
		array('flag'=>array('bit'=>'7', 'name'=>'Shipping')),
		array('flag'=>array('bit'=>'8', 'name'=>'Manufacturing')),
		// 0x0100
		array('flag'=>array('bit'=>'9', 'name'=>'Mileage')),
		);

	return array('stat'=>'ok', 'flags'=>$flags);
}
?>