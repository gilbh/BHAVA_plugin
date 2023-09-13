<?php

	/**
	 * The public-facing functionality of the plugin.
	 *
	 * @link       shebazm@itpathsolutions.co.in
	 * @since      1.0.0
	 *
	 * @package    Zotero_search
	 * @subpackage Zotero_search/public
	 */

	/**
	 * The public-facing functionality of the plugin.
	 *
	 * Defines the plugin name, version, and two examples hooks for how to
	 * enqueue the public-facing stylesheet and JavaScript.
	 *
	 * @package    Zotero_search
	 * @subpackage Zotero_search/public
	 * @author     Shebaz Multani <shebazm@itpathsolutions.co.in>
	 */

	class Zotero_search_Public {

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
		 * @param      string    $plugin_name       The name of the plugin.
		 * @param      string    $version    The version of this plugin.
		 */
		public function __construct( $plugin_name, $version ) {

			$this->plugin_name = $plugin_name;
			$this->version = $version;

		}

		/**
		 * Register the stylesheets for the public-facing side of the site.
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
			$vv = rand( 0, 999999999999 );
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/zotero_search-public.css', array(), $vv, 'all' );

		}

		/**
		 * Register the JavaScript for the public-facing side of the site.
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

			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/zotero_search-public.js', array( 'jquery' ), $this->version, false );
			$plugin_dir = plugin_dir_url(__FILE__);
			wp_localize_script( $this->plugin_name, 'zs_wpjs',
		        [ 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'plugin_url' => $plugin_dir ]
		    );
		}

		public function array_sort_by_column(&$arr, $col, $dir = SORT_ASC){
		    $sort_col = array();
		    foreach ($arr as $key=> $row) {
		        $sort_col[$key] = $row[$col];
		    }

		    array_multisort($sort_col, $dir, $arr);
		}

		public function search_items_callback( $search_data ){
			
			global $wpdb;
			

			$ZOTEROAPIKEY = get_option(ZS_PREFIX.'api_key');
			$ZOTEROUSERID = get_option(ZS_PREFIX.'user_id');
			$ZOTEROAPIURL = get_option(ZS_PREFIX.'api_url');  

			$wp_prefix    = $wpdb->prefix;
			$tbl_prefix   = $wp_prefix . ZS_PREFIX;
			$table_tbl    = $tbl_prefix . "tables";
			$itemmeta_tbl = $tbl_prefix . "itemmeta";
			$tinyurl_tbl  = $tbl_prefix . "tinyurl";
			$response     = ['status' => 'error', 'msg' => 'Something went wrong.'];

			$search_data  = (isset($_POST) && !empty($_POST)) ? $_POST : $search_data;

			if(!empty($ZOTEROAPIKEY) && !empty($ZOTEROUSERID) && !empty($ZOTEROAPIURL) ){
				$t = $wpdb->get_var("SELECT count(item_id) FROM $itemmeta_tbl LIMIT 1");
				if($t > 0){
					$data_items = [];

					//if(isset($search_data) && !empty($search_data) && count($search_data) > 3){
					if(isset($search_data) && !empty($search_data) && count($search_data) > 1){
						if(isset($search_data['keyword']) && !empty($search_data['keyword'])){
							$keyword = $search_data['keyword'];
							$mytables = $wpdb->get_results("SELECT * FROM $table_tbl WHERE table_name != 'master'");
							if(!empty($mytables)){
								foreach($mytables as $mytable){
									$table_name 	= $mytable->table_name;
									$table_tbl_name = $tbl_prefix . $table_name;
									$table_slug_id  = $mytable->id;

									$keyword_id = $wpdb->get_var("SELECT ID FROM $table_tbl_name WHERE $table_name LIKE '%$keyword%' " );
									if(!empty($keyword_id)){
										$search_data[$table_slug_id][] = $keyword_id;
									}
								}
							}
						}
						
						/*
						$mainQuery = '';
						$sqlI = 0;
						foreach($search_data as $key => $values ){
							$compare = '';
							if(isset($search_data[$key . '_all']) ){
								$compare = " meta_key = '$key' ";
							}else{
								if(!empty($values) && is_array($values)){
									foreach($values as $val){
										$compare .= " ( meta_key = '$key' AND meta_value = '$val' ) OR";
									}
									$compare = substr_replace($compare,"",-2);
								}
							}
							if(!empty($compare)){ $sqlI++;
								$query = "SELECT DISTINCT item_id FROM $itemmeta_tbl WHERE " . " ( ".$compare." ) AND item_id IN ( "; 
								$mainQuery .= $query ;
							}
						}
						for($i=1;$i<=$sqlI;$i++) $mainQuery .=  ' ) ';
						$mainQuery = str_replace(' AND item_id IN (  ) ','',$mainQuery);
						if(!empty($mainQuery)){
							$items = $wpdb->get_col($mainQuery);
							if(!empty($items)){
								$data_items = array_merge($data_items, $items);
							}
						}
						*/
						
						$joinQuery = "";
						$joinWhere = "WHERE";
						$AS = "A";
						$tempData = $search_data;
						$all_checked = $tempData['all_checked'];
						
						unset($tempData['action']);
						unset($tempData['keyword']);
						unset($tempData['all_checked']);
						
						$joinQuery = "SELECT DISTINCT {$AS}.item_id FROM $itemmeta_tbl AS $AS ";
						if (!empty($all_checked)) {
							$all_checked = explode(',', trim($all_checked, ','));
							$meta_value = $all_checked[0];
							$joinWhere .= " {$AS}.meta_key = $meta_value AND";
							$totalAll = count($all_checked);
							unset($tempData[$meta_value.'_all']);
							unset($tempData[$meta_value]);
							if ($totalAll > 1) {
								for ($i = 1; $i < $totalAll; $i++ ) {
									if(!empty($all_checked[$i])){
										$AS++; $ASS = chr(ord($AS)-1);
										$meta_value = $all_checked[$i];
										unset($tempData[$meta_value.'_all']);
										unset($tempData[$meta_value]);
										$joinQuery .= "INNER JOIN $itemmeta_tbl AS $AS ON {$ASS}.item_id = {$AS}.item_id ";
										$joinWhere .= " {$AS}.meta_key = $meta_value AND";
									}
								}
							}
						}
						if (!empty($tempData)) {
							$first = true;
							foreach($tempData as $key => $values) {
								if (!empty($values) && is_array($values)) {
									if(!empty($all_checked) || !$first){
										$AS++; $ASS = chr(ord($AS)-1);
										$joinQuery .= "INNER JOIN $itemmeta_tbl AS $AS ON {$ASS}.item_id = {$AS}.item_id ";
									} $first = false;
									$meta_value = implode(',', $values);
									$joinWhere .= " ( {$AS}.meta_key = $key AND {$AS}.meta_value IN ($meta_value) ) AND";
								}
							}
						}
						$mainQuery = $joinQuery . trim($joinWhere, 'AND');
						$response['mainQuery'] = $mainQuery;
						$items = $wpdb->get_col($mainQuery);
						if(!empty($items)){
							$data_items = array_merge($data_items, $items);
						}
						
						
						/*
						$thisKey = $search_data['key'];
						$thisVal = $search_data['val'];
						$mainQuery = "SELECT DISTINCT item_id FROM $itemmeta_tbl WHERE meta_key = '$thisKey'";
						if($thisVal != 'all'){
							$mainQuery .= " AND meta_value = '$thisVal'";
						}
						if(!empty($_SESSION['data_items'])){
							$mainQuery .= " AND item_id IN ('" .implode("','", array_map('mysql_real_escape_string', $_SESSION['data_items']))."')";
						}
						$response['mainQuery'] = $mainQuery;
						$response['session'] = $_SESSION['data_items'];
						$response['session_in'] = str_replace("','", $_SESSION['data_items']);
						$items = $wpdb->get_col($mainQuery);
						if(!empty($items)){
							$data_items = array_merge($data_items, $items);
						}
						*/
						
						$found_items = count($data_items);
						$response['found_items'] = $found_items;
						$response['status'] 	 = 'success';
						if(!empty($data_items)){
							$APIDATA = [
								'key' => $ZOTEROAPIKEY,
								'userid' => $ZOTEROUSERID,
								'endpoint' => $ZOTEROAPIURL,
							]; 
							$data_items = array_unique($data_items);
							$data_items = implode(',', $data_items);
							//$_SESSION['data_items'] = $data_items;
							$response['api']		 = $APIDATA;
							$response['data_items']  = $data_items;
							$response['msg'] 		 = 'Data items Found.';

						}else $response['msg'] = 'No items Found.';
					}else $response['msg'] = 'No data provied to search.';
				}else $response['msg'] = 'Please, sync data from zotero to make search work.';
			}else $response['msg'] = 'Please add API settings.';
			

			$responseData =  json_encode($response);


			if (defined('DOING_AJAX') && DOING_AJAX){
				echo $responseData;
				wp_die();
			}else return $responseData;

		}

		public function form_control_table_callable($sz_sc_attr){ 

			global $wpdb ,$wp;

			$wp_prefix    = $wpdb->prefix;
			$tbl_prefix   = $wp_prefix . ZS_PREFIX;
			$table_tbl    = $tbl_prefix . "tables";
			$itemmeta_tbl = $tbl_prefix . "itemmeta";

			$custom_route_slug = Zotero_search::get_custom_route_slug();
			$curators = false;
			$url_path = trim(parse_url(add_query_arg(array()), PHP_URL_PATH), '/');
			if(strpos($url_path, $custom_route_slug ) !== false){ $curators = true; }

			$tbl_SQL = "SELECT * FROM $table_tbl WHERE table_name != 'master' AND table_name != 'curators' ORDER BY id ASC";
			if($curators) {
				$tbl_SQL = "SELECT * FROM $table_tbl WHERE table_name != 'master' ORDER BY id ASC";
			}
			$mytables = $wpdb->get_results( $tbl_SQL );
			$version_class = '';
			if(!empty($mytables)){  if(isset($sz_sc_attr['version']) && ( $sz_sc_attr['version'] == "v2" || $sz_sc_attr['version']== "v3" )){ $version_class = 'version_2';  } ?>
				<form method="post" class="zs_item_frm <?php echo $version_class; ?>">
					<div class="parent-div" >
						<div class="main zs_shortcode_form ">
							<?php if ($curators) { echo "<h2>Curators</h2>"; } ?>
							<div class="search-wrapper">
								<div class="style_list_button">
									<div class="list-head">
										<input type="hidden" name="action" value="zs_search_items" >
										<input type="hidden" name="all_checked" id="all_checked" value="" >
										<input type="text" name="keyword" placeholder="Enter keyword here.." style="width: 30%;display: none;">
										<input type="submit" name="" value="<?php echo apply_filters('zs_search_txt' , 'Search' ); ?>">
									</div>
									<?php $zs_zotero_total_items = get_option('zs_zotero_total_items');
										if ($zs_zotero_total_items) { ?>
											<span class="result_count"> Total Records: <?php echo $zs_zotero_total_items; ?> Items</span>
									<?php } ?>
								</div>
								<?php if(isset($sz_sc_attr['version']) && ( $sz_sc_attr['version']== "v2" || $sz_sc_attr['version']== "v3" ) ){ ?>
									<div class="style_list_button">
										<div class="bhava_intro_popup">
											<a href="javascript:;" id='bhava_open_modal' data-target='#modal' >How to use BHAVA</a>
										</div>
										<a href="javascript:;" id="reset-frm" >Clear Selection</a>
										<button type="button" name="menu_style" class="change_style_btn active_style" >
											<img class="menu_icon style_icon" src="<?php echo plugin_dir_url(__FILE__); ?>/img/layout-list.svg" alt="Click to switch layouts (no change to selection)">
											<img class="list_icon style_icon" src="<?php echo plugin_dir_url(__FILE__); ?>/img/list-check.svg">
											<span>Menu layout</span>
										</button>
		<!-- 									<input type="button" name="menu_style" class="change_style_btn" value="Menu View"> -->
									</div>
								<?php } ?>
							</div>
							<div class="ajax-response" ></div>
								<?php 
								if($curators){
									$dh = 73;
									if(count($mytables) > 2){
										$dh = $dh + ((count($mytables) - 2) * 15);
									}  ?>
									<div class="selected_labels" style="height:<?php echo $dh; ?>px" >
										<div class="">
											<input type="button" name="" value="Copy" id="copy_tagline" >
										</div>
										<div id="taglines" >
											<?php 
											if(!empty($mytables)){
												foreach($mytables as $mytable){
													$table_name = ucfirst($mytable->table_name);
													if(isset($_POST[$mytable->id]) && !empty($_POST[$mytable->id])){
														$sub = $_POST[$mytable->id];
														$main_label = $wpdb->get_var("SELECT table_name FROM $table_tbl WHERE ID = $mytable->id");
														$main_label_name = $tbl_prefix . $main_label;
														$labels = '';
														foreach($sub as $s){
															$label  = $wpdb->get_row("SELECT * FROM $main_label_name WHERE ID = $s",ARRAY_N);
															$_label  = trim($label[1]) . ' | ';
															$labels .= $_label;
														}
														$labels = rtrim($labels,'| ');
														echo "<label class=' $table_name'><b>$table_name = </b><span>$labels</span> </label>";
													}else{
														echo "<label class='hide $table_name'><b>$table_name = </b></label>";
													}

												}
											} ?>
										</div>
									</div>
									<div class="refill_frm">
										<textarea class="refill_textarea" placeholder="Paste your previously generated notes here…" ></textarea>
										<input type="button" name="" value="Refill Form" id="refill_btn"  >
									</div>
								<?php } 
									$meta_keys = $wpdb->get_col("SELECT DISTINCT(meta_key) FROM $itemmeta_tbl");
								?>
								<?php if(isset($sz_sc_attr['version']) && ( $sz_sc_attr['version']== "v2" || $sz_sc_attr['version']== "v3" ) ){ ?>
												<?php if (!$curators) { ?>
									<div class="zs_hidden_tags">
										<div id="taglines" >
											<?php 
											if(!empty($mytables)){
												foreach($mytables as $mytable){
													$table_name = ucfirst($mytable->table_label);
													if(empty($table_name)){
														$table_name = ucfirst($mytable->table_name);
													}
													if(isset($_POST[$mytable->id]) && !empty($_POST[$mytable->id])){
														$sub = $_POST[$mytable->id];
														$main_label = $wpdb->get_var("SELECT table_name FROM $table_tbl WHERE ID = $mytable->id");
														$main_label_name = $tbl_prefix . $main_label;
														$labels = '';
														foreach($sub as $s){
															$label  = $wpdb->get_row("SELECT * FROM $main_label_name WHERE ID = $s",ARRAY_N);
															$_label  = trim($label[1]) . ' | ';
															$labels .= $_label;
														}
														$labels = rtrim($labels,'| ');
														echo "<label class=' $table_name'><span>$labels</span> </label>";
													}else{
														echo "<label class='hide $table_name'></label>";
													}

												}
											} ?>
										</div>
									</div>
									<?php } ?>
								<div class="zotero_category_list menu_style_show">
									<?php 
									// $meta_values = $wpdb->get_col("SELECT DISTINCT(meta_value) FROM $itemmeta_tbl");
									foreach ($mytables as $mytable) {
								    	$table_label = $mytable->table_label;
										$table_name = $mytable->table_name;
								    	if(empty($table_label)){
								    		$table_label = ucfirst($mytable->table_name);
								    	}
										if($sz_sc_attr['version']== "v3"){
									?>
											<div class="row_title_parent" bis_skin_checked="1">
												<input type="button" data-val="<?php echo strtolower($table_name); ?>" value="<?php echo strtolower($table_label); ?>">
											</div>
										<?php }else{ ?>
												<input type="button" data-val="<?php echo strtolower($table_name); ?>" value="<?php echo strtolower($table_label); ?>">
									<?php 	} 
									} ?>
								</div>
								<?php } ?>

							<div class="main_row_content menu_style_active">	
								<?php
								foreach ($mytables as $mytable) {   
								    $table_name 	= $mytable->table_name;
								    $table_tbl_name = $tbl_prefix . $table_name;
								    $table_slug 	= $table_name;
								    $table_slug_id  = $mytable->id;

								    $table_name = str_replace('_', ' ', $table_name);
								    $table_name = ucfirst($table_name);

								    $checkedAll = '';
								    if(isset($_POST[$table_slug_id.'_all'])){
								    	$checkedAll = 'checked';
								    }
								    $meta_values = [];
								    $meta_values = $wpdb->get_col("SELECT DISTINCT(meta_value) FROM $itemmeta_tbl WHERE meta_key = $table_slug_id"); 
								   	$all_disabled = !empty($meta_values) ? '' : 'disabled';
									
									// New Table Label added after 15-06-23
								   	$table_label = $mytable->table_label;
								    if(empty($table_label)){
								    	$table_label = ucfirst($mytable->table_name);
								    }


								    ?>
								    <div class="main_row <?php echo strtolower($table_name); ?>" id="<?php echo strtolower($table_name); ?>">
								    	<div class="row_title_parent">
											<strong><?php echo $table_label; ?><i class="fas fa-regular fa-chevron-down"></i></strong>
										</div>
										<div class="row_vlaue_parent">
										<label class="head_all <?php //echo $all_disabled; ?>"> 
											<input 
												type="checkbox" class="check_all" value="all" 
												name="<?php echo $table_slug_id; ?>_all" 
												data-name="<?php echo $table_slug_id; ?>"  
												data-label="<?php echo $table_name; ?>"  
												<?php echo $checkedAll; ?>
												<?php //echo $all_disabled; ?>
											> All 
										</label>
									    <?php $table_data = $wpdb->get_results("SELECT * FROM $table_tbl_name", ARRAY_N );
										if($table_data){ $checkboxs = [];
											foreach($table_data as $t){
												$name = $t[1];
												$slug = $name;
												$slug = strtolower($slug);
												$slug = trim($slug);
												$slug_id = $t[0];
												$note = end($t);

												// $slug = str_replace(' ', '-', $name);

												$checked = '0';
												if($checkedAll){
													$checked = '1';
												}else{
													if(isset($_POST[$table_slug_id]) && in_array($slug_id, $_POST[$table_slug_id])){
												    	$checked = '1';
												    }
												}


												/*$disabled = '0';
												if(!in_array($table_slug_id, $meta_keys) || !in_array($slug_id, $meta_values)){
													$checked = '0';
													$disabled = '1';
												}
												$checkboxs[]    = [
													'label'    => $name,
													'name'     => $table_slug_id,
													'value'    => $slug_id,
													'disabled' => $disabled,
													'checked'  => $checked,
												];*/

											
											 ?> <p><label data-label="<?php echo $table_slug; ?>" title='<?php echo $note; ?>' class="<?php echo !empty($note)?'zs-tt-info':''; ?>" >
													<input 
														type="checkbox" 
														name="<?php echo $table_slug_id; ?>[]" 
														value="<?php echo $slug_id; ?>"
														data-label="<?php echo $name; ?>"
														<?php //echo $disabled; ?>  
														<?php echo $checked ? 'checked' : '' ; ?>
													> <?php 
														echo $name; ?>
												</label></p>
											<?php }

											//Sort by disabled at last
											/* $this->array_sort_by_column($checkboxs, 'disabled');

											foreach($checkboxs as $checkbox){ ?>
												<p><label class=" <?php echo $checkbox['disabled'] ? 'disabled' : '' ; ?> " >
													<input 
														type="checkbox" 
														name="<?php echo $checkbox['name']; ?>[]" 
														value="<?php echo $checkbox['value']; ?>"
														data-label="<?php echo $checkbox['label']; ?>"
														<?php echo $checkbox['disabled'] ? 'disabled' : '' ; ?>  
														<?php echo $checkbox['checked'] ? 'checked' : '' ; ?>  
													> <?php echo $checkbox['label']; ?></label>
												</p>
											<?php } */ ?>
										</div>
									<?php } ?>
									</div>
								<?php } ?>
							</div>
							<div class="list-footer" style="display:none;">
								<input type="submit" name="" value="Search">
                                <div class="ajax-response" ></div>
							</div>
						</div>
					</div>
				</form>

			<?php }else{ ?>
				<div class="notice notice-warning is-dismissible">
					<p><?php _e('Please import master file to render search form.', $this->plugin_name) ?></p>
				</div>
			<?php } ?>
			<div class='bhava_intro_modal' id='modal'>
			    <div class='modal-dialog'>
			        <div class='modal__header'>
			            <div class='close close-modal'>
			                <i class='fa-solid fa-xmark'></i>
			            </div>
			        </div>
			        <div class='modal__body'>
			        </div>
			    </div>
			</div>
		<?php
		}
		
		public function zs_generate_zotero_url_callback(){
			$items_id = $_POST['items_id'];
			$AjaxResponse = [
				'status' => 'error',
				'message' => 'Something went wrong',
			];
			$pass = 'false';
			$token_prefix = 'temp_id_';
			$TOKEN = $token_prefix . time();
			$UpdateItems = $failed = [];
			$Yesterday  = date('Ymd', strtotime('yesterday'));

			$ZOTEROAPIKEY = get_option(ZS_PREFIX.'api_key');
			$ZOTEROUSERID = get_option(ZS_PREFIX.'user_id');

			if(!empty($items_id)){
				$items = explode(',', $items_id);
				$items = array_chunk($items, 50);
				foreach($items as $item){
					$SearchItem = implode(',',$item);
					$url = "https://api.zotero.org/groups/$ZOTEROUSERID/items/?itemKey=$SearchItem";
					$AjaxResponse['geturl'][] = $url;
					$response = wp_remote_get($url);
					if($response['response']['code'] == 200){
						$body = json_decode($response['body'], true);
						if(!empty($body)){ $i = 0;
							foreach($body as $itemData){ 
								
								$new_tags = [];
								$key 	  = $itemData['key'];
								$version  = $itemData['version'];
								$tags 	  = $itemData['data']['tags'];
								if(!empty($tags)){
									foreach($tags as $tag){

										$t_tag = $tag['tag'];
										$tag_ts = str_replace($token_prefix, '', $tag['tag']) ;
										$tday = date('Ymd', $tag_ts);
										if(is_numeric($tag_ts)){
											if( $tday >= $Yesterday){
												$new_tags[] = [ 'tag' => $t_tag ];
											}
										}else $new_tags[] = [ 'tag' => $t_tag ];
									}
								}
								$new_tags[] = [ 'tag' => $TOKEN ];
								
								$UpdateItems[] = [
									'key' 	   => $key,
									'tags' 	   => $new_tags,
									'version'  => $version,
								];

								
							}
						}
					}
				}

				$AjaxResponse['UpdateItems'] = $UpdateItems;

				//Update multiple ITEMS
				$SplitUpdateItems = array_chunk($UpdateItems, 50);
				$AjaxResponse['SplitUpdateItems'][] = $SplitUpdateItems;
				$headers = [ 'Zotero-API-Key' => $ZOTEROAPIKEY, 'Content-Type' => 'application/json' ];
				// foreach($SplitUpdateItems as $SplitUpdateItem){
					foreach($SplitUpdateItems as $UpdateItemsData){
						$data = [];
						$UpdateItemsRAW = wp_json_encode( $UpdateItemsData );
						// $UpdateItemsRAW = json_encode( $UpdateItemsData );
						

						$Updateargs = [
							'headers' => $headers,
							'body'	  => $UpdateItemsRAW,
						];
						$Updateurl = "https://api.zotero.org/groups/$ZOTEROUSERID/items";
						$update_response = wp_remote_post($Updateurl, $Updateargs);

						$data['status'] = 'failed';
						if($update_response['response']['code'] == 200){
						$data['status'] = 'pass';
							$pass = 'true';
						}else{
							$AjaxResponse['data'] = $update_response;
							$AjaxResponse['message'] = 'Update error occurred';
						}
						$data['headers'] = $headers;
						$data['request_raw'] = $UpdateItemsRAW;
						$data['request'] = $UpdateItemsData;
						$data['data'] = $update_response;
						$data['api_response'] = $api_response;
						$AjaxResponse['data'][] = $data;
					}
				// }
			}


			$AjaxResponse['failed']  = $failed;
				
			if($pass == 'true'){
				$redirect = "https://www.zotero.org/groups/$ZOTEROUSERID/tags/$TOKEN";
				$AjaxResponse['status']   = 'success';
				$AjaxResponse['message']  = 'Opening a new window ...';
				$AjaxResponse['redirect'] = $redirect;
			}
		
			echo json_encode($AjaxResponse);
			wp_die();
		}

	}