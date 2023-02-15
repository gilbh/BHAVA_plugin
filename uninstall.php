<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       authortesting.com
 * @since      1.0.0
 *
 * @package    Zotero_search
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
$ZS_PREFIX = 'zs_';
$tbl_prefix = $wpdb->prefix . $ZS_PREFIX;
$itemmeta_tbl = $tbl_prefix . "itemmeta";
$table_tbl = $tbl_prefix . "tables";

$wpdb->query( "DROP TABLE IF EXISTS ".$itemmeta_tbl );
$mytables = $wpdb->get_results("SELECT * FROM $table_tbl");
if($mytables){
	foreach ($mytables as $mytable) {
		$table = $mytable->table_name; 
		$wpdb->query( "DROP TABLE IF EXISTS ".$tbl_prefix.$table );
	}
}
$wpdb->query( "DROP TABLE IF EXISTS ".$table_tbl );


delete_option($ZS_PREFIX.'api_key');
delete_option($ZS_PREFIX.'user_id');
delete_option($ZS_PREFIX.'api_url');
