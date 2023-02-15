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
 * @author     Shebaz Multani <shebazm@itpathsolutions.com>
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

		$itemmeta_tbl = $tbl_prefix . "itemmeta";
		$table_tbl = $tbl_prefix . "tables";
		$periods = "periods";
		$periods_tbl = $tbl_prefix . $periods;
		
		$sql = "CREATE TABLE $itemmeta_tbl (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  item_id tinytext NOT NULL,
		  meta_key mediumint(9) NULL,
		  meta_value mediumint(9) NULL,
		  PRIMARY KEY (id),
		  KEY item_id (item_id(63)),
		  KEY meta_key (meta_key),
		  KEY meta_value (meta_value)
		) $charset_collate; ";

		dbDelta( $sql );

		$sql = "CREATE TABLE $table_tbl (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  table_name tinytext NOT NULL,
		  PRIMARY KEY (id)
		) $charset_collate; ";

		dbDelta( $sql );

		$wpdb->query( "DROP TABLE IF EXISTS $periods_tbl" );
		$sql = "CREATE TABLE $periods_tbl (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  $periods tinytext NOT NULL,
		  note tinytext NULL, 
		  PRIMARY KEY (id)
		) $charset_collate; ";

		dbDelta( $sql );
		$wpdb->query("INSERT INTO $periods_tbl ($periods) VALUES ('< 6'),('6'),('7'),('8'),('9'),('10'),('11'),('12'),('13'),('14'),('15'),('16'),('17'),('18'),('19'),('20'),('21');");


		//Add daily cron job
		if (! wp_next_scheduled ( 'zs_daily_scheduled_sync_zotero_data' )) {
			wp_schedule_event( time(), 'daily', 'zs_daily_scheduled_sync_zotero_data' );
		}



	}

}
