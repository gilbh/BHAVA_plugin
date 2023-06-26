<?php

/**
 * Provide a admin area view for the plugin import page
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       authortesting.com
 * @since      1.0.0
 *
 * @package    Zotero_search
 * @subpackage Zotero_search/admin/partials
 */


	global $wpdb;
	$wp_prefix = $wpdb->prefix;
	$tbl_prefix = $wp_prefix . ZS_PREFIX;
	$table_tbl = $tbl_prefix . "tables";
	$import_page = menu_page_url('zotero_search-import', false);
	$current_tbl = !empty($_GET['table']) ? $_GET['table'] : '';
	$delete = '';
	if(!empty($_GET['tbl_id']) && !empty($current_tbl)){
		$tbl_id = $_GET['tbl_id'];
		$delete = $wpdb->delete($current_tbl, ['ID' => $tbl_id]);
	}
	// 
	if(!empty($_POST['table_lable_value']) && !empty($current_tbl)){
		$tbl_label_val = $_POST['table_lable_value'];
		$default_label = trim(str_replace('wp_zs_','',$current_tbl));
		$label_update = $wpdb->query($wpdb->prepare("UPDATE $table_tbl SET table_label = '".$tbl_label_val."' WHERE table_name = '".$default_label."'"));
	}

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
	<?php if($delete){ ?>
		<div class="notice notice-success"><p>Record deleted successfully!</p></div>
		<script type="text/javascript">
			history.replaceState && history.replaceState(
			  null, '', location.pathname + location.search.replace(/[\?&]tbl_id=[^&]+/, '').replace(/^&/, '?') + location.hash
			);

		</script>
	<?php }if(isset($_SESSION['response']) && !empty($_SESSION['response'])){
		$responce = $_SESSION['response'];
		unset($_SESSION['response']);
		$records_identified_msg = '';
		if ( isset( $responce[ 'records_identified' ] ) ) {
			$records_identified_msg = 'Records identified: <b>' . $responce[ 'records_identified' ] . '</b>';
		}
		$filename = "Filename: <b>$responce[filename]</b> <br> $records_identified_msg <br><br>";
		if($responce['status'] == 'success' ){
			echo "<div class='notice notice-success'><p> $filename $responce[message]</p></div>";
		}else{
			if(is_array($responce['message'])){
				foreach($responce['message'] as $eMsg){
					echo "<div class='notice notice-error'> <p> $filename $eMsg[message]</p><ul><li>";
						echo implode('</li><li>', $eMsg['data']);
					echo "<li></ul></div>";
				}
			}else{
				echo "<div class='notice notice-error'><p> $filename $responce[message]</p></div>";
			}
		}
	} ?>

	<h1></h1>
	<h1 class="wp-heading-inline"> Import Faceted Classification Table </h1>

	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data" >

		<input type="hidden" name="action" value="zotero_import_master">

		<input type="hidden" name="zotero_import_master_form_nonce" value="<?php echo wp_create_nonce( 'zotero_import_master_form_nonce' ) ?>" />			

		<input type="file" name="master_file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">

		<input type="submit" class="page-title-action" value="<?php _e('Start Import', $this->plugin_name); ?>">

	</form>


	<h1 class="wp-heading-inline"> Sync Zotero Library Data </h1>
	
	<form id="import-zotero-data-frm" >
		<input type="hidden" name="action" value="import_zotero_data">
		<input type="hidden" name="import_zotero_data_api" value="<?php echo wp_create_nonce( 'import_zotero_data_api' ) ?>" />
		<label for="result_count"> Remove facet items with less than X bibliographies: [default value is 2] <br>
			*make sure to import Faceted Classification Table before clicking Start Sync to assure all facet items are considered
			<input type="number" name="result_count" value="import_zotero_data">
		</label>
		<div style="display: flex;" >
			
		<div style="margin-top: 15px;" >
			<button id="import-zotero-data" class="page-title-action">
				Start Sync
			</button>
			<span  class="spinner"></span>
		</div>
		</div>

	</form>

	<h1 class="wp-heading-inline"> Verify RIS File </h1>

	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data" >

		<input type="hidden" name="action" value="zotero_error_handling">

		<input type="hidden" name="zotero_error_handling_form_nonce" value="<?php echo wp_create_nonce( 'zotero_error_handling_form_nonce' ) ?>" />			

		<input type="file" name="ris_error_file" accept=".ris">

		<input type="submit" class="page-title-action" value="<?php _e('Verify Data', $this->plugin_name); ?>">

	</form>
	
	<div class="subtable-main" >
		
		<h1 class="wp-heading-inline"> Faceted Classification Sub-Tables </h1>

		<ul class="subsubsub">
		<?php 

		$mytables = $wpdb->get_results("SELECT * FROM $table_tbl WHERE table_name != 'master'");
		$tbl_count = count($mytables);
		if(!empty($mytables)){ $i=0;
			foreach($mytables as $mytable){ $i++;
				$table_tbl_name = $tbl_prefix . $mytable->table_name;
				$table_name 	= ucfirst($mytable->table_name);
				$table_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_tbl_name" );
				$current = ( !empty($_GET['table']) && $_GET['table'] == $table_tbl_name) ? 'current':'';
				$tbl_url = add_query_arg('table',$table_tbl_name, $import_page);
				$separator = $i < $tbl_count ? '|' : '';

				echo "<li><a href='$tbl_url' class='$current' > $table_name <span class='count'>($table_count)</span></a> $separator </li> "  ;

				} 
			} ?>
		</ul>
	</div>

	<?php if(!empty($current_tbl)){
		$tbl_columns = $wpdb->get_col("DESC $current_tbl", 0);
		$tbl_data = $wpdb->get_results("SELECT * FROM $current_tbl",ARRAY_A  );
		// Check tabel_label Column exiest or not if not add
		// Added for New Label Function
		$default_label = trim(str_replace('wp_zs_','',$current_tbl)); 
		if($wpdb->get_var("SHOW TABLES LIKE '".$table_tbl."'") == $table_tbl.""){
			$sql = "SELECT COUNT(*) AS a FROM information_schema.COLUMNS WHERE TABLE_NAME = '".$table_tbl."' AND COLUMN_NAME =  'table_label'";
			$query_data = $wpdb->get_results($sql);
			if($query_data[0]->a == 0){
				$sql2 = 'ALTER TABLE `'.$table_tbl.'` ADD `table_label` tinytext NOT NULL AFTER `table_name`';
				$wpdb->query($sql2);
			}
		}
		$current_tbl_name = $wpdb->get_row("SELECT table_label FROM $table_tbl WHERE table_name != 'master' AND table_name = '".$default_label."' ",ARRAY_A);
		?>
		<div class="zs_label_update_section">
			<form action="" method="POST" id="facetes_label_update">
				<label for="table_lable_value">
					Change facet label (leave empty to display facet name as it appears in the table):
					<input type="text" name="table_lable_value" value="<?php echo !empty($current_tbl_name['table_label']) ? $current_tbl_name['table_label'] : '' ; ?>">
				</label>
				<input type="submit">
			</form>
		</div>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<?php if(!empty($tbl_columns)){ $i=0;
						foreach($tbl_columns as  $column){ $i++;  ?>
							<th class="<?php echo $i==1?'id-col':''; ?>" ><?php echo $column; ?></th>
						<?php }
					} ?>
					<th class='action-col' >Action</th>
				</tr>
			</thead>
			<tbody>
				<?php if(!empty($tbl_data)){
					 foreach($tbl_data as $data){
					 	echo "<tr>";
					 	foreach($data as $col => $td){
					 		echo "<td>$td</td>" ;
					 	}
					 	$delete_link = add_query_arg(['table'=>$current_tbl,'tbl_id'=>$data['id']], $import_page);
					 	echo "<td><a href='$delete_link'>Delete</a></td>";
					 	echo "</tr>";
					 }
				} ?>
			</tbody>
		</table>
	<?php } ?>


</div>