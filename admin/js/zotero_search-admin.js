(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */


	$(document).on('click' ,'#import-zotero-data', function(e){ 
	    e.preventDefault();
	   	$(".spinner").addClass("is-active");
	   	$(".wp-notice").remove();

	   	var formData = new FormData($("#import-zotero-data-frm")[0])

	   	 jQuery.ajax({
		    type: "post",
		    processData: false,
  			contentType: false,
		    url: wp_zs_js.ajaxurl,
		    data: formData,
		    success: function(response) {
		        console.log(response);
		    	response = JSON.parse(response);
		        if(response.status == 'success'){
		    		jQuery('#wpbody-content .wrap').prepend('<div class="wp-notice" ><div class="notice notice-success"><p>'+response.message+'</p></div></div>');
		        }else{
		    		jQuery('#wpbody-content .wrap').prepend('<div class="wp-notice" ><div class="error"><p>'+response.message+'</p></div></div>');
		        }
		    },
		    error: function(error){
		    	jQuery('#wpbody-content').prepend('<div class="wp-notice" ><div class="error"><p>'+error+'</p></div></div>');
		    },
		    complete: function(){
	   			$(".spinner").removeClass("is-active");
		    }
		});

	});


})( jQuery );
