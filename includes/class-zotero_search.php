<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       authortesting.com
 * @since      1.0.0
 *
 * @package    Zotero_search
 * @subpackage Zotero_search/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Zotero_search
 * @subpackage Zotero_search/includes
 * @author     Shebaz Multani <shebazm@itpathsolutions.com>
 */
class Zotero_search {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Zotero_search_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	public static $custom_route_slug;
	protected $shortcode_name;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'ZOTERO_SEARCH_VERSION' ) ) {
			$this->version = ZOTERO_SEARCH_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'zotero_search';

		self::$custom_route_slug = 'curators';
		$this->shortcode_name = 'form_control_table';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Zotero_search_Loader. Orchestrates the hooks of the plugin.
	 * - Zotero_search_i18n. Defines internationalization functionality.
	 * - Zotero_search_Admin. Defines all hooks for the admin area.
	 * - Zotero_search_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {	

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-zotero_search-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-zotero_search-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-zotero_search-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-zotero_search-public.php';

		$this->loader = new Zotero_search_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Zotero_search_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Zotero_search_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Zotero_search_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_menu' );
		$this->loader->add_action( 'admin_post_zotero_import_master', $plugin_admin, 'zotero_import_master_handler');
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'admin_notices' );
		$this->loader->add_action( 'wp_ajax_import_zotero_data', $plugin_admin, 'import_zotero_data_handler');
		$this->loader->add_action( 'admin_post_zotero_update_settings', $plugin_admin, 'zotero_update_settings_handler');
		$this->loader->add_action( 'zs_daily_scheduled_sync_zotero_data', $plugin_admin, 'import_zotero_data_handler');
		$this->loader->add_action( 'admin_post_zotero_error_handling', $plugin_admin, 'zotero_error_handling_handler');
		
		$this->loader->add_action( 'init', $this, 'zs_init_actions');


	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Zotero_search_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		
		$this->loader->add_action( 'wp_ajax_zs_search_items', $plugin_public, 'search_items_callback' );
		$this->loader->add_action( 'wp_ajax_nopriv_zs_search_items', $plugin_public, 'search_items_callback' );
		
		$this->loader->add_action( 'wp_ajax_zs_generate_zotero_url', $plugin_public, 'zs_generate_zotero_url_callback' );
		$this->loader->add_action( 'wp_ajax_nopriv_zs_generate_zotero_url', $plugin_public, 'zs_generate_zotero_url_callback' );
	


	}
	public function zs_init_actions(){

		/*Start Session*/
		if(!session_id()) session_start();

		$shortcode_name = $this->get_shortcode_name();
		$custom_route_slug = $this->get_custom_route_slug();

		$plugin_public = new Zotero_search_Public( $this->get_plugin_name(), $this->get_version() );
		/*Register shortcode*/
		add_shortcode($shortcode_name , array( $plugin_public, 'form_control_table_callable') );


		/*Add curators route*/
		global $wpdb;
	    $post_tbl = $wpdb->prefix . "posts";
	    //Get all pages slug with [form_control_table] shortcode
	    $pages = $wpdb->get_results( "SELECT post_name,post_parent FROM $post_tbl WHERE post_content LIKE '%$shortcode_name%' AND post_type = 'page' AND post_status = 'publish' " );
	    if(!empty($pages)){
	    	foreach($pages as $page){
	    		$page_slug = $page->post_name;
	    		$page_parent = $page->post_parent;
	    		if($page_parent > 0){
	    			$parent_page_slug = $wpdb->get_var( "SELECT post_name FROM $post_tbl WHERE ID = $page_parent; " );
	    			$page_slug = "$parent_page_slug/$page_slug";
	    		}
	    		//Add curators route with that page
		   		add_rewrite_rule( $page_slug . '/('.$custom_route_slug.')/?$', 'index.php?pagename='.$page_slug.'&'.$custom_route_slug.'=$matches[1]', 'top' );
	    	}
	    }
	    //Save permalinks
	    flush_rewrite_rules();

	}
	
	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Zotero_search_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	public static function get_custom_route_slug() {
		return self::$custom_route_slug;
	}

	public function get_shortcode_name() {
		return $this->shortcode_name;
	}

}