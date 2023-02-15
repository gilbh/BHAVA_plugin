<?php

/**
 * Fired during plugin deactivation
 *
 * @link       authortesting.com
 * @since      1.0.0
 *
 * @package    Zotero_search
 * @subpackage Zotero_search/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Zotero_search
 * @subpackage Zotero_search/includes
 * @author     Shebaz Multani <shebazm@itpathsolutions.com>
 */
class Zotero_search_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

		//Add remove cron job
		wp_clear_scheduled_hook( 'zs_daily_scheduled_sync_zotero_data' );

	}

}
