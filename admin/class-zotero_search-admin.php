<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       authortesting.com
 * @since      1.0.0
 *
 * @package    Zotero_search
 * @subpackage Zotero_search/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Zotero_search
 * @subpackage Zotero_search/admin
 * @author     Test Author <author@testing.com>
 */
class Zotero_search_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Zotero_search_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Zotero_search_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/zotero_search-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Zotero_search_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Zotero_search_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/zotero_search-admin.js', array( 'jquery' ), $this->version, false );

	}

	public function add_menu()
    {
        add_menu_page( "Zotero Import", "Zotero Import", 'manage_options', $this->plugin_name . '-import', array( $this,'zotero_import_page_callback' ));
    }

    public function zotero_import_page_callback() {
        include( plugin_dir_path( __FILE__ ) . 'partials/zotero_search-admin-import.php' );
    }

    public function zotero_import_master_handler() {
        if(
        	isset( $_POST['zotero_import_master_form_nonce'] ) && 
        	wp_verify_nonce( $_POST['zotero_import_master_form_nonce'], 'zotero_import_master_form_nonce') 
        ) {
        
        	global $wpdb;
        	$wp_prefix = $wpdb->prefix;
			$tbl_prefix = $wp_prefix . ZS_PREFIX;
			$master_type_tbl = $tbl_prefix . "master_type";
			$master_tbl = $tbl_prefix . "master";

        	require_once dirname(__FILE__) . '/../core/PHPExcel-1.8/Classes/PHPExcel.php';
        	$filepath = $_FILES['master_file']['tmp_name'];

		    $status = 'false';
        	if($filepath){
	        	$objReader = PHPExcel_IOFactory::load($filepath);
	        	$objReader->setActiveSheetIndex(0);
		        $sheet = $objReader->getActiveSheet();
		        if($sheet->getColumnIterator()){
		        	$status = 'true';
			        foreach($sheet->getColumnIterator() as $column) {	$first = true;
			        	$type = '';
					    foreach($column->getCellIterator() as $key => $cell) {
					        $name = $cell->getCalculatedValue();
					        $slug = str_replace(' ', '-', strtolower($name));

					        if($type == '') $type = $slug;
					        if($first){ $first = false;
					        	$wpdb->insert($master_type_tbl,[
					        		'name' => $name,
					        		'slug' => $slug,
					        	]);
					        }else{
					        	$type_id = $wpdb->get_var( "SELECT id FROM $master_type_tbl WHERE slug = '$type'" );
					        	$wpdb->insert($master_tbl,[
					        		'type_id' => $type_id,
					        		'name' 	  => $name,
					        		'slug' 	  => $slug,
					        	]);
					        }
					    }
					}
			    }
        	}

			wp_redirect(add_query_arg('settings-updated', $status ,  wp_get_referer()));



        } else {
        	wp_die( __( 'Invalid nonce specified', $this->plugin_name ), __( 'Error', $this->plugin_name ), array(
					'response' 	=> 403,
					'back_link' => 'admin.php?page=' . $this->plugin_name,

			) );
        }
    
    }

    public function admin_notices() {

	    //get the current screen
	    $screen = get_current_screen();
	 
	    //return if not plugin settings page 
	    //To get the exact your screen ID just do ver_dump($screen)
	    if ( $screen->id !== 'toplevel_page_zotero_search-import') return;
	         
	    //Checks if settings updated 
	    if ( isset( $_GET['settings-updated'] ) ) {
	        //if settings updated successfully 
	        if ( 'true' === $_GET['settings-updated'] ) : ?>
	 
	            <div class="notice notice-success is-dismissible">
	                <p><?php _e('Congratulations! Your master file is imported.', $this->plugin_name) ?></p>
	            </div>
	 
	        <?php else : ?>
	 
	            <div class="notice notice-warning is-dismissible">
	                <p><?php _e('Sorry, File was not imported.', $this->plugin_name) ?></p>
	            </div>
	             
	        <?php endif;
	    }
	}

}
