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

var widget_type = $('#widget_type').val();
$(document).ready(function(){

	tools.fixextracontent();

	$('#packetery-widget .js-country').unbind();
	$(document).on('change', '#packetery-widget .js-country', function(){
		if (widget_type == 0) {
			if ($('.confirmPacketeryDeliveryOption').hasClass('disabled') == false) {
				$('.confirmPacketeryDeliveryOption').addClass('disabled');
				$('.confirmPacketeryDeliveryOption').css("pointer-events", "none");
			}
		} else {
			if ($('button[name="confirmDeliveryOption"]').hasClass('disabled') == false) {
				$('button[name="confirmDeliveryOption"]').addClass('disabled');
				$('button[name="confirmDeliveryOption"]').css("pointer-events", "none");
			}			
		}
		var extra = $(this).parentsUntil('.carrier-extra-content').parent();
		packetery.widgetGetCities(extra);
		return false;
	});
	$('#packetery-widget .js-city').unbind();
	$(document).on('change', '#packetery-widget .js-city', function(){
		if (widget_type == 0) {
			if ($('.confirmPacketeryDeliveryOption').hasClass('disabled') == false) {
				$('.confirmPacketeryDeliveryOption').addClass('disabled');
				$('.confirmPacketeryDeliveryOption').css("pointer-events", "none");
			}
		} else {
			if ($('button[name="confirmDeliveryOption"]').hasClass('disabled') == false) {
				$('button[name="confirmDeliveryOption"]').addClass('disabled');
				$('button[name="confirmDeliveryOption"]').css("pointer-events", "none");
			}
		}
		var extra = $(this).parentsUntil('.carrier-extra-content').parent();
		packetery.widgetGetNames(extra);
	});
	$('#packetery-widget .js-name').unbind();
	$(document).on('change', '#packetery-widget .js-name', function(){

		var extra = $(this).parentsUntil('.carrier-extra-content').parent();
		var id_branch = $(this).find('option:selected').val();
		if (id_branch > 0) {
			console.log('widget_type '+widget_type);
			if (widget_type == '0') {
				$('.confirmPacketeryDeliveryOption').removeClass('disabled');
				$('.confirmPacketeryDeliveryOption').css("pointer-events", "auto");
				console.log('confirmPacketeryDeliveryOption');
			} else {
				$('button[name="confirmDeliveryOption"]').removeClass('disabled');
				$('button[name="confirmDeliveryOption"]').css("pointer-events", "auto");
				console.log('confirmPacketeryDeliveryOption1');
			}
		}
		var is_cod = 0;
		var is_ad = $(this).find('option:selected').data('ad');
		packetery.widgetSaveOrderBranch(id_branch, is_cod);
		if (is_ad == 1) {
			packetery.widgetDetailsClear(extra);
		} else {
			packetery.widgetGetDetails(extra);
		}
		return false;
	});
});

tools = {
	checkwidgetstatus: function(extra) {
		tools.setcoutrycontext();
	},

	setcoutrycontext: function() {
		var id_address_delivery = prestashop.cart.id_address_delivery;
	},

	continueSetDisabled: function() {
		if ($('.continue').hasClass('disabled') == false) {
			$('.continue').addClass('disabled');
			$('.continue').css("pointer-events", "none");
		}
	},
	continueSetEnabled: function() {
		console.log('continueSetEnabled');
		if ($('.continue').hasClass('disabled') == true) {
			$('.continue').removeClass('disabled');
			$('.continue').css("pointer-events", "auto");
		}
	},

	fixextracontent: function() {
		if ($('.js_packetery_carriers').length == 0) {
			$('.carrier-extra-content').each(function() {
				if ($(this).css('display') == 'block') {
					if ($(this).find('#packetery-widget').length) {
						$('button[name="confirmDeliveryOption"]').addClass('disabled');
						$('button[name="confirmDeliveryOption"]').css("pointer-events", "none");											
					}
				}
			});
			$('.delivery-option input').change(function() {
				console.log('delivery');
				var id_carrier = $(this).val();
				id_carrier = id_carrier.replace(',', '');
				if ($(this).parent().parent().parent().next().hasClass('carrier-extra-content') == true) {
					var extra = $(this).parent().parent().parent().next();
				} else {
					var extra = $(this).parent().parent().next().next();
				}

				if ($(extra).hasClass('carrier-extra-content') == true) {
					if ($(extra).find('#widget_carrier').length > 0) {
						if (id_carrier == $(extra).find('#widget_carrier').val()) {
							console.log(id_carrier+' - '+$(extra).find('#widget_carrier').val());
						} else {
							tools.continueSetEnabled();
						}
					}
				}

				if ($(extra).hasClass('carrier-extra-content') == true) {
					if ($(extra).find('#packetery-widget').length) {
						var id_branch = $(extra).find('#packetery-widget .js-name option:selected').val();
						console.log('clicked packetery '+id_branch);
						if (id_branch > 0) {
							if (widget_type == 0) {
								$('button[name="confirmDeliveryOption"]').removeClass('disabled');
								$('button[name="confirmDeliveryOption"]').css("pointer-events", "auto");
							} else {
								$('.confirmPacketeryDeliveryOption').removeClass('disabled');
								$('.confirmPacketeryDeliveryOption').css("pointer-events", "auto");
							}
						} else {
							if (widget_type == 1) {
								$('button[name="confirmDeliveryOption"]').addClass('disabled');
								$('button[name="confirmDeliveryOption"]').css("pointer-events", "none");
							} else {
								$('.confirmPacketeryDeliveryOption').addClass('disabled');
								$('.confirmPacketeryDeliveryOption').css("pointer-events", "none");
							}
						}
					} else {
						if (widget_type == 1) {
							$('button[name="confirmDeliveryOption"]').removeClass('disabled');
							$('button[name="confirmDeliveryOption"]').css("pointer-events", "auto");
						} else {
							$('.confirmPacketeryDeliveryOption').removeClass('disabled');
							$('.confirmPacketeryDeliveryOption').css("pointer-events", "auto");
						}
					}
					setTimeout(function(){
						$(extra).css('display', 'block');
					}, 800);
					setTimeout(function(){
						$(extra).css('display', 'block');
					}, 1500);
					setTimeout(function(){
						$(extra).css('display', 'block');
					}, 3000);
					var id_carrier = $(this).val();
					tools.checkwidgetstatus(extra);
				}
			});
		}
	}
}

packetery = {
	widgetClearField: function(field, extra){
		var find_field = '#packetery-widget .js-'+field;
		$(extra).find(find_field).html('');
		$(extra).find(find_field).html('<option value="0" disabled="" selected>-- please choose --</option>');
	},
	widgetFillField: function(field, data, extra){
		if (field == 'country') {
			//console.log('country');
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
		//console.log(data);

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
	},

	widgetSaveOrderBranch: function(id_branch, is_cod){
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax_front.php?action=widgetsaveorderbranch'+ajaxs.checkToken(),
	        data: {'id_branch': id_branch, 'is_cod':is_cod},
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
		//console.log(country);
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax_front.php?action=widgetgetdetails'+ajaxs.checkToken(),
	        data: {'country': country, 'city': city, 'id_branch': id_branch},
	        extra: extra,
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                //console.log('msg'+msg);
                data = JSON.parse(msg);
                packetery.widgetFillDetails(data, this.extra);
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },		
	    });
	},
}

ajaxs = {
	baseuri:  function(){
		//console.log('baseuri' + $('#baseuri').val());
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
