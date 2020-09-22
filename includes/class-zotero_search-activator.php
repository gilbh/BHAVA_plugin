<?php

/**
 * Fired during plugin activation
 *
 * @link       authortesting.com
 * @since      1.0.0
 *
 * @package    Zotero_search
 * @subpackage Zotero_search/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Zotero_search
 * @subpackage Zotero_search/includes
 * @author     Test Author <author@testing.com>
 */
class Zotero_search_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		
		global $wpdb;

		$wp_prefix = $wpdb->prefix;
		$tbl_prefix = $wp_prefix . ZS_PREFIX;
		$charset_collate = $wpdb->get_charset_collate();

		$master_type_tbl = $tbl_prefix . "master_type";
		$master_tbl = $tbl_prefix . "master";

		$sql = "CREATE TABLE $master_type_tbl (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  name tinytext NOT NULL,
		  slug tinytext NOT NULL,
		  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
		$sql = "CREATE TABLE $master_tbl (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  type_id mediumint(9) NOT NULL,
		  name tinytext NOT NULL,
		  slug tinytext NOT NULL,
		  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( $sql );


	}

}
