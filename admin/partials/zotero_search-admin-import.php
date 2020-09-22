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


<h1>Import Master file</h1>

<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data" >

	<input type="hidden" name="action" value="zotero_import_master">

	<input type="hidden" name="zotero_import_master_form_nonce" value="<?php echo wp_create_nonce( 'zotero_import_master_form_nonce' ) ?>" />			

	<input type="file" name="master_file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">

	<input type="submit" value="<?php _e('Start Import', $this->plugin_name); ?>">

</form>