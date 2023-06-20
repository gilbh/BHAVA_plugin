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
 * @author     Shebaz Multani <shebazm@itpathsolutions.co.in>
 */
require plugin_dir_path( __FILE__ ) .'/../core/autoload.php';
use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
        // add_menu_page( "Zotero Import", "Zotero Import", 'manage_options', $this->plugin_name . '-import', array( $this,'zotero_import_page_callback' ));
        add_menu_page( "Zotero Admin", "Zotero Admin", 'manage_options', $this->plugin_name . '-admin', array( $this,'zotero_import_page_callback' ));
        add_submenu_page( $this->plugin_name . '-admin', "Zotero Import", "Zotero Import", "manage_options", $this->plugin_name . '-import', array( $this, "zotero_import_page_callback"  ));
        add_submenu_page( $this->plugin_name . '-admin', "Zotero Settings", "Zotero Settings", "manage_options", $this->plugin_name . '-settings', array( $this, "zotero_settings_page_callback"  ));

    }

    public function zotero_import_page_callback() {
        include( plugin_dir_path( __FILE__ ) . 'partials/zotero_search-admin-import.php' );
    }

    public function zotero_settings_page_callback() {
        include( plugin_dir_path( __FILE__ ) . 'partials/zotero_search-admin-settings.php' );
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
			$status = 'false';
			$commnets = [];

        	if($_FILES['master_file']['type'] == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || $_FILES['master_file']['type'] == 'application/vnd.ms-excel' ){		    
        		$filepath = $_FILES['master_file']['tmp_name'];
	        	if($filepath){
		        	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		        	$objReader = IOFactory::load($filepath);
		        	$SheetCount =  $objReader->getSheetCount();
		        	if($SheetCount > 0){ $SQL ='';
		        		for($i = 0; $i < $SheetCount; $i++){
		        			$objReader->setActiveSheetIndex($i);
			        		$sheet = $objReader->getActiveSheet();
			        		//$objReader->setReadDataOnly(true);

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
							$extra_column_name = "note";
							$tbl_column .= " $extra_column_name tinytext NULL, "; 
							$tbl_columns[] = $extra_column_name;

							$tbl_column_insert = implode(', ', $tbl_columns);
							$wpdb->query( "DROP TABLE IF EXISTS $tbl_name" );
							$SQL = "CREATE TABLE IF NOT EXISTS $tbl_name
							(
			        			id mediumint(9) NOT NULL AUTO_INCREMENT,
			        			$tbl_column
			        			PRIMARY KEY  (id)
							) $charset_collate;
			        		";
				        	dbDelta( $SQL );

				        	// $t = $wpdb->get_var("SELECT id FROM $table_tbl WHERE table_name = '$sheet_name' ");
				        	$wpdb->delete($table_tbl, ['table_name' => $sheet_name] );
				        	$wpdb->insert($table_tbl, ['table_name' => $sheet_name]);
							
				        	$first = true;
							$total_tbl_columns = count($tbl_columns);
				        	$insertSQL = "INSERT into $tbl_name ( $tbl_column_insert ) VALUES ";
			        		foreach ($sheet->getRowIterator() AS $row) {
							    $cellIterator = $row->getCellIterator();
							    $cells = []; $row_count = 1; $note = '';
						    	if($first){ $first = false; } 
						    	else{
								    foreach ($cellIterator as $cell) {
							        	$value = $cell->getValue();

							        	if($row_count > $total_tbl_columns) break; 
						    			if($row_count == 1){  
							    			if ($value == null && $value == '') break;
							    			
							    			$call_no = $cell->getColumn() . $cell->getRow(); 
						    				$note = $sheet->getComment($call_no)->getText()->getPlainText();
							    		} 
							    		$value = str_replace("'", "\'", $value);
							        	$cells[] = " '$value'  ";
							        	$row_count++;
							    	}  
								    if(!empty($note))
								    	$cells[$total_tbl_columns - 1] = " '$note' ";
								    else
								    	$cells[$total_tbl_columns - 1] = " '' ";
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
						$wpdb->delete($table_tbl, ['table_name' => 'periods'] );
						$wpdb->insert($table_tbl, ['table_name' => 'periods']);
		        	}

		        	/*Update Notes from master sheet*/
			        /*$objReader->setActiveSheetIndex(0);
			        $sheet = $objReader->getActiveSheet();
			        $comments = $sheet->getComments();
			        if(!empty($comments)){ $updateSQL = '';
				        foreach($comments  as $cellID => $comment) {
						    $c_column = preg_replace("/[^a-zA-Z]+/", "", $cellID);
						    $c_row = preg_replace("/[^0-9]+/", "", $cellID);
						    $c_row = $c_row - 1;
						    $c_index = $c_column . 1;
						    $note = esc_sql ($comment->getText()->getPlainText());

						    $c_table = strtolower($sheet->getCell($c_index)->getValue());
						    $c_table = $tbl_prefix . $c_table;
						    $updateSQL = " UPDATE $c_table SET note='$note' WHERE id = $c_row; \n";
							$wpdb->query( $updateSQL );

						}
			        }*/

	        	}
	        }

			    
			wp_redirect(add_query_arg([
				'settings-updated' => $status,
				'action' => 'import_master_file',
			] ,  wp_get_referer()));
			
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
		        	<?php if ( 'import_master_file' === $_GET['action'] ) : ?>
			            <p><?php _e('Congratulations, your master file is imported.', $this->plugin_name) ?></p>
			        <?php else : ?>
			            <p><?php _e('Congratulations, your settings are saved successfully.', $this->plugin_name) ?></p>
			        <?php endif; ?>
		        </div>

	        <?php else : ?>
	 
		        <div class="notice notice-warning is-dismissible">
			        <?php if ( 'import_master_file' === $_GET['action'] ) : ?>
			                <p><?php _e('Sorry, File was not imported.', $this->plugin_name) ?></p>
				    <?php else : ?>
			                <p><?php _e('Sorry, your settings were not saved.', $this->plugin_name) ?></p>
			        <?php endif; ?>
		        </div>
	             
	        <?php endif;
	    }
	}
    
    public function import_zotero_data_handler() {
		if(
        	( 
        	 	isset( $_POST['import_zotero_data_api'] ) && 
        	 	wp_verify_nonce( $_POST['import_zotero_data_api'], 'import_zotero_data_api') 
        	) || defined( 'DOING_CRON' )
        ) {

			global $wpdb;
        	$wp_prefix = $wpdb->prefix;
			$tbl_prefix = $wp_prefix . ZS_PREFIX;
			$table_tbl = $tbl_prefix . "tables";
			$itemmeta_tbl = $tbl_prefix . "itemmeta";
			$charset_collate = $wpdb->get_charset_collate();

			$ZOTEROAPIKEY = get_option(ZS_PREFIX.'api_key');
			$ZOTEROUSERID = get_option(ZS_PREFIX.'user_id');
			$ZOTEROAPIURL = get_option(ZS_PREFIX.'api_url');  

			$top_items_key = $top_items_keys = $found_items_key = [];
			if(!empty($ZOTEROAPIKEY) && !empty($ZOTEROUSERID) && !empty($ZOTEROAPIURL) ){

			$all_tables = [];
			$TABLES = $wpdb->get_results("SHOW TABLES", ARRAY_N);
			foreach($TABLES as $tbl){
				$all_tables[] = $tbl[0];
			}
			
			$requestHeaders = ['timeout' => 10];
			$start = 0; $limit = 100; $zs_zotero_total_items = 0;
			$requset = $requsetData = [];
			for($start = 0; true; $start+=$limit ){ $found_items = 0;
				$top_requset_url = $ZOTEROAPIURL ."/groups/". $ZOTEROUSERID . "/items/top?start=" . $start ."&limit=" . $limit;
				// $data['requset_url']['top_requset_url'][] = $top_requset_url;
				$top_api_response = wp_remote_get($top_requset_url, $requestHeaders);
				$requset['top_api_response'] = $top_api_response;
				if ( !empty($top_api_response) && is_array( $top_api_response ) && ! is_wp_error( $top_api_response ) ) {
					if(!empty($top_api_response['body'])){
						if($start == 0 && !empty($top_api_response['headers'])){
							$zs_zotero_total_items = $top_api_response['headers']['Total-Results'];
							update_option('zs_zotero_total_items', $zs_zotero_total_items);
							$data['$zs_zotero_total_items'] = $zs_zotero_total_items;
						}
						$top_api_response_body = json_decode($top_api_response['body'], true);
						$found_items = count($top_api_response_body);
						$top_items_keys[] = wp_list_pluck($top_api_response_body, 'key');
					}
				}
				//$requset['found_items'] = $found_items;
				//$requset['limit'] = $limit;
				//$requsetData[] = $requset;
				if( $found_items < $limit ) break;
			}
			//$data['requsetData'] = $requsetData;
			$top_items_key = call_user_func_array("array_merge", $top_items_keys);
			// $data['top_items_key'] = $top_items_key;
            if(!empty($top_items_key)){
				$wpdb->query("TRUNCATE TABLE $itemmeta_tbl");
			}

			$start = 0; $limit = 100; 
			for($start = 0; true; $start+=$limit ){ $found_items = 0;
				$items_requset_url = $ZOTEROAPIURL ."/groups/". $ZOTEROUSERID . "/items?itemType=note&start=" . $start ."&limit=" . $limit;
				 $data['requset_url']['items_requset_url'][] = $items_requset_url;
				$items_api_response = wp_remote_get($items_requset_url, $requestHeaders);
				if ( !empty($items_api_response) && is_array( $items_api_response ) && ! is_wp_error( $items_api_response ) ) {
					if(!empty($items_api_response['body'])){
						$items_api_response_body = json_decode($items_api_response['body'], true);
						$found_items = count($items_api_response_body);
						$item_option[] = ''; // Added for Delete facet values with less then 2
						foreach($items_api_response_body as $item){
							if(isset($item['data']['note'])){
								$item_id = $item['data']['parentItem'];
								if(in_array($item_id, $top_items_key)){
									$found_items_key[] = $item_id;

									$item_id = $item['data']['parentItem'];
									$note = $item['data']['note'];
									$note = strip_tags($note, '<br>');
									$note = str_replace(['<br />', '<br/>', '<br>', "\n"], '<<n>>', $note);
									$note = explode('<<n>>', $note);
									// $data['note'][$item_id] = $note;
									$insertItemMetaData = '';
									foreach($note as $n){
										$na = explode('=', $n);
										$meta_values = trim($na[1]);
										$meta_key = $na[0];
										$meta_key = strtolower(esc_sql(trim($meta_key)));
										$meta_key_id = $wpdb->get_var("SELECT id FROM $table_tbl WHERE table_name = '$meta_key' ");
										if(!empty($meta_key_id)){
											$meta_key_tbl = $tbl_prefix . $meta_key;
											if(isset($meta_values) && !empty($meta_values)){
												$options = explode('|',$meta_values);
												$options = array_map('strtolower', $options);
												$options = array_map('trim', $options);
												if($meta_key == 'periods' ){
													$options = preg_replace_callback('/(\d+)-(\d+)/', function($m) {
													    return implode('|', range($m[1], $m[2]));
													}, $options);
													$options = explode('|',$options[0]);
													// $data['note'][$item_id] = $options;
												}
												foreach($options as $option){
													if( in_array($meta_key_tbl, $all_tables) ){
														
														if($meta_key == 'periods' && $option < 6 )
															$option_id = $wpdb->get_var("SELECT id FROM $meta_key_tbl WHERE $meta_key = '< 6' ");
														else
															$option_id = $wpdb->get_var("SELECT id FROM $meta_key_tbl WHERE $meta_key = '$option' ");

														if($option_id){
															$item_meta = $wpdb->get_var("SELECT id FROM $itemmeta_tbl WHERE item_id = '$item_id' AND meta_key = '$meta_key_id' AND meta_value = '$option_id' ");
															if(!$item_meta){
																$insertItemMetaData .= " ( '$item_id' , '$meta_key_id' , '$option_id' ),";
															}
														}

														// Added for Delete facet values with less then 2
														$item_option[$meta_key_id][$option_id] = $wpdb->get_var("SELECT COUNT(*) FROM $itemmeta_tbl WHERE  meta_key = '$meta_key_id' AND meta_value = '$option_id' ");
													}
												}
											}
										}
									}
									if($insertItemMetaData){
										$insertItemmetaSQL = "INSERT into $itemmeta_tbl ( item_id , meta_key, meta_value ) VALUES $insertItemMetaData";
										$insertItemmetaSQL = substr_replace($insertItemmetaSQL,";",-1);
										$result = $wpdb->query( $insertItemmetaSQL );
										// $data['insertItemMetaData'][] = $insertItemmetaSQL;
									}

								}
							}
						}
					}
				}
				if( $found_items < $limit ) break;
			}

			// Added for Delete facet values with less then 2
			if(isset( $_POST['result_count'] )){
				$del_count = $_POST['result_count'];
			}else{
        		$del_count = 2;
        	}
			$que[] = '';
			$del_ids[] = '';
			foreach ($item_option as $io_key => $item_option_values) {
				$meta_key_name = $wpdb->get_var("SELECT table_name FROM $table_tbl WHERE id = '$io_key' ");
				if(!empty($meta_key_name)){
					$del_tbl_name = $tbl_prefix . $meta_key_name;
				}
				$del_ids = [];
				foreach ($item_option_values as $key => $item_option_value) {
					if(!empty($item_option_value) && $item_option_value <= $del_count){
						$del_ids[] = $key;
					}
				}
				$comma_sap_del_ids = implode(', ', $del_ids);
				if(!empty($comma_sap_del_ids)){
					$wpdb->query($wpdb->prepare("DELETE FROM $del_tbl_name WHERE ID IN ( $comma_sap_del_ids ) "));
					$que[] = "DELETE FROM $del_tbl_name WHERE ID IN ( $comma_sap_del_ids)";
				}
			}
			// Added for Delete facet values with less then 2 end

			$remaning_items_key = array_diff($top_items_key, $found_items_key);
			// $data['remaning_items_key'][] = $remaning_items_key;
			$message = 'Data synced successfully.';
			/*if(!empty($remaning_items_key)){
				$message = "Data synced successfully<span style='color:red;'> But this item's note where not synced: <b>" . implode(', ', $remaning_items_key) ."</b></span>";
			}*/
        	$response = [
        		'status' => 'success',
        		'message' => $message,
        		'data' => $data,
        		'del' => $que,
        	];	
		} else{
        	$response = [
        		'status' => 'failed',
        		'message' => 'Please add API settings'
        	];
        }


        }else{
        	$response = [
        		'status' => 'failed',
        		'message' => 'Invalid nonce specified'
        	];
        }
        echo json_encode($response);
        wp_die();
	}

	public function zotero_update_settings_handler(){

		if(
        	isset( $_POST['zotero_settings_form_nonce'] ) && 
        	wp_verify_nonce( $_POST['zotero_settings_form_nonce'], 'zotero_settings_form_nonce') 
        ) {

			$api_key = $_POST['api_key'];
			$user_id = $_POST['user_id'];
			$api_url = $_POST['api_url'];
			$status = 'false';
			$update1 = update_option( ZS_PREFIX . 'api_key', $api_key );
			$update2 = update_option( ZS_PREFIX . 'user_id', $user_id );
			$update3 = update_option( ZS_PREFIX . 'api_url', $api_url );
			if($update1 || $update2 || $update3){
				$status = 'true';
			}
			wp_redirect(add_query_arg('settings-updated', $status ,  wp_get_referer()));

		} else {
        	wp_die( __( 'Invalid nonce specified', $this->plugin_name ), __( 'Error', $this->plugin_name ), array(
					'response' 	=> 403,
					'back_link' => 'admin.php?page=' . $this->plugin_name,

			) );
        }
	}

	public function get_line_message($lineno, $default = true){

		$linemessage = $default ? "On line number $lineno " : "";;
		if(strpos($lineno, '_') !== false){
			$no = explode('_', $lineno);
			$msg_prefix = (isset($no[2]) && !empty($no[2])) ? "Item <b>$no[2]</b>" : "";
			if($no[0] == $no[1]) $linemessage = "$msg_prefix On line number $no[0] ";
			else $linemessage = "$msg_prefix From line number $no[0] to $no[1] ";
		}
		return $linemessage;
	}

	public function zotero_error_handling_handler(){
		if(
        	isset( $_POST['zotero_error_handling_form_nonce'] ) && 
        	wp_verify_nonce( $_POST['zotero_error_handling_form_nonce'], 'zotero_error_handling_form_nonce') 
        ) {

			global $wpdb;
			$response = [
				'status'  => 'failed',
				'message' => 'something went wrong!',
			];
        	
        	$ris_file = $_FILES['ris_error_file']['tmp_name'];
			$response['filename'] = $_FILES['ris_error_file']['name'];
			

        	$handle = fopen($ris_file, "r");
        	if ($handle) { $no = 0; 
        		$NoteLine = $itemStartLine = $threeLine = $TYLine = $publicationYear = ''; 
        		$noteFound = $itemON = $NoteON = false; 
        		$noNoteItems =  $notes = $duplicateTI = $AllTI = [];
        		
        		while (($line = fgets($handle)) !== false) { $no++;

        			if (strpos($line, '  - ') !== false && $NoteON) {
						$noteEndLine = ($no-1);
						$NoteON = false; 
					}

        			if (strpos($line, 'RN  - ') !== false || strpos($line, 'N1  - ') !== false) {
        				$NoteON = true;
        				$noteStartLine = $no;
        			}

        			if ($NoteON) {
						$NoteLine .= str_replace(['RN  - ','N1  - '],'' , $line);		
        			}
        			
        			/*Item Start*/
        			if (strpos($line, 'TY  - ') !== false) {
        				$itemStartLine = $no;
        				//$threeLine = $line;
        				$noNoteItems[$no] = $line;
        			}

        			if (strpos($line, 'TI  - ') !== false) {
        				$threeLine = $line;
        				$TYLine = str_replace('TI  - ', '', $line); //remove TI  -
        			}

        			if (strpos($line, 'PY  - ') !== false) {
        				$publicationYear = str_replace('PY  - ', '', $line); //remove PY  -
        			}

        			/*if (strpos($line, 'AU  - ') !== false){
        				$threeLine .= $line;
        			}*/

        			/*Item End*/
        			if (strpos($line, 'ER  - ') !== false){

        				$TYLine .= "|$publicationYear"; //Append publication year so both title and publication year can be compared 
        				$TIKey = array_search($TYLine, $AllTI); // Search in all array if TI title already exists 
        				$AllTI[$no] = $TYLine;

        				if($TIKey !== false ){ //At Item End check append $threeLine in duplicate array
        					$duplicateTI[$TIKey] = "$threeLine PY - $publicationYear";
        				}

        				//Note validation
        				if(strpos($NoteLine, '=') !== false && strpos($NoteLine, 'Curators') !== false){ 
							$noteFound = true;
							$NoteLine = str_replace([PHP_EOL,'<br/>','<br>','<br/>','\n', '\r', '\r\n'], '<br />', $NoteLine);
							$NoteLine = strip_tags($NoteLine,'<br>');
							$NoteLine = trim($NoteLine, '<br />');
							$key = $noteStartLine.'_'.$noteEndLine.'_'.$threeLine;
							$extractedNote = explode('<br />', $NoteLine);	
							$newNote = [];
							foreach($extractedNote as $enote){
								if(strpos($enote, '=') !== false){ $newNote[] = $enote; }
							}
							$notes[$key] = $newNote;
						}
						$NoteLine = '';

        				if($noteFound){
        					array_pop($noNoteItems);
        				}else{
        					$noNoteItems[$itemStartLine] = $threeLine;
        					$itemStartLine = $threeLine = '';
        				}
        				$noteFound = false;
        			}

        		} fclose($handle);

				//Count total records identified
        		$response['records_identified'] = count($AllTI);

        		$wp_prefix = $wpdb->prefix;
				$tbl_prefix = $wp_prefix . ZS_PREFIX;
				$table_tbl = $tbl_prefix . "tables";

        		$risNotes = $risTable = $risNotesFull = [];
        		if(!empty($notes)){ $i = 0;
        			foreach ($notes as $lineno => $note) { 
        				if(!empty($note)){
	        				$risNotesFull[$lineno] = $note;
        					foreach($note as $n){ $i++;
	        					$n = explode('=', $n);
	        					if(isset($n[1]) && !empty($n[1])){
	        						$tbl = preg_replace('/[^A-Za-z0-9\_]/','', str_replace(' ', '_', strtolower(trim($n[0]))));
	        						if($tbl != 'flag'){
		        						$risTable[$lineno . '_' .$i] = $tbl;
		        						$risNotes[]  = [
		        							'lineno' => $lineno,
		        							'label'  => $tbl,
		        							'value'  => $n[1],
		        						]; 
	        						}
	        					}
        					}
        				}
        			}
        			
        			//Ingnore "Flag" keyword from RIS file
        			$risTable = array_filter($risTable, function($val){
        				return $val == 'flag' ? '' : $val;
        			});

        			$mytables = $wpdb->get_col("SELECT table_name FROM $table_tbl WHERE table_name != 'master'");	
        			// $mytables[] = 'test';
        			
        			$dbMissing = array_diff( $risTable, $mytables);
        			$risMissing = array_diff($mytables, $risTable);
        			//if(empty($risMissing) && empty($dbMissing)){
        				$error = $errorData = [];  $msg = '';
        				foreach($risNotes as $rnote){
        					$lineno = $rnote['lineno'];
        					$label = $rnote['label'];
        					$value = trim(trim($rnote['value']),'|');
        					if(!empty($value)){
	        					$value = explode('|', $value);
	        					$value = array_map(function($v){ //remove unwanted space char and return value
	        						return preg_replace("/\xc2\xa0|&nbsp;/", '', trim($v));
	        					}, $value);
	        					if($label == 'periods'){
	        						$newValue = [];
	        						foreach ($value as $val) {
										$val = preg_replace("/\xc2\xa0|&nbsp;/", '', trim($val));
	        							$rangeChar = '';
		        						if (strpos($val, '-') !== false) {
		        							$rangeChar = '-';
		        						} elseif (strpos($val, '–') !== false) {
		        							$rangeChar = '–';
		        						} 
		        						if($rangeChar) {
		        							$newVal = explode($rangeChar, $val);
		        							$minVal = trim(htmlspecialchars_decode($newVal[0]));
		        							$appendLtSix = '';
		        							if ($minVal == '< 6' || $minVal == '<6') { 
			        							$minVal = 6;
			        							$appendLtSix = '< 6';
		        							}
		        							$maxVal = $newVal[1];
		        							$range = range($minVal, $maxVal);
		        							if($appendLtSix) { array_unshift($range, $appendLtSix); }
		        							$newValue = array_merge($newValue, $range);
		        						} else {
		        							//if less then 6 return '< 6'
		        							$val = $val < 6 ? '< 6' : $val;
	        								$newValue[] = $val;
		        						}
	        						}
	        						$value = $newValue;
	        					}
	        					$current_tbl = $tbl_prefix . $label;
	        					$tbl_data = $wpdb->get_col("SELECT $label FROM $current_tbl");
	        					$valDiff = array_diff($value,$tbl_data);
	        					if(!empty($valDiff)){ $msg = '';
	        						$msg = $this->get_line_message($lineno);
									$valDiff = implode(', ', $valDiff);
									$Label = ucfirst($label);
	        						$msg .= " <b>$valDiff</b> does not exist in <b>$Label.</b>" ;
	        						$errorData[] = $msg;
	        					}
        					}
        				}
        				if(!empty($errorData)){
	        				$error[] = [
	    						'message' => 'Following note values found in RIS file but does not exist in database.',
	    						'data' 	  => $errorData,
	    					];
        				}
        				
        				$emptyItemValueError = $errorData = [];
    					foreach($risNotesFull as $lineno => $risNoteFull){
	        				$_risNoteFull = [];
	        				foreach($risNoteFull as $singleNote){
	        					$newval = explode('=', $singleNote);
	        					$_risNoteFull[] = trim(strtolower($newval[0]));
	        					if(empty(trim($newval[1]))){
	        						$EIVMsg = $this->get_line_message($lineno);
        							$EIVMsg .= "empty facet: <b>$newval[0]</b>" ;
	        						$emptyItemValueError[] = $EIVMsg;
	        					}
	        				}
    						$risItemMissing = array_diff($mytables, $_risNoteFull);
    						if(!empty($risItemMissing)){ $msg = '';
    							$msg = $this->get_line_message($lineno);
								$risItemMissing = array_map(function($val){ return ucfirst($val); }, $risItemMissing);
								$risItemMissing = implode(', ', $risItemMissing);
								$Label = ucfirst($label);
        						$msg .= "missing facet(s): <b>$risItemMissing</b>" ;
        						$errorData[] = $msg;
    						}
    						
    					}
        				if(!empty($errorData)){
	    					$error[] = [
	    						'message' => 'Following facets are missing in RIS items.',
	    						'data' 	  => $errorData,
	    					]; 
	    				}
	    				if(!empty($emptyItemValueError)){
	    					$error[] = [
	    						'message' => 'Following facet(s) with empty value.',
	    						'data' 	  => $emptyItemValueError,
	    					]; 
	    				}

        				if(empty($error)){
        					if(empty($noNoteItems) && empty($duplicateTI)){
	        					$response['status'] = 'success';
	        					$response['message'] = 'RIS filed have been verified successfully and no error found.';
        					}
        				}else $response['message'] = $error; 

        			//}else{

        				//$error = [];  
						$msg = '';
        				if(!empty($risMissing)){
        					$errorData = [];
        					foreach($risMissing as $lineno => $item){
								$msg = $this->get_line_message($lineno, false);
								$msg .= " <b>$item</b> are missing in RIS file";
								$errorData[] = $msg;
							}
        					$error[] = [
        						'message' => 'Following note facets do not exist in RIS file.',
        						'data' => $errorData,
        					]; 
        				} 

        				if(!empty($dbMissing)){
        					$errorData = []; 
        					foreach($dbMissing as $lineno => $item){
								$msg = $this->get_line_message($lineno, false);
								$msg .= " <b>$item</b> field is not found in the table's facets.";
								$errorData[] = $msg;
							}
        					$error[] = [
        						'message' => 'Following notes found in RIS file but does not exist in database.',
        						'data' => $errorData,
        					]; 
						}

						//$response['message'] = $error;
						
        			//}

        			if(!empty($noNoteItems) ){
						$error = is_array($response['message']) ? $response['message'] : []; $msg = ''; $errorData = [];
						foreach($noNoteItems as $lineno => $item){
							$msg = "Line number $lineno item <b>$item</b> lacks index notes.";
							$errorData[] = $msg;
						}

						$error[] = [
    						'message' => 'Following items have no notes.',
    						'data' => $errorData,
    					]; 
						$response['status'] = 'failed';
						$response['message'] = $error;
        			}
        			
        			if(!empty($duplicateTI) ){
						$error = is_array($response['message']) ? $response['message'] : []; $msg = ''; $errorData = [];
						foreach($duplicateTI as $lineno => $item){
							$msg = "Line number $lineno <b>$item</b> is a duplicate TI title.";
							$errorData[] = $msg;
						}

						$error[] = [
    						'message' => 'Following are duplicate TI.',
    						'data' => $errorData,
    					]; 
						$response['status'] = 'failed';
						$response['message'] = $error;
        			}
								   
					if (empty($error)) {
        				$response['status'] = 'success';
        				$response['message'] = 'RIS filed have been verified successfully and no error found.';
        			}else $response['message'] = $error;

					
        		}else $response['message'] = 'No notes found.';
        		
        	}else $response['message'] = 'Error reading file.';

        	$_SESSION['response'] = $response;
        	// print_r($response);
        	wp_redirect( wp_get_referer() );

        } else {
        	wp_die( __( 'Invalid nonce specified', $this->plugin_name ), __( 'Error', $this->plugin_name ), array(
					'response' 	=> 403,
					'back_link' => 'admin.php?page=' . $this->plugin_name,

			) );
        }
	}


}