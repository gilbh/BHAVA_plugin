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
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$wp_prefix = $wpdb->prefix;
		$tbl_prefix = $wp_prefix . ZS_PREFIX;
		$charset_collate = $wpdb->get_charset_collate();

/*		$master_type_tbl = $tbl_prefix . "master_type";
		$master_tbl = $tbl_prefix . "master";

		$sql = "CREATE TABLE $master_type_tbl (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  name tinytext NOT NULL,
		  slug tinytext NOT NULL,
		  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  PRIMARY KEY  (id)
		) $charset_collate;";

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
*/
		$items_tbl = $tbl_prefix . "items";
		$itemmeta_tbl = $tbl_prefix . "itemmeta";
		$table_tbl = $tbl_prefix . "tables";
		$sql = "CREATE TABLE $items_tbl (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  item tinytext NOT NULL,
		  item_name tinytext NOT NULL,
		  datetime TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  PRIMARY KEY  (id)
		) $charset_collate; ";

		dbDelta( $sql );

		$sql = "CREATE TABLE $itemmeta_tbl (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  item_id tinytext NOT NULL,
		  meta_key tinytext NULL,
		  meta_value longtext NULL,
		  PRIMARY KEY  (id)
		) $charset_collate; ";

		dbDelta( $sql );

		$sql = "CREATE TABLE $table_tbl (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  table_name tinytext NOT NULL,
		  PRIMARY KEY  (id)
		) $charset_collate; ";

		dbDelta( $sql );


	}

}
