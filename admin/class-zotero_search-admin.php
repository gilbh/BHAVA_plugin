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
		wp_localize_script( $this->plugin_name, 'wp_zs_js',
	        [ 'ajaxurl' => admin_url( 'admin-ajax.php' ),]
	    );

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
			$table_tbl = $tbl_prefix . "tables";
			$charset_collate = $wpdb->get_charset_collate();

        	require_once dirname(__FILE__) . '/../core/PHPExcel-1.8/Classes/PHPExcel.php';
        	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        	$filepath = $_FILES['master_file']['tmp_name'];

		    $status = 'false';
        	if($filepath){
	        	$objReader = PHPExcel_IOFactory::load($filepath);
	        	$SheetCount =  $objReader->getSheetCount();
	        	if($SheetCount > 0){ $SQL ='';
	        		for($i = 0; $i < $SheetCount; $i++){
	        			$objReader->setActiveSheetIndex($i);
		        		$sheet = $objReader->getActiveSheet();
		        		$title_row = $sheet->getRowIterator(1)->current();
		        		$cellIterator = $title_row->getCellIterator();
		        		$cellIterator->setIterateOnlyExistingCells(false);

		        		$sheet_name = $sheet->getTitle();
		        		$sheet_name = preg_replace('/[^A-Za-z0-9\_]/','', str_replace(' ', '_', strtolower(trim($sheet_name)))) ;
		        		$tbl_name = $tbl_prefix . $sheet_name;
		        		
		        		$tbl_column = ''; $tbl_columns = [];
		        		foreach ($cellIterator as $cell) {
						    $tbl_column_name = $cell->getValue();
						    if($tbl_column_name){
			        			$tbl_column_name = preg_replace('/[^A-Za-z0-9\_]/','',str_replace(' ','_',strtolower(trim($tbl_column_name)))) ;
							    $tbl_column .= " $tbl_column_name tinytext NULL, "; 
							    $tbl_columns[] = $tbl_column_name;
						    }
						}
						$tbl_column_insert = implode(', ', $tbl_columns);
						$wpdb->query( "DROP TABLE IF EXISTS $tbl_name" );
						$SQL = "CREATE TABLE $tbl_name
						(
		        			id mediumint(9) NOT NULL AUTO_INCREMENT,
		        			$tbl_column
		        			PRIMARY KEY  (id)
						) $charset_collate;
		        		";
			        	dbDelta( $SQL );
						
			        	$wpdb->insert($table_tbl, ['table_name' => $tbl_name]);

			        	$first = true;
			        	$insertSQL = "INSERT into $tbl_name ( $tbl_column_insert ) VALUES ";
		        		foreach ($sheet->getRowIterator() AS $row) {
						    $cellIterator = $row->getCellIterator();
						    $cellssss = $cells = []; $row_count = 1;
					    	if($first){ $first = false; } 
					    	else{
							    foreach ($cellIterator as $cell) {
						        	$value = $cell->getValue();
						        	if($row_count > count($tbl_columns)) break; 
					    			if($row_count == 1){  
						    			if ($value == null && $value == '') break;
						    		} 
						    		$value = str_replace("'", "\'", $value);
						        	$cells[] = " '$value'  ";
						        	$row_count++;
						    	}  
						    }
						    if(isset($cells[0]) && !empty($cells[0])) {
							    $fieldValue = implode(', ', $cells);
							    $insertSQL .= " ( ";
							    $insertSQL .= $fieldValue;
							    $insertSQL .= " ),";
						    }
						}
						$insertSQL = substr_replace($insertSQL,";",-1);
						$wpdb->query( $insertSQL );
		    			$status = 'true';
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

    public function import_zotero_data_handler() {
		if(
        	isset( $_POST['import_zotero_data_api'] ) && 
        	wp_verify_nonce( $_POST['import_zotero_data_api'], 'import_zotero_data_api') 
        ) {

			global $wpdb;
        	$wp_prefix = $wpdb->prefix;
			$tbl_prefix = $wp_prefix . ZS_PREFIX;
			$item_tbl = $tbl_prefix . "items";
			$itemmeta_tbl = $tbl_prefix . "itemmeta";
			$charset_collate = $wpdb->get_charset_collate();

			$ZOTEROAPIKEY = '0W03GX7ROTMtYWtXdj1fwCsa';
			$ZOTEROUSERID = '783482';

        	 $curl = curl_init();
			 curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			 curl_setopt($curl, CURLOPT_HEADER, 1);


			 // $requset_url = ZOTEROAPIURL . "/users/6829294/items";
			 $requset_url = ZOTEROAPIURL ."/groups/". $ZOTEROUSERID . "/items";
			 curl_setopt_array($curl, array(
			   CURLOPT_URL => $requset_url	,
			   CURLOPT_RETURNTRANSFER => true,
			   CURLOPT_ENCODING => "",
			   CURLOPT_MAXREDIRS => 10,
			   CURLOPT_TIMEOUT => 0,
			   CURLOPT_FOLLOWLOCATION => true,
			   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			   CURLOPT_CUSTOMREQUEST => "GET",
			   CURLOPT_HTTPHEADER => array(
				 "Zotero-API-Key: ".$ZOTEROAPIKEY
			   ),
			 ));
        	$api_response = curl_exec($curl);
        	$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
			$header = substr($api_response, 0, $header_size);
			$body = substr($api_response, $header_size);
			$resp = json_decode($body, true);

			foreach($resp as $r){
				if(isset($r['data']['note'])){
					$item = $r['data']['parentItem'];
					$note = $r['data']['note'];
					$note = strip_tags($note, '<br>');
					$note = str_replace('<br />', '<<n>>', $note);
					$note = str_replace('<br/>', '<<n>>', $note);
					$note = str_replace('<br>', '<<n>>', $note);
					$note = explode('<<n>>', $note);
					
					$item_id = $wpdb->get_var("SELECT id FROM $item_tbl WHERE item = '$item' ");
					if(!$item_id){
						$curl = curl_init();
						 curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
						 curl_setopt($curl, CURLOPT_HEADER, 1);

						 $requset_url = ZOTEROAPIURL ."/groups/". $ZOTEROUSERID . "/items/" .$item ;
						 curl_setopt_array($curl, array(
						   CURLOPT_URL => $requset_url	,
						   CURLOPT_RETURNTRANSFER => true,
						   CURLOPT_ENCODING => "",
						   CURLOPT_MAXREDIRS => 10,
						   CURLOPT_TIMEOUT => 0,
						   CURLOPT_FOLLOWLOCATION => true,
						   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						   CURLOPT_CUSTOMREQUEST => "GET",
						   CURLOPT_HTTPHEADER => array(
							 "Zotero-API-Key: ".$ZOTEROAPIKEY
						   ),
						 ));
						 $response = curl_exec($curl);
						 $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
						$header = substr($response, 0, $header_size);
						$body = substr($response, $header_size);
						$resp = json_decode($body, true);
						$item_name = $resp['data']['title'];

						$insertItemSQL = "INSERT into $item_tbl ( item, item_name ) VALUES ( '$item', '$item_name' );";
						$wpdb->query( $insertItemSQL );
						$item_id = $wpdb->insert_id;
					}
					$insertItemMetaData = '';
					foreach($note as $n){
						$na = explode('=', $n);
						$meta_key = $na[0];
						$meta_key = strtolower($meta_key);
						$meta_key = trim($meta_key);
						if(isset($na[1]) && !empty($na[1])){
							$options = explode('|',$na[1]);
							$options = array_map('strtolower', $options);
							$options = array_map('trim', $options);
							foreach($options as $option){
								if(!empty($option)){
									$item_meta = $wpdb->get_var("SELECT id FROM $itemmeta_tbl WHERE meta_key = '$meta_key' AND meta_value = '$option' ");
									if(!$item_meta){
										$insertItemMetaData .= " ( '$item_id' , '$meta_key' , '$option' ),";
									}
								}
							}
						}
					}
					if($insertItemMetaData){
						$insertItemmetaSQL = "INSERT into $itemmeta_tbl ( item_id , meta_key, meta_value ) VALUES $insertItemMetaData";
						$insertItemmetaSQL = substr_replace($insertItemmetaSQL,";",-1);
						$wpdb->query( $insertItemmetaSQL );
					}
				}
			}

			$response = [
        		'status' => 'success',
        		'message' => 'Data synced successfully'
			];
        }else{
        	$response = [
        		'status' => 'failed',
        		'message' => 'Invalid nonce specified'
        	];
        }
        echo json_encode($response);
        wp_die();
	}
}
