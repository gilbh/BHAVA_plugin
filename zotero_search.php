<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              authortesting.com
 * @since             1.0.0
 * @package           Zotero_search
 *
 * @wordpress-plugin
 * Plugin Name:       Zotero Search
 * Plugin URI:        zoterosearchtesting.com
 * Description:       This is a Zotero Search plugin.
 * Version:           1.0.0
 * Author:            Test Author
 * Author URI:        authortesting.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       zotero_search
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'ZOTERO_SEARCH_VERSION', '1.0.0' );

/**
 * ZoteroSearch plugins table prefix.
 */
define( 'ZS_PREFIX', 'zs_' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-zotero_search-activator.php
 */
function activate_zotero_search() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-zotero_search-activator.php';
	Zotero_search_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-zotero_search-deactivator.php
 */
function deactivate_zotero_search() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-zotero_search-deactivator.php';
	Zotero_search_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_zotero_search' );
register_deactivation_hook( __FILE__, 'deactivate_zotero_search' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-zotero_search.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_zotero_search() {

	$plugin = new Zotero_search();
	$plugin->run();

}
run_zotero_search();
