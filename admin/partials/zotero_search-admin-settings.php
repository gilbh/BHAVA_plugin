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
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->


<h1>Zotero API Settings</h1>

<?php 
	$api_key = get_option(ZS_PREFIX.'api_key');  
	$user_id = get_option(ZS_PREFIX.'user_id');  
	$api_url = get_option(ZS_PREFIX.'api_url');  
?>
<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data" >

	<input type="hidden" name="action" value="zotero_update_settings">

	<input type="hidden" name="zotero_settings_form_nonce" value="<?php echo wp_create_nonce( 'zotero_settings_form_nonce' ) ?>"  />			

	<input type="text" name="api_url" placeholder="API URL" required value="<?php echo $api_url; ?>" >

	<input type="text" name="api_key" placeholder="API Key" required value="<?php echo $api_key; ?>" >
	
	<input type="text" name="user_id" placeholder="User ID" required value="<?php echo $user_id; ?>" >

	<input type="submit" value="<?php _e('Save', $this->plugin_name); ?>" class="button ">

</form>

<br><br>
<a href='<?php echo plugin_dir_url(__DIR__) . 'adminer.php?username='.DB_USER.'&db='.DB_NAME; ?>' target="_blank" class="button " > Access Adminer  </a>
