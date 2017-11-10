/**
* 2017 Zlab Solutions
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    Eugene Zubkov <magrabota@gmail.com>
*  @copyright 2017 Zlab Solutions
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/
let widget_popup_lang_pac = '';

(function(){
    // Your base, I'm in it!
    var originalAddClassMethod = jQuery.fn.addClass;
    var originalRemoveClassMethod = jQuery.fn.removeClass;
    jQuery.fn.addClass = function(){
        var result = originalAddClassMethod.apply( this, arguments );
        jQuery(this).trigger('cssClassChanged');
        return result;
    }
    jQuery.fn.removeClass = function(){
        var result = originalRemoveClassMethod.apply( this, arguments );
        jQuery(this).trigger('cssClassRemoved');
        return result;
    }
})();


$(document).ready(function(){
	bindsw.readAjaxFields();

	if (typeof easypaytextCheckout != "undefined") {
		bindsw.opcBindsEasy();
		bindsw.widgetStatusMoveOnLoadEasy();
	} else {
		if ($('.supercheckout_shipping_option').length > 0) {
			bindsw.opcBindsSuper();
			bindsw.widgetStatusMoveOnLoadSuper();
		} else if(typeof OnePageCheckoutPS != 'undefined') {
			bindsw.onChangeContinueOpcps();
			$(document).on('change', 'input[id^="delivery_option_"]', function() {
				setTimeout(function() {
					bindsw.opcBindsOpcps();
					bindsw.widgetStatusMoveOnLoadOpcps();
				}, 2000);
			});
			$(document).on('change', '#delivery_id_country', function() {
				console.log('bind country');
				setTimeout(function() {
					bindsw.opcBindsOpcps();
					bindsw.widgetStatusMoveOnLoadOpcps();
				}, 2000);
			});
			setTimeout(function() {
				bindsw.opcBindsOpcps();
				bindsw.widgetStatusMoveOnLoadOpcps();
			}, 2000);
		} else {
			bindsw.opcBinds();
			bindsw.widgetStatusMoveOnLoadClassic();
		}
	}

	$('#packetery-widget .js-country').on('change', function(){
		packetery.widgetGetCities();
	});
	$('#packetery-widget .js-city').on('change', function(){
		packetery.widgetGetNames();
	});
	$('#packetery-widget .js-name').on('change', function(){
		var id_branch = $(this).find('option:selected').val();
		var is_cod = 0;
		packetery.widgetGetDetails();
	});
});

bindsw = {
/*POPUP*/
	readAjaxFields: function() {
		var raw = $('#ajaxfields').val();
		if (typeof raw != 'undefined') {
			var json = decodeURIComponent(raw);
			widget_popup_lang_pac = JSON.parse(json);
		}
	},

/*OPC One Page Checkout Prestashop*/
	widgetStatusMoveOnLoadOpcps: function() {
		console.log('widgetStatusMoveOnLoadOpcps');
		let checked_carrier = $('input[id^="delivery_option_"]:checked').val()
		console.log('widgetStatusMoveOnLoadOpcps '+checked_carrier );
		if (typeof checked_carrier == 'undefined') {
			return false;
		}
		checked_carrier = checked_carrier.replace(',','');
		$('.packetery-widget-status').each(function() {
			var clone = $(this).clone();
			var id_carrier = $(this).parent().parent().attr('data-carrier');

			if (checked_carrier == id_carrier) {
				if ($('input[id="delivery_option_'+id_carrier+'"]').parent().parent().parent().find('.packetery-widget-status').length == 0) {
					$('input[id="delivery_option_'+id_carrier+'"]').parent().parent().parent().append(clone);
				}
			}
			//$(this).remove();
			$('.packetery_widget_popup_'+id_carrier+'_open').click(function() {
				if($(this).find('.js-name option:selected').val() == 0) {
					let $button = $(this).find('.confirmPacketeryDeliveryOption');
					if ($button.hasClass('disabled') == false) {
						$button.addClass('disabled');
						$button.css("pointer-events", "none");
					}
				}
			});
			$clone = $('.packetery_widget_popup_'+id_carrier+'_open').parent();
			if (checked_carrier == id_carrier) {
				$clone.css('display', 'block');
				if ($('.packetery_widget_popup_'+id_carrier+'_open:visible').attr('data-status') != 1) {
					bindsw.continueSetDisabled();
				}
			}


			console.log('try set row_packetery_carrier_id ' + id_carrier);
			var row_packetery_carrier_id = $('input[id="delivery_option_'+id_carrier+'"]').val();
			if (typeof row_packetery_carrier_id == 'undefined') {
				return true;
			}

			row_packetery_carrier_id = row_packetery_carrier_id.replace(',','');

			if (checked_carrier == row_packetery_carrier_id) {
				$clone.css('display', 'block');
				if ($clone.find('.packetery_widget_popup_'+id_carrier+'_open').data('status') == 0) {
					bindsw.continueSetDisabled();
				} else {
					bindsw.continueSetEnabled();
				}
			}
			setTimeout(function() {
				bindsw.checkContinueOpcps();
			}, 500);
		});

	},

	checkContinueOpcps: function() {
		if ($('.packetery-widget-status a:visible').length > 0) {
			if ($('.packetery-widget-status a:visible').data('status') == 1) {
				bindsw.continueSetEnabled();
			} else {
				bindsw.continueSetDisabled();
			}
		}
	},

	onChangeContinueOpcps: function() {
		$(document).on('cssClassChanged', 'button#btn_place_order', function() {
			console.log('onChangeContinueOpcps');
			setTimeout(function() {
				bindsw.checkContinueOpcps();
			}, 1500);
		});
		$(document).on('change', '#btn_place_order', function() {
			console.log('onChangeContinueOpcpsChangeAttr');
			setTimeout(function() {
				bindsw.checkContinueOpcps();
			}, 1500);
		});


	},

	opcBindsOpcps: function() {
		console.log('opcBindsOpcps');
		bindsw.readAjaxFields();
		// initialize each popup
		$('.packetery-widget-status a').css('font-size', 12);
		if ($('#js_packetery_carriers').length > 0) {
			var line = $('#js_packetery_carriers').val();
			var packetery_carriers = line.split(',');
			var cnt = packetery_carriers.length;
			console.log('bind each packetery carrier');
			for (var i = 0; i < cnt; i++) {
				var id_carrier = packetery_carriers[i];
				//console.log('carrier '+id_carrier);
				bindsw.bindCarrierPopupOpcps(id_carrier);
			}
		} else {
			console.log('cant find packetery carriers');
		}
		
		$('input[name="id_carrier"]').change(function() {
			var id_carrier = $(this).val();
			id_carrier = id_carrier.replace(',', '');
			$('.packetery-widget-status').css('display', 'none');
			$next = $(this).parent().next().find('div');
			if ($next.hasClass('packetery-widget-status') == true) {
				//show packetery link
				$next.css('display', 'block');
				//if popup exists
				if ($('#packetery_widget_popup_'+id_carrier).length > 0) {
					if ($('#packetery_widget_popup_'+id_carrier).find('.js-name option:selected').val() == '0') {
						$('#submit_easypay').addClass('disabled');
					}
				}
			} else {
				bindsw.continueSetEnabled();
				return false;
			}
			bindsw.showCarrierPopup(id_carrier);
		});
	},
	bindCarrierPopupOpcps: function(id_carrier) {
		console.log('bindCarrierPopupOpcps '+id_carrier);
		$('#packetery_widget_popup_'+id_carrier).popup({
			scrolllock: true,
			autoopen: false,
			transition: 'all 0.3s',
			blur: false
		});
		$('#packetery_widget_popup_'+id_carrier).css('display', 'block');
		$popup_block = $('#packetery_widget_popup_'+id_carrier);
		bindsw.bindPopupContinue();
		bindsw.preCheckOneCountryPopupWidget($popup_block);
	},
	bindPopupContinueOpcps: function() {
		console.log('bindPopupContinueOpcps');
		$('.confirmPacketeryDeliveryOption').unbind();
		$('.confirmPacketeryDeliveryOption').click(function() {
			var name_branch = $(this).parentsUntil('.popup_content').find('.js-name option:selected').text();

			var id_carrier = $(this).parentsUntil('.popup_content').parent().attr('data-carrier');
			$selected_branch = $('#packetery_widget_popup_'+id_carrier).find('.js-name option:selected');

			$('.packetery-widget-status a').text(widget_popup_lang_pac.please_choose_branch);
			$('.packetery-widget-status a').data('status', '0');

			$('div.carrier-extra-content.packetery_widget_wrapper:not([id="packetery_widget_popup_'+id_carrier+'"])').each(function() {
				$block = $(this);
				packetery.widgetClearField('city', $block);
				packetery.widgetClearField('name', $block);
				packetery.widgetDetailsClear($block);
				bindsw.preCheckOneCountryPopupWidget($block);
			});
			//widgetClearField: function(field, extra){

			//$('.packetery_widget_popup_5_open').data('status');
			//$('#delivery_option_'+id_carrier).parent().parent().parent().next().find('a').text(name_branch);
			//$('#delivery_option_'+id_carrier).parent().parent().parent().next().find('a').data('status', '1');
			$('a.packetery_widget_popup_'+id_carrier+'_open').text(name_branch);
			$('a.packetery_widget_popup_'+id_carrier+'_open').data('status', '1');
			bindsw.continueSetEnabled();
			//bindsw.continueSetDisabled();
		});
	},
/*END OPC One Page Checkout Prestashop*/

/*OPC Knowband Supercheckout*/
	widgetStatusMoveOnLoadSuper: function() {
		$('.packetery-widget-status').each(function() {
			var clone = $(this).clone();
			var id_carrier = $(this).parent().parent().data('carrier');
			$('.supercheckout_shipping_option[value="'+id_carrier+'"]').parent().next().append(clone);
			if ($('#choosed_carrier').val() == id_carrier) {
				$(clone).css('display', 'block');
				if ($(clone).data('value') != 1) {
					bindsw.continueSetDisabled();
				}
			}
			
			$(this).remove();
		});
	},
	opcBindsSuper: function() {
		// initialize each popup
		$('.packetery-widget-status a').css('font-size', 12);
		if ($('#js_packetery_carriers').length > 0) {
			var line = $('#js_packetery_carriers').val();
			var packetery_carriers = line.split(',');
			var cnt = packetery_carriers.length;
			for (var i = 0; i < cnt; i++) {
				var id_carrier = packetery_carriers[i];
				bindsw.bindCarrierPopupSuper(id_carrier);
			}
		}
		
		$('input[name="id_carrier"]').change(function() {
			var id_carrier = $(this).val();
			id_carrier = id_carrier.replace(',', '');
			$('.packetery-widget-status').css('display', 'none');
			$next = $(this).parent().next().find('div');
			if ($next.hasClass('packetery-widget-status') == true) {
				$next.css('display', 'block');
				if ($('#packetery_widget_popup_'+id_carrier).length > 0) {
					if ($('#packetery_widget_popup_'+id_carrier).find('.js-name option:selected').val() == '0') {
						$('#submit_easypay').addClass('disabled');
					}
				}
			} else {
				bindsw.continueSetEnabled();
				return false;
			}
			bindsw.showCarrierPopup(id_carrier);
		});
	},
	bindCarrierPopupSuper: function(id_carrier) {

		$('#packetery_widget_popup_'+id_carrier).popup({
			scrolllock: true,
			autoopen: false,
			transition: 'all 0.3s',
			blur: false
		});
		$('#packetery_widget_popup_'+id_carrier).css('display', 'block');
		bindsw.bindPopupContinueSuper();
	},
	bindPopupContinueSuper: function() {
		$('.confirmPacketeryDeliveryOption').unbind();
		$('.confirmPacketeryDeliveryOption').click(function() {
			var name_branch = $(this).parentsUntil('.popup_content').find('.js-name option:selected').text();
			var id_carrier = $(this).parentsUntil('.popup_content').parent().attr('data-carrier');

			$('.carrier_action input[value="'+id_carrier+'"]').parent().next().find('a').text(name_branch);
			$('#submit_easypay').removeClass('disabled');
		});
	},
/*END OPC Knowband Supercheckout*/

/*OPC EasyPay*/
	widgetStatusMoveOnLoadEasy: function() {
		$('.packetery-widget-status').each(function() {
			var clone = $(this).clone();
			var id_carrier = $(this).parent().parent().data('carrier');
			$('#id_carrier[value="'+id_carrier+'"]').parent().next().append(clone);
			$(this).remove();
			$clone = $('.packetery_widget_popup_'+id_carrier+'_open').parent();
			if ($('#choosed_carrier').val() == id_carrier) {
				$clone.css('display', 'block');
				if ($clone.data('status') != 1) {
					bindsw.continueSetDisabled();
				}
			}
		});
	},
	opcBindsEasy: function() {
		// initialize each popup
		$('.packetery-widget-status a').css('font-size', 12);
		if ($('#js_packetery_carriers').length > 0) {
			var line = $('#js_packetery_carriers').val();
			var packetery_carriers = line.split(',');
			var cnt = packetery_carriers.length;
			for (var i = 0; i < cnt; i++) {
				var id_carrier = packetery_carriers[i];
				bindsw.bindCarrierPopupEasy(id_carrier);
			}
		}
		
		$('input[name="id_carrier"]').change(function() {
			var id_carrier = $(this).val();
			id_carrier = id_carrier.replace(',', '');
			$('.packetery-widget-status').css('display', 'none');
			$next = $(this).parent().next().find('div');
			if ($next.hasClass('packetery-widget-status') == true) {
				$next.css('display', 'block');
				if ($('#packetery_widget_popup_'+id_carrier).length > 0) {
					if ($('#packetery_widget_popup_'+id_carrier).find('.js-name option:selected').val() == '0') {
						if ($('.packetery_widget_popup_'+id_carrier+'_open').data('status') == 0) {
							bindsw.continueSetDisabled();
						}
					}
				}
			} else {
				bindsw.continueSetEnabled();
				return false;
			}

			bindsw.showCarrierPopup(id_carrier);
		});
	},
	bindCarrierPopupEasy: function(id_carrier) {

		$('#packetery_widget_popup_'+id_carrier).popup({
			scrolllock: true,
			autoopen: false,
			transition: 'all 0.3s',
			blur: false
		});
		$('#packetery_widget_popup_'+id_carrier).css('display', 'block');
		bindsw.bindPopupContinueEasy();
	},
	bindPopupContinueEasy: function() {
		$('.confirmPacketeryDeliveryOption').unbind();
		$('.confirmPacketeryDeliveryOption').click(function() {
			var name_branch = $(this).parentsUntil('.popup_content').find('.js-name option:selected').text();

			var id_carrier = $(this).parentsUntil('.popup_content').parent().attr('data-carrier');

			$('.carrier_action input[value="'+id_carrier+'"]').parent().next().find('a').text(name_branch);
			$('.carrier_action input[value="'+id_carrier+'"]').parent().next().find('a').data('status', '1');
			$('#submit_easypay').removeClass('disabled');
		});
	},
/*END OPC EasyPay*/
/*CLASSIC*/
	widgetStatusMoveOnLoadClassic: function() {
		let checked_carrier = $('input[id^="delivery_option_"]:checked').val()
		if (typeof checked_carrier == 'undefined') {
			return false;
		}
		checked_carrier = checked_carrier.replace(',','');
		$('.packetery-widget-status').each(function() {
			var clone = $(this).clone();
			var id_carrier = $(this).parent().parent().data('carrier');

			$('input[id="delivery_option_'+id_carrier+'"]').parent().parent().parent().after(clone);
			$(this).remove();
			$('.packetery_widget_popup_'+id_carrier+'_open').click(function() {
				if($(this).find('.js-name option:selected').val() == 0) {
					let $button = $(this).find('.confirmPacketeryDeliveryOption');
					if ($button.hasClass('disabled') == false) {
						$button.addClass('disabled');
						$button.css("pointer-events", "none");
					}
				}
			});
			$clone = $('.packetery_widget_popup_'+id_carrier+'_open').parent();
			if (checked_carrier == id_carrier) {
				$clone.css('display', 'block');
				if ($clone.data('value') != 1) {
					bindsw.continueSetDisabled();
				}
			}

			var row_packetery_carrier_id = $('input[id="delivery_option_'+id_carrier+'"]').val();
			row_packetery_carrier_id = row_packetery_carrier_id.replace(',','');

			if (checked_carrier == row_packetery_carrier_id) {
				$clone.css('display', 'block');
				if ($('.packetery_widget_popup_'+id_carrier+'_open').data('status') == 0) {
					bindsw.continueSetDisabled();
				} else {
					bindsw.continueSetEnabled();
				}
			}
		});
	},
	opcBinds: function() {
		// initialize each popup
		if ($('#js_packetery_carriers').length > 0) {
			var line = $('#js_packetery_carriers').val();
			var packetery_carriers = line.split(',');
			var cnt = packetery_carriers.length;
			for (var i = 0; i < cnt; i++) {
				var id_carrier = packetery_carriers[i];
				bindsw.bindCarrierPopup(id_carrier);
			}
		}
		
		$('input[id^="delivery_option"]').unbind();
		$('input[id^="delivery_option"]').change(function() {
			$('.packetery-widget-status').css('display', 'none');
			$next = $(this).parent().parent().parent().next();
			if ($next.hasClass('packetery-widget-status') == true) {
				$next.css('display', 'block');
			} else {
				bindsw.continueSetEnabled();
				return false;
			}

			var id_carrier = $(this).val();
			id_carrier = id_carrier.replace(',', '');
			if ($('.carrier_action input[value="'+id_carrier+'"]').parent().next().find('a').data('status') == '0') {
				bindsw.continueSetDisabled();
			}
			bindsw.showCarrierPopup(id_carrier);
		});
	},
	continueSetDisabled: function() {
		if ($('.continue').hasClass('disabled') == false) {
			$('.continue').addClass('disabled');
			$('.continue').css("pointer-events", "none");
		}
		if ($('#submit_easypay').hasClass('disabled') == false) {
			$('#submit_easypay').addClass('disabled');
		}
		if ($('#btn_place_order').hasClass('disabled') == false) {
			console.log('continueSetDisabled');
			$('#btn_place_order').addClass('disabled');
			$('#btn_place_order').prop('disabled', true);
		}
	},
	continueSetEnabled: function() {
		console.log('continueSetEnabled');
		$('#submit_easypay').removeClass('disabled');
		$('#btn_place_order').removeClass('disabled');
		$('#btn_place_order').prop('disabled', false);
		if ($('.continue').hasClass('disabled') == true) {
			$('.continue').removeClass('disabled');
			$('.continue').css("pointer-events", "auto");
		}

	},
	bindPopupContinue: function() {
		$('.confirmPacketeryDeliveryOption').unbind();
		$('.confirmPacketeryDeliveryOption').click(function() {
			var name_branch = $(this).parentsUntil('.popup_content').find('.js-name option:selected').text();

			var id_carrier = $(this).parentsUntil('.popup_content').parent().attr('data-carrier');
			$selected_branch = $('#packetery_widget_popup_'+id_carrier).find('.js-name option:selected');

			$('.packetery-widget-status a').text(widget_popup_lang_pac.please_choose_branch);
			$('.packetery-widget-status a').data('status', '0');

			$('div.carrier-extra-content.packetery_widget_wrapper:not([id="packetery_widget_popup_'+id_carrier+'"])').each(function() {
				$block = $(this);
				packetery.widgetClearField('city', $block);
				packetery.widgetClearField('name', $block);
				packetery.widgetDetailsClear($block);
				bindsw.preCheckOneCountryPopupWidget($block);
			});
			//widgetClearField: function(field, extra){

			//$('.packetery_widget_popup_5_open').data('status');
			//$('#delivery_option_'+id_carrier).parent().parent().parent().next().find('a').text(name_branch);
			//$('#delivery_option_'+id_carrier).parent().parent().parent().next().find('a').data('status', '1');
			$('a.packetery_widget_popup_'+id_carrier+'_open').text(name_branch);
			$('a.packetery_widget_popup_'+id_carrier+'_open').data('status', '1');
			bindsw.continueSetEnabled();
			//bindsw.continueSetDisabled();
		});
	},
	bindCarrierPopup: function(id_carrier) {

		$('#packetery_widget_popup_'+id_carrier).popup({
			scrolllock: true,
			autoopen: false,
			transition: 'all 0.3s',
			blur: false
		});
		$('#packetery_widget_popup_'+id_carrier).css('display', 'block');
		$popup_block = $('#packetery_widget_popup_'+id_carrier);
		bindsw.bindPopupContinue();
		bindsw.preCheckOneCountryPopupWidget($popup_block);
	},

	preCheckOneCountryPopupWidget: function($popup_block) {
		if ($popup_block.find('select.js-country option').length == 1) {
			packetery.widgetGetCities($popup_block);
		}
	},

	checkContinue: function(id_carrier) {
		$selected_branch = $('#packetery_widget_popup_'+id_carrier).find('.js-name option:selected');
		if (($selected_branch.length > 0) && ($selected_branch.val() != 0)) {
			$('.continue').removeClass('disabled');
			$('.continue').css("pointer-events", "auto");
		} else {
			if ($('.continue').hasClass('disabled') == false) {
				$('.continue').addClass('disabled');
				$('.continue').css("pointer-events", "none");
			}
		}
	},
	showCarrierPopup: function(id_carrier) {
		$('#packetery_widget_popup_'+id_carrier+' #id_carrier_widget').val(id_carrier);
		if ($('#js_packetery_carriers').length > 0) {
			$('#packetery_widget_popup_'+id_carrier).css('display', 'block');

			bindsw.checkContinue(id_carrier);

			$('#packetery_widget_popup_'+id_carrier).popup('show');
			//$('#packetery_widget_popup_'+id_carrier).find('.js-name').trigger("change");
			for (var i = 1; i < 10; i++) {
				setTimeout(function(){
					$('#packetery_widget_popup_'+id_carrier).css('display', 'block');
				}, i*200);
			}
		}
	},
	branchSave: function() {
		$('#bo-widget-save-branch').click(function() {
			var id_order = $('#id_order_widget').val();
			var id_branch = $('.js-name option:selected').val();
			var name_branch = $('.js-name option:selected').text();
			var is_ad = $('.js-name option:selected').data('ad');
			
			ajaxs.changeOrderBranch(id_order, id_branch, name_branch, is_ad);
		});
	},
/*END POPUP*/
}

packetery = {
	widgetClearField: function(field, extra){
		var find_field = '#packetery-widget .js-'+field;
		$(extra).find(find_field).html('');
		$(extra).find(find_field).html('<option value="0" disabled="" selected>-- '+widget_popup_lang_pac.please_choose+' --</option>');
	},
	widgetFillField: function(field, data, extra){
		if (field == 'country') {
			packetery.widgetClearField('city', extra);
			packetery.widgetClearField('name', extra);
			$(extra).find('.pack-details-container').html('');
		} else if (field == 'city') {
			packetery.widgetClearField('city', extra);
			packetery.widgetClearField('name', extra);
			$(extra).find('.pack-details-container').html('');
		} else if (field == 'name') {
			packetery.widgetClearField('name', extra);
			var id_branch = 1;
		}

		var find_field = '#packetery-widget .js-'+field;
		var cnt = data.length;
		for (var i = 0; i < cnt; i++) {
			if (cnt == 1) {
				var selected = 'selected';
				$(extra).find(find_field+' option:selected').prop('selected', false);
			} else {
				var selected = '';
			}
			if (id_branch > 0) {
				var is_ad = 0;
				is_ad = data[i]['is_ad'];
				id_branch = data[i]['id_branch'];
				$(extra).find(find_field).append('<option value="'+id_branch+'" data-ad="'+is_ad+'" '+selected+'>'+data[i][field]+'</option>');
			} else {
				if (field == 'city') {
					var is_ad = 0;
					is_ad = data[i]['is_ad'];
					$(extra).find(find_field).append('<option value="'+data[i][field]+'" data-ad="'+is_ad+'" '+selected+'>'+data[i][field]+'</option>');
				} else {
					$(extra).find(find_field).append('<option value="'+data[i][field]+'" '+selected+'>'+data[i][field]+'</option>');
				}
			}
		}
		if (cnt == 1) {
			$(extra).find(find_field).change();
		}
	},
	widgetDetailsClear: function(extra) {
		$(extra).find('.widget_block_title').css('display', 'none');
		$(extra).find('.branch-foto').html('');
		//left
		$(extra).find('.pack-details-container').html('');

		//right
		$(extra).find('.pack-details-opening').html('');		
	},
	widgetFillDetails: function(data, extra) {
		var lang = JSON.parse($('#ajaxfields').val());
		$(extra).find('.widget_block_title').each(function() {
			if ($(this).css('display') == 'none') {
				$(this).css('display', 'block');
			}
		});
		// foto
		$(extra).find('.branch-foto').html('<div class="col-md-6"><a href="'+data.url+'" target="_blank"><img src="'+data.img+'" id="branch-image" /></a></div>');

		//right
		if (data.dressing_room == 1)
			var dressing_room = lang.yes;
		else
			var dressing_room = lang.no;
		if (data.claim_assistant == 1)
			var claim_assistant = lang.yes;
		else
			var claim_assistant = lang.no;
		if (data.packet_consignment == 1)
			var packet_consignment = lang.yes;
		else
			var packet_consignment = lang.no;

		$(extra).find('.pack-details-container').html('<div class="branch-details"></div>');
		if (data.region != undefined)
			if (data.region.length > 0)
				$(extra).find('.pack-details-container .branch-details').append('<p>'+data.region+', '+data.city+'</p>');
			else
				$(extra).find('.pack-details-container .branch-details').append('<p>'+data.city+'</p>');
		$(extra).find('.pack-details-container .branch-details').append('<p>'+data.street+'</p>');
		$(extra).find('.pack-details-container .branch-details').append('<p>'+data.place+'</p>');
		$(extra).find('.pack-details-container .branch-details').append('<p>'+lang.zip+': '+data['zip']+'</p>');
		$(extra).find('.pack-details-container .branch-details').append('<p>'+lang.max_weight+': '+data.max_weight+'</p>');
		$(extra).find('.pack-details-container .branch-details').append('<p>'+lang.dressing_room+': '+dressing_room+'</p>');
		$(extra).find('.pack-details-container .branch-details').append('<p>'+lang.claim_assistant+': '+claim_assistant+'</p>');
		$(extra).find('.pack-details-container .branch-details').append('<p>'+lang.packet_consignment+': '+packet_consignment+'</p>');
		$(extra).find('.pack-details-container .branch-details').append('<p><a href="'+data['url']+'" target="_blank">'+lang.moredetails+'</a></p>');

		//left
		$(extra).find('.pack-details-opening').html('<div class="col-md-6"></div>');
		var ohcs_html = data.opening_hours_short;
		var ohcl_html = data.opening_hours_long;
		var ohtable_html = data.opening_hours;
		if (ohcl_html != undefined) {
			if (ohcl_html.length > 0) {
				$(extra).find('.pack-details-opening').append('<div>'+ohcl_html+'</div>');
				return true;
			}
			if (ohtable_html.length > 0) {
				$(extra).find('.pack-details-opening').append('<div>'+ohtable_html+'</div>');
				return true;
			}
			if (ohcs_html.length > 0) {
				$(extra).find('.pack-details-opening').append('<div>'+ohcs_html+'</div>');
				return true;
			}
		}
	},

	widgetSaveOrderBranch: function(id_branch, is_cod){
		var id_carrier = $('.packetery_widget_wrapper:visible').data('carrier');
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax_front.php?action=widgetsaveorderbranch'+ajaxs.checkToken(),
	        data: {'id_branch': id_branch, 'is_cod':is_cod, 'id_carrier': id_carrier},
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                return true;
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },		
	    });
	},

	widgetGetCities: function(extra){
		var country = $(extra).find('#packetery-widget .js-country option:selected').val();
		packetery.widgetDetailsClear();
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax_front.php?action=widgetgetcities'+ajaxs.checkToken(),
	        data: {'country': country},
	        extra: extra,
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
	            data = JSON.parse(msg);
	            packetery.widgetFillField('city', data, this.extra);
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },		
	    });
	},

	widgetGetNames: function(extra){
		var country = $(extra).find('#packetery-widget .js-country option:selected').val();
		var city = $(extra).find('#packetery-widget .js-city option:selected').val();
		var is_ad = $(extra).find('#packetery-widget .js-city option:selected').data('ad');
		packetery.widgetDetailsClear(extra);
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax_front.php?action=widgetgetnames'+ajaxs.checkToken(),
	        data: {'country': country, 'city': city, 'is_ad':is_ad},
	        extra: extra,
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                data = JSON.parse(msg);
                packetery.widgetFillField('name', data, this.extra)
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },
	    });
	},
	widgetGetDetails: function(extra){
		var country = $(extra).find('#packetery-widget .js-country option:selected').val();
		var city = $(extra).find('#packetery-widget .js-city option:selected').val();
		var id_branch = $(extra).find('#packetery-widget .js-name option:selected').val();
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax_front.php?action=widgetgetdetails'+ajaxs.checkToken(),
	        data: {'country': country, 'city': city, 'id_branch': id_branch},
	        extra: extra,
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                data = JSON.parse(msg);
                packetery.widgetFillDetails(data, this.extra);
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },		
	    });
	},
}

ajaxsw = {
	baseuri:  function(){
		return $('#baseuri').val();
	},		
	checkToken:  function(){
		return '&token='+prestashop.static_token;
	},
}

var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};

var getStringParameter = function getUrlParameter(sParam, url) {
    var sPageURL = decodeURIComponent(url),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;
    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};