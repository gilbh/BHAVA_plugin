<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       authortesting.com
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
 * @author     Test Author <author@testing.com>
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/zotero_search-public.css', array(), $this->version, 'all' );

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

	}

	/**
	 * Regsiter Shortcodes 
	 *
	 * @since    1.0.0
	 */
	public function register_shortcodes(){

		add_shortcode( 'form_control_table', array( $this, 'form_control_table_callable') );

	}

	public function form_control_table_callable(){
		global $wpdb;
		$wp_prefix = $wpdb->prefix;
		$tbl_prefix = $wp_prefix . ZS_PREFIX;
		$master_type_tbl = $tbl_prefix . "master_type";
		$master_tbl = $tbl_prefix . "master";

		?>	
		<style type="text/css">
			.main_row {
			    display: inline-block;
			    font-size: 18px;
			    vertical-align: top;
			    margin-left: 10px;
			}
			.main_row .head_all {
				margin: 10px 0 20px 0;
			}
			.main_row p {
				margin-bottom: 8px;
			}
		</style>
		<?php if( isset($_POST['action']) &&  $_POST['action'] == 'Zotero_search_call' ){

			$ZOTEROAPIKEY = '0W03GX7ROTMtYWtXdj1fwCsa';
			$ZOTEROUSERID = '783482';


			$postData = $_POST;
			unset($postData['action']);
			if(isset($postData['focus-languages'])){
				$postData['languages'] = $postData['focus-languages'];
				unset($postData['focus-languages']);

			}

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
			$response = curl_exec($curl);
			$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$body = substr($response, $header_size);
			$resp = json_decode($body, true);
			$parentItems = [];
			echo '<ul>';
			foreach($resp as $r){
				if(isset($r['data']['note'])){
					$note = $r['data']['note'];
					$note = strip_tags($note, '<br>');
					$note = str_replace('<br />', '<<n>>', $note);
					$note = str_replace('<br/>', '<<n>>', $note);
					$note = str_replace('<br>', '<<n>>', $note);
					$note = explode('<<n>>', $note);
					foreach($note as $n){
						foreach($postData as $post_key => $post_value){
							//check if search key exists in api responce
							if(strpos(strtolower($n), strtolower($post_key) ) !== false ){
								$na = explode('=', $n);
								if(isset($na[1]) && !empty($na[1])){
									$options = explode('|',$na[1]);
									$options = array_map('strtolower', $options);
									$options = array_map('trim', $options);
									$diff = array_diff($options, $post_value);
									if(count($options) != count($diff)){
										$parentItems[] = $r['data']['parentItem'];
									}
								}
							}
						}
					}
				}
			}
			if(!empty($parentItems)) {
				$parentItems = array_unique($parentItems);
				foreach($parentItems as $item){
					$curl = curl_init();
					 curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
					 curl_setopt($curl, CURLOPT_HEADER, 1);

					 // $requset_url = ZOTEROAPIURL . "/users/6829294/items/" . $item;
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
					echo '<li>'.$resp['data']['title'].'</li>';
					//echo '<br>';
				}
			}
			echo '</ul>';
		}else{ ?>
			<form method="post">
				<input type="hidden" name="action" value="Zotero_search_call">
				<div class="main">
					<?php $types = $wpdb->get_results("SELECT * FROM $master_type_tbl"); ?>
					<?php if($types) { 
						foreach($types as $type) { ?> 
						<div class="main_row">
							<strong><?php echo $type->name; ?></strong>
							<label class="head_all"> <input class="check_all" type="checkbox" name="<?php echo $type->slug; ?>" value="all"> All </label>
							<?php $master = $wpdb->get_results("SELECT * FROM $master_tbl WHERE type_id = $type->id");
							if($master){
								foreach($master as $m){ ?>
									<p><label class="<?php echo $type->slug; ?>">
										<input type="checkbox" name="<?php echo $type->slug; ?>[]" value="<?php echo $m->slug; ?>"> 
										<?php echo $m->name; ?></label>
									</p>
								<?php }
							} ?>
						</div>
						<?php }
					} ?>
				</div>
				<input type="submit" name="" value="Search">
			</form>
		<?php } ?>
		<script type="text/javascript">
			jQuery('.check_all').change(function(){
				name = jQuery(this).attr('name')
				jQuery('input[name^="'+name+'"]').prop("checked" , this.checked);
			});
		</script>
		<?php
	}


}
