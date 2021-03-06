<?php
//
// Description
// -----------
// This method will turn the sapos settings for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get the ATDO settings for.
// 
// Returns
// -------
//
function ciniki_sapos_categoriesGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.categoriesGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    //
    // Get the objectList from other modules
    //
    $objects = array();
    foreach($modules as $module => $m) {
        list($pkg, $mod) = explode('.', $module);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'objectList');
        if( $rc['stat'] != 'ok' ) {
            continue;
        }
        $fn = $pkg . '_' . $mod . '_sapos_objectList';
        if( !is_callable($fn) ) {
            continue;
        }
        $rc = $fn($ciniki, $args['tnid']);
        if( $rc['stat'] != 'ok' ) {
            continue;
        }
        if( isset($rc['objects']) ) {
            $objects = array_merge($objects, $rc['objects']);
        }
    }
    
    //
    // Grab the settings for the tenant from the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $args['tnid'], 'ciniki.sapos', 'settings', 'invoice-autocat');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = isset($rc['settings']) ? $rc['settings'] : array();

    //
    // Setup the categories
    //
    $categories = array();
    foreach($objects as $oid => $object) {
        $categories[] = array(
            'field' => 'invoice-autocat-' . $oid,
            'label' => $object['name'],
            'value' => (isset($settings['invoice-autocat-' . $oid]) ? $settings['invoice-autocat-' . $oid] : (isset($object['category']) ? $object['category'] : '')),
            );
    }

    return array('stat'=>'ok', 'categories'=>$categories);
}
?>
