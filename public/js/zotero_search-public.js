(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
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

	var counting = true;
	var AID = '';
	
	var typingTimer;
	$(document).on('input', '.zs_shortcode_form input[name="keyword"]', function(){
		clearTimeout(typingTimer);
		typingTimer = setTimeout(function(){
			$('.zs_shortcode_form input[type="checkbox"]').trigger('change');
		},1000);
	});

	var tempVal = '';
	$(document).on('change', '.zs_shortcode_form input[type="checkbox"]', function(){
	
		var all_checked = $('#all_checked');
		var all_checked_val = all_checked.val();
		var $this = $(this);
		if($this.hasClass('check_all')){
			var name = $(this).data('name')
			$('input[name^="'+name+'"]:not(:disabled)').prop("checked" , this.checked);
			if (this.checked){
				$('input[name^="'+name+'"]:not(:disabled)').parent().addClass("checked");
				all_checked_val = all_checked_val.replace(this.dataset.name + ",", "");
				all_checked_val = all_checked_val + this.dataset.name + "," ;
			}else{
				$('input[name^="'+name+'"]:not(:disabled)').parent().removeClass("checked");
				all_checked_val = all_checked_val.replace(this.dataset.name + ",", "");
			}
		}
		
		var current_all = $(this).parents('.main_row').find('input[type="checkbox"]:not(.check_all)').length;
		var current_checked_all = $(this).parents('.main_row').find('input[type="checkbox"]:checked:not(.check_all)').length;
		if(current_all == current_checked_all){
			$(this).parents('.main_row').find('.check_all').prop("checked" , true);
			$(this).parents('.main_row').find('.check_all').parent().addClass("checked");
			tempVal = $(this).parents('.main_row').find('.check_all').data('name');
			all_checked_val = all_checked_val.replace(tempVal + ",", "");
			all_checked_val = all_checked_val + tempVal + "," ;				
		}else{ 
			$(this).parents('.main_row').find('.check_all').prop("checked" , false);
			$(this).parents('.main_row').find('.check_all').parent().removeClass("checked");
			tempVal = $(this).parents('.main_row').find('.check_all').data('name');
			all_checked_val = all_checked_val.replace(tempVal + ",", "");
		}
		all_checked.val(all_checked_val);
		
		var missings = [];
		$('.zs_shortcode_form .main_row').each(function(){
			var tag_lines = '';
			$('input[type="checkbox"]:checked:not(.check_all)', this).each(function(){
				tag_lines = tag_lines + $(this).data('label') + ' | ';
			});
			tag_lines = tag_lines.slice(0,-2);
			var tag = '<span>' + tag_lines + '</span>';
			var parent_label = $(this).find('strong').text();
			var parent = $('.selected_labels .'+parent_label);
			if(tag_lines){
				parent.css("display", "block");
				parent.html('<b>'+parent_label+' = </b>'+tag_lines);
			}else{ 
				missings.push(parent.find('b').text().replace(' = ', ''));
				parent.hide(); 
			}

		});
			localStorage.setItem("missings_tags", JSON.stringify(missings));


		var allCheckBox = $('.zs_shortcode_form input[type="checkbox"]');
		var allCheckBoxLabel = $('.zs_shortcode_form .main_row_content label');
		var zs_shortcode_form = $('.zs_shortcode_form');
		allCheckBox.attr('readonly', true);
		allCheckBoxLabel.addClass('readonly');
		if(this.checked){
			$(this).parent('label').addClass('checked');
		}else{
			$(this).parent('label').removeClass('checked');
		}
		zs_shortcode_form.css('cursor', 'wait');
		
		if(counting){
			counting = false;
			var btnText = 'Search', items_id = '';
			var formData = new FormData(document.querySelector('.zs_item_frm'));
			var submitBtn = $('.zs_shortcode_form [type=submit]');
			submitBtn.attr('redirect_url','' );
			$('.ajax-response').html('');
			let thisKey = $this.attr('name').replace('[]', '').replace('_all', '');
			let thisVal = $this.attr('value');
			
			$.ajax({
				type: 'POST',
				url: zs_wpjs.ajaxurl,
				processData: false,contentType: false,data: formData,
				//data: {action: 'zs_search_items', key: thisKey, val: thisVal },
				success: function(responce){
					var resp = JSON.parse(responce);
					if(resp.status == 'success'){
						btnText = 'Show ' + resp.found_items + ' Results >>';;
						items_id = resp.data_items;
						if(AID == '') AID = resp.api;

					}else{
						btnText = 'Search';
						items_id = '';
					}
				},
				complete: function(){
					submitBtn.val(btnText);
					submitBtn.attr('items_id',items_id );
					counting = true;
					console.log('complted');
					allCheckBox.attr('readonly', false);
					allCheckBoxLabel.removeClass('readonly');
					zs_shortcode_form.css('cursor', 'inherit');
				}
			});
		}

	});

	
	$(document).on('click', '.zs_shortcode_form [type=submit]', function(e){
		e.preventDefault();
		var $this = $(this);
		var items_id = $this.attr('items_id');
		var redirect_url = $this.attr('redirect_url');
		if(redirect_url != ''){
			window.open(redirect_url, '_blank');
		}else{
			var ajaxresponse = $('.ajax-response');
			ajaxresponse.html('');

			if(items_id != '' && items_id != undefined && items_id.length > 0  ){
				if(AID != '' && AID != undefined){
					items_id = items_id.split(',');
					var submitBtn = $('.zs_shortcode_form [type=submit]');
					submitBtn.attr('disabled' , true);
					ajaxresponse.html('<p>Please wait, fetching item data..</p>' );
					// Number of Items per API call
					var NIPR = 10;
					// Number of API calls
					var NAC = Math.ceil(items_id.length / NIPR);
					// Number of API calls completed
					var NACC = 0;
					//last-modified-version for If-Unmodified-Since-Version
					var IUMSV = '';
					var updateItems = [];
					var today = new Date();
					var yesterday  = new Date(today); yesterday.setDate(yesterday.getDate() - 1)
					var yesterdayDate = yesterday.getFullYear()+''+(yesterday.getMonth()+1)+''+yesterday.getDate();
					var token_prefix = 'temp_id_';
					var TOKEN = token_prefix + Math.floor(today.getTime() / 1000);
					var redirect = "https://www.zotero.org/groups/"+AID.userid+"/tags/" + TOKEN;
					var APIEndpoint = AID.endpoint +"/groups/"+AID.userid+"/items"  ;
					var APIKey = AID.key;
					while(items_id.length) { 
						var itemKey = items_id.splice(0,NIPR).join(",");
						$.ajax({ 
							method: 'GET', 
							url: APIEndpoint, 
							// async: false ,
							data: { itemKey:itemKey },
							success: function(response, textStatus, request) {
								IUMSV = request.getResponseHeader('last-modified-version');
                                if (response.length > 0) {
                                    $.each(response, function( index, item ) {
                                        var key = item.key;
                                        // var version = item.version;
                                        var tags = item.data.tags;
                                        var newTags = [];
                                        if(tags != ''){
                                            $.each(tags, function( index, tag ) {
                                                var tTag = tag.tag;
                                                var tsTag = tTag.replace(token_prefix,'');
                                                var tNow = new Date(tsTag * 1000);
                                                var tdate = tNow.getFullYear()+''+(tNow.getMonth()+1)+''+tNow.getDate();
                                                //Only include tags not older then 2 days 
                                                if($.isNumeric(tdate)){
                                                    if( tdate >= yesterdayDate){
                                                        newTags.push({ tag: tTag});
                                                    }
                                                }else newTags.push({ tag: tTag});
                                            });
                                        }
                                        newTags.push({ tag: TOKEN});
                                        updateItems.push({
                                            'key' 	 : key,
                                            'tags' 	 : newTags,
                                            // 'version': version,
                                        });
                                    });
                                } else {
									ajaxresponse.html('<p style="color:red;" >ERROR: No items found from Zotero.</p>');	
								}
							},
							error: function(error){
								ajaxresponse.html('<p style="color:red;" >ERROR: Error while fetching item data. '+error.responseText+'</p>');
							},
							complete: function(){ NACC++; }
						});
					}
					
					$(document).ajaxComplete(function(event, request, settings) {
						//Check if all get items api calls are completed
						if(NAC == NACC && IUMSV != '' && updateItems.length > 0){

							NACC = 0;
                            var chunk = 50;
                            var $j = 0;
                            var limitedItems;
                            var totalItemNumber = updateItems.length;
                            var totalUpdateCall = Math.ceil(totalItemNumber/chunk);
                            var itemUpdateErrors = [];
							ajaxresponse.html('<p>Please wait, generating URL..</p>' );
							setTimeout(function(){
	                            for(var $i = 0; $i < totalItemNumber; $i+=chunk) {
	                                limitedItems = updateItems.slice($i,$i+chunk);
									$j+=1;
									$.ajax({
										method: 'POST', 
										url: APIEndpoint, 
										async: false,
										headers: {
											"Zotero-API-Key": APIKey,
											"If-Unmodified-Since-Version": IUMSV,
			    							"Content-Type": "application/json"
										},
										data: JSON.stringify(limitedItems),
										success: function(response, textStatus, request){
											IUMSV = request.getResponseHeader('last-modified-version');
											if(Object.keys(response.success).length > 0){
												/*ajaxresponse.html('<p>Opening a new window ...</p>');
												$this.attr('redirect_url', redirect);
												setTimeout(function(){
													window.open(redirect, '_blank');
													ajaxresponse.html('<p>If not automatically redirected click here: <a target="_blank" href="'+redirect+'" >'+redirect+'</a></p>');
												},1000);*/
											}else{
												// ajaxresponse.html('<p style="color:red;" >ERROR: Items not updated or unchanged.</p>');
												itemUpdateErrors.push("ERROR: Items not updated or unchanged.");
											}
										},
										error: function(error){
											// ajaxresponse.html('<p style="color:red;" >ERROR: Error while updating item tags. '+error.responseText+'</p>');
											itemUpdateErrors.push(error.responseText);
										},
										complete: function(){
											var completePer = Math.ceil((100 * $j) / totalUpdateCall);
							ajaxresponse.html('<p>Please wait, generating URL.. '+completePer+'%</p>' );
											console.log('Please wait, generating URL.. '+completePer+'%');
											// submitBtn.attr('disabled' , false);
										}
									});
								}
								submitBtn.attr('disabled' , false);
								ajaxresponse.html('<p>Opening a new window ...</p>');
								$this.attr('redirect_url', redirect);
								setTimeout(function(){
									window.open(redirect, '_blank');
									ajaxresponse.html('<p>If not automatically redirected click here: <a target="_blank" href="'+redirect+'" >'+redirect+'</a></p>');
									if(itemUpdateErrors.length > 0){
									$.each(itemUpdateErrors, function($i, $errorMsg){
											ajaxresponse.append('<p style="color:red;" >ERROR: Error while updating item tags. '+$errorMsg+'</p>');
										});
									}
								},1000);
							}, 10);

						}				
					});
				}else ajaxresponse.html('<p style="color:red;" >API data missing! Please contact Administrator!</p>');
			}
		}

	});


	$(document).on('click', '#reset-frm', function(){
		$('.zs_item_frm').trigger("reset");
		$('.zs_shortcode_form input:checkbox').removeAttr('checked').trigger("change");
		$('.zs_result_listing').remove();
	});
	$(document).on('click', '#copy_tagline', function(){
		CopyToClipboard('taglines');
	});

	$(window).load(function() {
		let contentData = {};
		$('.main_row_content .main_row').each(function() {
			let table = $(this).find('strong').text().toLowerCase();
			let tblData = [];
			$('p', this).each(function() {
				tblData.push($.trim($(this).text().toLowerCase()));
			});
			contentData[table] = tblData;
		});
		$(document).on('click', '#refill_btn', function() {
			let filled = false;
			let refill_textarea = $('.refill_textarea');
			//Uncheck previous values before refilling form
			jQuery('.main_row_content input[type=checkbox]').prop('checked', false).trigger('change')
			if(refill_textarea.val() != '') {
				let refill_text = refill_textarea.val().split('\n');
				$.each (refill_text, function(i,line) {
					if (line != '') {
						let lines = line.split('=');
						if(lines[1]) {
							let table = $.trim(lines[0]).replace(/[^a-z0-9_-]/gi, '').toLowerCase();
							if(table in contentData) {
								let tblData = contentData[table];
								$.each (lines[1].split('|'), function(i,tbl) {
									let found = jQuery.inArray($.trim(tbl).toLowerCase(), tblData);
									if(found !== -1) {
										$('.main_row_content .'+table+ ' p:eq('+found+') input[type=checkbox]').prop('checked', true).trigger('change');
										filled = true;
									} 
								});
							}
						}
					}
				});
			}
			if (filled) {
				alert('Form refilled successfully!');
			} else {
				alert('No valid facet found to fill!');
			}
		});
	});

	function CopyToClipboard(containerid, msg = "Content copied to clipboard.") {
		
        var container = document.getElementById(containerid);
        var dummy = document.createElement("textarea");
        document.body.appendChild(dummy);
        
	  	if(containerid && container){
		    var range = document.createRange();
		    range.selectNode(container);
		    window.getSelection().removeAllRanges(); // clear current selection
		    window.getSelection().addRange(range);
		    
            var text_only = document.getSelection().toString();

            if(containerid == 'taglines'){
		  		var missings_tags = JSON.parse(localStorage.getItem("missings_tags"));
		  		if(missings_tags.length){
		  			var mt = missings_tags.join();
		  			text_only = text_only + "Flag = missing facet: "+ mt;
		    		msg = msg +"\n"+mt + " is missing. Added a Flag message.";

		  		}
            }

	  		dummy.value = text_only.trim();
	  	}else{
	  		dummy.value = containerid;
	  	}
        
        dummy.select();
        document.execCommand("copy");
		document.body.removeChild(dummy);
        
	    alert(msg)
	  
	}

	// Dev 01-05-23
	$(document).on('click',".zs_item_frm .style_list_button .change_style_btn",function(){
			var design_val = $(this).prop('name');
			$('.zs_item_frm .style_list_button .change_style_btn').removeClass('active_style');
			if(design_val == 'menu_style'){
				$(this).parents().find('.main_row_content').addClass('menu_style_active');
				$(this).parents().find('.zotero_category_list').fadeIn('slow');
				$('.zs_shortcode_form .zotero_category_list input:first-child').click();
			}else{
				$(this).parents().find('.zotero_category_list').hide();
				$(this).parents().find('.main_row_content').removeClass('menu_style_active');
			}
			$(this).addClass('active_style');
	});
	$(document).on('click','.zotero_category_list input', function(){
		var target = $(this).val();
		$('.zotero_category_list input').removeClass('active');
		$(this).addClass('active');
		$("#"+target).addClass('active').siblings(".main_row").removeClass('active');
		$("#"+target).children('.row_vlaue_parent').show();
		return false;
  	});
  	jQuery(document).on('click','.version_2 .main_row_content .main_row strong',function(){
		jQuery(this).parent().siblings().slideToggle('fast');
		jQuery(this).toggleClass('close');
	});

})( jQuery );