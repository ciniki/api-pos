<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_objects($ciniki) {
    
    $objects = array();
    $objects['invoice'] = array(
        'name'=>'Invoice',
        'sync'=>'yes',
        'table'=>'ciniki_sapos_invoices',
        'fields'=>array(
            'source_id'=>array('name'=>'Source', 'ref'=>'ciniki.sapos.invoice', 'default'=>'0'),
            'invoice_number'=>array('name'=>'Invoice Number', 'default'=>''),
            'invoice_type'=>array('name'=>'Invoice Type', 'default'=>'10'),
            'po_number'=>array('name'=>'Purchase Order Number', 'default'=>''),
            'receipt_number'=>array('name'=>'Receipt Number', 'default'=>''),
            'customer_id'=>array('name'=>'Customer', 'ref'=>'ciniki.customers.customer'),
            'status'=>array('name'=>'Status', 'default'=>'0'),
            'payment_status'=>array('default'=>'0'),
            'shipping_status'=>array('default'=>'0'),
            'manufacturing_status'=>array('default'=>'0'),
            'donationreceipt_status'=>array('name'=>'Donation Receipt Status', 'default'=>'0'),
            'preorder_status'=>array('name'=>'Pre-Order Status', 'default'=>'0'),
            'flags'=>array('default'=>'0'),
            'invoice_date'=>array(),
            'due_date'=>array('default'=>'',),
            'billing_name'=>array('default'=>''),
            'billing_address1'=>array('default'=>''),
            'billing_address2'=>array('default'=>''),
            'billing_city'=>array('default'=>''),
            'billing_province'=>array('default'=>''),
            'billing_postal'=>array('default'=>''),
            'billing_country'=>array('default'=>''),
            'shipping_name'=>array('default'=>''),
            'shipping_address1'=>array('default'=>''),
            'shipping_address2'=>array('default'=>''),
            'shipping_city'=>array('default'=>''),
            'shipping_province'=>array('default'=>''),
            'shipping_postal'=>array('default'=>''),
            'shipping_country'=>array('default'=>''),
            'shipping_phone'=>array('default'=>''),
            'shipping_notes'=>array('default'=>''),
            'work_address1'=>array('default'=>''),
            'work_address2'=>array('default'=>''),
            'work_city'=>array('default'=>''),
            'work_province'=>array('default'=>''),
            'work_postal'=>array('default'=>''),
            'work_country'=>array('default'=>''),
            'tax_location_id'=>array('ref'=>'ciniki.taxes.location', 'default'=>'0'),
            'preorder_subtotal_amount'=>array('name'=>'Pre-Order Subtotal', 'default'=>'0'),
            'preorder_shipping_amount'=>array('name'=>'Pre-Order Shipping', 'default'=>'0'),
            'preorder_total_amount'=>array('name'=>'Pre-Order Total', 'default'=>'0'),
            'subtotal_amount'=>array('default'=>'0'),
            'subtotal_discount_amount'=>array('default'=>'0'),
            'subtotal_discount_percentage'=>array('default'=>'0'),
            'discount_amount'=>array('default'=>'0'),
            'shipping_amount'=>array('default'=>'0'),
            'total_amount'=>array('default'=>'0'),
            'total_savings'=>array('default'=>'0'),
            'paid_amount'=>array('default'=>'0'),
            'balance_amount'=>array('default'=>'0'),
            'user_id'=>array(),
            'customer_notes'=>array('default'=>''),
            'invoice_notes'=>array('default'=>''),
            'internal_notes'=>array('default'=>''),
            'submitted_by'=>array('default'=>''),
            ),
        'history_table'=>'ciniki_sapos_history',
        );
    $objects['invoice_item'] = array(
        'name'=>'Invoice Item',
        'sync'=>'yes',
        'table'=>'ciniki_sapos_invoice_items',
        'fields'=>array(
            'invoice_id'=>array('name'=>'Invoice', 'ref'=>'ciniki.sapos.invoice'),
            'line_number'=>array('name'=>'Line Number'),
            'status'=>array('name'=>'Status', 'default'=>'0'),
            'category'=>array('name'=>'Category', 'default'=>''),
            'flags'=>array('name'=>'Flags', 'default'=>'0'),
            'object'=>array('name'=>'Object', 'default'=>''),
            'object_id'=>array('name'=>'Object ID', 'default'=>'0'),
            'price_id'=>array('name'=>'Price', 'default'=>'0'),
            'student_id'=>array('name'=>'Student', 'default'=>'0'),
            'code'=>array('name'=>'Code', 'default'=>''),
            'description'=>array('name'=>'Description'),
            'quantity'=>array('name'=>'Quantity'),
            'shipped_quantity'=>array('name'=>'Shipped Quantity', 'default'=>'0'),
            'unit_amount'=>array('name'=>'Unit Amount'),
            'unit_discount_amount'=>array('name'=>'Unit Discount Amount', 'default'=>'0'),
            'unit_discount_percentage'=>array('name'=>'Unit Discount Percentage', 'default'=>'0'),
            'unit_preorder_amount'=>array('name'=>'Unit Pre-Order Amount', 'default'=>'0'),
            'subtotal_amount'=>array('name'=>'Subtotal Amount'),
            'discount_amount'=>array('name'=>'Discount Amount'),
            'total_amount'=>array('name'=>'Total Amount'),
            'unit_donation_amount'=>array('name'=>'Donation Amount', 'default'=>'0'),
            'taxtype_id'=>array('name'=>'Taxtype', 'ref'=>'ciniki.taxes.type', 'default'=>'0'),
            'shipping_profile_id'=>array('name'=>'Shipping Profile', 'ref'=>'ciniki.sapos.shippingprofile', 'default'=>'0'),
            'notes'=>array('name'=>'Notes', 'default'=>''),
            ),
        'history_table'=>'ciniki_sapos_history',
        );
    $objects['invoice_tax'] = array(
        'name'=>'Invoice Tax',
        'sync'=>'yes',
        'table'=>'ciniki_sapos_invoice_taxes',
        'fields'=>array(
            'invoice_id'=>array('name'=>'Invoice', 'ref'=>'ciniki.sapos.invoice'),
            'taxrate_id'=>array('name'=>'Tax Rate', 'ref'=>'ciniki.taxes.rate'),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'line_number'=>array('name'=>'Line Number'),
            'description'=>array('name'=>'Description'),
            'amount'=>array('name'=>'Tax Amount'),
            ),
        'history_table'=>'ciniki_sapos_history',
        );
    $objects['invoice_costing'] = array(
        'name' => 'Invoice Costing',
        'sync' => 'yes',
        'o_name' => 'costing',
        'o_container' => 'costings',
        'table' => 'ciniki_sapos_invoice_costing',
        'fields' => array(
            'invoice_id' => array('name'=>'Invoice', 'ref'=>'ciniki.sapos.invoice'),
            'line_number' => array('name'=>'Line Number', 'default'=>'1'),
            'description' => array('name'=>'Description', 'default'=>''),
            'quantity' => array('name'=>'Quantity', 'default'=>'1'),
            'cost' => array('name'=>'Cost', 'default'=>''),
            'price' => array('name'=>'Price', 'default'=>''),
            ),
        'history_table' => 'ciniki_sapos_history',
        );
    $objects['qi_item'] = array(
        'name'=>'Quick Invoice Item',
        'sync'=>'yes',
        'table'=>'ciniki_sapos_qi_items',
        'fields'=>array(
            'template'=>array(),
            'name'=>array(),
            'object'=>array(),
            'object_id'=>array(),
            'description'=>array(),
            'quantity'=>array(),
            'unit_amount'=>array(),
            'unit_discount_amount'=>array(),
            'unit_discount_percentage'=>array(),
            'taxtype_id'=>array('ref'=>'ciniki.taxes.type'),
            ),
        'history_table'=>'ciniki_sapos_history',
        );
    $objects['transaction'] = array(
        'name'=>'Transaction',
        'sync'=>'yes',
        'table'=>'ciniki_sapos_transactions',
        'fields'=>array(
            'invoice_id'=>array('ref'=>'ciniki.sapos.invoice'),
            'status'=>array('name'=>'Status', 'default'=>'40'),
            'transaction_type'=>array(),
            'transaction_date'=>array(),
            'source'=>array(),
            'customer_amount'=>array(),
            'transaction_fees'=>array(),
            'tenant_amount'=>array(),
            'user_id'=>array(),
            'notes'=>array(),
            'gateway'=>array(),
            'gateway_token'=>array(),
            'gateway_status'=>array(),
            'gateway_response'=>array(),
            ),
        'history_table'=>'ciniki_sapos_history',
        );
    $objects['shipment'] = array(
        'name'=>'Shipment',
        'sync'=>'yes',
        'table'=>'ciniki_sapos_shipments',
        'fields'=>array(
            'invoice_id'=>array('ref'=>'ciniki.sapos.invoice'),
            'shipment_number'=>array(),
            'status'=>array(),
            'flags'=>array(),
            'weight'=>array(),
            'weight_units'=>array(),
            'shipping_company'=>array(),
            'tracking_number'=>array(),
            'td_number'=>array(),
            'boxes'=>array(),
            'dimensions'=>array(),
            'pack_date'=>array(),
            'ship_date'=>array(),
            'freight_amount'=>array(),
            'notes'=>array('default'=>''),
            ),
        'history_table'=>'ciniki_sapos_history',
        );
    $objects['shipment_item'] = array(
        'name'=>'Shipment Item',
        'sync'=>'yes',
        'table'=>'ciniki_sapos_shipment_items',
        'fields'=>array(
            'shipment_id'=>array('ref'=>'ciniki.sapos.shipment'),
            'item_id'=>array('ref'=>'ciniki.sapos.invoice_item'),
            'quantity'=>array(),
            'notes'=>array(),
            ),
        'history_table'=>'ciniki_sapos_history',
        );
    $objects['expense'] = array(
        'name'=>'Expense',
        'sync'=>'yes',
        'table'=>'ciniki_sapos_expenses',
        'fields'=>array(
            'name'=>array(),
            'description'=>array(),
            'invoice_date'=>array(),
            'paid_date'=>array(),
            'total_amount'=>array(),
            'notes'=>array(),
            ),
        'history_table'=>'ciniki_sapos_history',
        );
    $objects['expense_category'] = array(
        'name'=>'Expense Category',
        'sync'=>'yes',
        'table'=>'ciniki_sapos_expense_categories',
        'fields'=>array(
            'name'=>array(),
            'sequence'=>array(),
            'flags'=>array(),
            'taxrate_id'=>array('ref'=>'ciniki.taxes.rate'),
            'start_date'=>array(),
            'end_date'=>array(),
            ),
        'history_table'=>'ciniki_sapos_history',
        );
    $objects['expense_item'] = array(
        'name'=>'Expense Item',
        'sync'=>'yes',
        'table'=>'ciniki_sapos_expense_items',
        'fields'=>array(
            'expense_id'=>array('ref'=>'ciniki.sapos.expense'),
            'category_id'=>array('ref'=>'ciniki.sapos.expense_category'),
            'amount'=>array(),
            'notes'=>array(),
            ),
        'history_table'=>'ciniki_sapos_history',
        );
    $objects['expense_image'] = array(
        'name'=>'Expense Image',
        'sync'=>'yes',
        'table'=>'ciniki_sapos_expense_images',
        'fields'=>array(
            'expense_id'=>array('ref'=>'ciniki.sapos.expense'),
            'image_id'=>array('ref'=>'ciniki.images.image'),
            ),
        'history_table'=>'ciniki_sapos_history',
        );
    $objects['mileage'] = array(
        'name'=>'Mileage',
        'sync'=>'yes',
        'table'=>'ciniki_sapos_mileage',
        'fields'=>array(
            'start_name'=>array(),
            'start_address'=>array(),
            'end_name'=>array(),
            'end_address'=>array(),
            'travel_date'=>array(),
            'distance'=>array(),
            'flags'=>array(),
            'notes'=>array(),
            ),
        'history_table'=>'ciniki_sapos_history',
        );
    $objects['mileage_rate'] = array(
        'name'=>'Mileage Rate',
        'sync'=>'yes',
        'table'=>'ciniki_sapos_mileage_rates',
        'fields'=>array(
            'rate'=>array(),
            'start_date'=>array(),
            'end_date'=>array(),
            ),
        'history_table'=>'ciniki_sapos_history',
        );
    $objects['donationpackage'] = array(
        'name'=>'Donation Package',
        'o_name'=>'package',
        'o_container'=>'packages',
        'sync'=>'yes',
        'table'=>'ciniki_sapos_donation_packages',
        'fields'=>array(
            'name'=>array('name'=>'Name'),
            'subname'=>array('name'=>'Sub-Name', 'default'=>''),
            'permalink'=>array('name'=>'Permalink'),
            'invoice_name'=>array('name'=>'Invoice Name', 'default'=>''),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'category'=>array('name'=>'Category', 'default'=>''),
            'sequence'=>array('name'=>'Amount', 'default'=>'1'),
            'amount'=>array('name'=>'Amount', 'default'=>'0'),
            'primary_image_id'=>array('name'=>'Image', 'default'=>'0', 'ref'=>'ciniki.images.image'),
            'synopsis'=>array('name'=>'Synopsis', 'default'=>''),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_donation_history',
        );
    $objects['simpleshiprate'] = array(
        'name' => 'Shipping Rate',
        'sync' => 'yes',
        'o_name' => 'simpleshiprate',
        'o_container' => 'simpleshiprates',
        'table' => 'ciniki_sapos_simpleshiprates',
        'fields' => array(
            'country' => array('name'=>'Country', 'default'=>''),
            'province' => array('name'=>'Province', 'default'=>''),
            'city' => array('name'=>'City', 'default'=>''),
            'minimum_amount' => array('name'=>'Invoice Total', 'default'=>'0'),
            'rate' => array('name'=>'Shipping Rate'),
            ),
        'history_table' => 'ciniki_sapos_history',
        );
//  $objects['rule'] = array(
//      'name'=>'Rules',
//      'sync'=>'yes',
//      'table'=>'ciniki_sapos_rules',
//      'fields'=>array(
//          'status'=>array(),
//          'flags'=>array(),
//          'sequence'=>array(),
//          'formulas'=>array(),
//          'code'=>array(),
//          'description'=>array(),
//          'quantity'=>array(),
//          'unit_amount'=>array(),
//          'taxtype_id'=>array(),
//          'notes'=>array(),
//          ),
//      'history_table'=>'ciniki_sapos_history',
//      );
    
    $objects['shippingprofile'] = array(
        'name' => 'Shipping Profile',
        'sync' => 'yes',
        'o_name' => 'profile',
        'o_container' => 'profiles',
        'table' => 'ciniki_sapos_shipping_profiles',
        'fields' => array(
            'name' => array('name'=>'Name'),
            ),
        'history_table' => 'ciniki_sapos_history',
        );
    $objects['shippingrate'] = array(
        'name' => 'Shipping Profile Rate',
        'sync' => 'yes',
        'o_name' => 'rate',
        'o_container' => 'rates',
        'table' => 'ciniki_sapos_shipping_rates',
        'fields' => array(
            'profile_id' => array('name'=>'Profile', 'ref'=>'ciniki.sapos.shippingprofile'),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'min_quantity' => array('name'=>'Minimum Quantity', 'default'=>'0'),
            'max_quantity' => array('name'=>'Maximum Quantity', 'default'=>'0'),
            'min_amount' => array('name'=>'Minimum $ Amount', 'default'=>'0'),
            'max_amount' => array('name'=>'Maximum $ Amount', 'default'=>'0'),
            'shipping_amount_us' => array('name'=>'Shipping to US', 'default'=>''),
            'shipping_amount_ca' => array('name'=>'Shipping to Canada', 'default'=>''),
            'shipping_amount_intl' => array('name'=>'Shipping International', 'default'=>''),
            ),
        'history_table' => 'ciniki_sapos_history',
        );
    $objects['setting'] = array(
        'type'=>'settings',
        'name'=>'SAPOS Settings',
        'table'=>'ciniki_sapos_settings',
        'history_table'=>'ciniki_sapos_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
