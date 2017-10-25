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

var lang_pac = '';
$(document).ready(function(){
	binds.readAjaxFields();

	/*SETTINGS ACTIONS*/
	$('#tab-settings .settings-input input').change(function(){
		var id = $(this).data('id');
		var value = $(this).val();
		ajaxs.updateSettings(id, value);
	});
	$('#tab-settings .settings-input select').change(function(){
		var id = $(this).data('id');
		var value = $(this).find('option:selected').val();
		ajaxs.updateSettings(id, value);
	});

	/*Change cod payment*/
	binds.payment_cod();
	binds.carrier_cod();
	binds.ad_carrier_cod();
	/*End Change cod payment*/
	/*ADD CARRIER*/
	binds.add_new_packetery_carrier();

	$('#packetery-carriers-list-table a.edit').click(function() {
		var url = $(this).attr('href');
		var id_carrier = getStringParameter('id_carrier', url);
		ajaxs.removecarrier(id_carrier);
		$(this).parent().parent().parent().parent().css('display', 'none');
		return false;
	});
	/*END ADD CARRIER*/
	/*End SETTINGS ACTIONS*/
	
	$('#add-packetery-carrier-block').popup();
	$('#change-order-branch').popup();

	$('#packetery-carriers-list-table i.process-icon-new').click(function(){
		$('#add-packetery-carrier-block').popup('show');
		return false;
	});

	$('#update-branches').click(function(){
		ajaxs.updateBranches('#update-branches', false);
	});
	tools.ad_list_build();
	tools.psTableTrackingLinks('#packetery-orders-table');

	tools.psTablePaginationChange('#packetery-orders-table');
	tools.psTableAddCheckbox('#packetery-orders-table');
	tools.psTableAddDataOrder('#packetery-orders-table');

	binds.order_change_branch();
	binds.order_update();
	binds.order_download_pdf();
	binds.order_export();
	binds.tab_branch_list();
});
// END ON READY

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



tools = {
	/*Pagination*/
	psTablePaginationChange: function(pstable_jq_select) {
		$('.prestaui-paginator-page').click(function() {
			var page = $(this).text();
			tools.clearOrdersTable(pstable_jq_select);
			ajaxs.getOrdersRows(page);
		});
	},
	drawOrdersRow: function(json) {
		var orders_arr = JSON.parse(json);
		var orders = orders_arr[0];
		var cnt = orders.length;
		for (var i = 0; i < cnt; i++) {
			var html = '';
			html += '<tr class="odd" data-id-order="'+orders[i].id_order+'">';
			html += '<td class="center"><span><input type="checkbox" class="ps-table-checkbox" value="0"></span></td>';
			html += '<td class="center"><span>'+orders[i].id_order+'</span></td>';
			html += '<td class="center">  <span>'+orders[i].customer+'</span> </td>';
			html += '<td class="center">  <span>'+orders[i].total+'</span> </td>';
			html += '<td class="center">  <span>'+orders[i].date+'</span> </td>';
			if (orders[i].is_cod == '1') {
				html += '<td><i class="icon-check status"></i></td>';
			} else {
				html += '<td><i class="icon-remove status"></i></td>';
			}
			
			html += '<td class="center"><span>'+orders[i].name_branch+'</span> </td>';

			if (orders[i].is_ad == '1') {
				html += '<td class="center"> <i class="icon-check status"></i>  </td>';
			} else {
				html += '<td class="center"> <i class="icon-remove status"></i>  </td>';
			}
			if (orders[i].exported == '1') {
				html += '<td class="center"> <i class="icon-check status"></i>  </td>';
			} else {
				html += '<td class="center"> <i class="icon-remove status"></i>  </td>';
			}
			if (orders[i].tracking_number.length > 0) {
				html += '<td class="center">  <span><a href="https://www.zasilkovna.cz/Z'+orders[i].tracking_number+'" target="_blank">Z'+orders[i].tracking_number+'</a></span> </td>';
			} else {
				html += '<td class="center"><span></span></td>';
			}
			html += '</tr>';
			$('#packetery-orders-table tbody').append(html);
		}
		binds.order_cod();
	},
	clearOrdersTable: function(pstable_jq_select) {
		$(pstable_jq_select).find('tbody').html('');
	},
	/*END pagination*/

	//packetery-orders-table
	psTableAddCheckbox: function(pstable_jq_select) {
		$(pstable_jq_select).find('table tbody tr').each(function() {
			$(this).prepend('<td class="center"><span><input type="checkbox" class="ps-table-checkbox" value="0"/></span></td>');
		});
		var head = '<th class="center"><span class="title_box"><input type="checkbox" class="ps-table-checkbox-all" value="0"/><span class="all-fix">'+lang_pac.all+'</span></span></th>';
		$(pstable_jq_select).find('table thead tr').prepend(head);
		binds.checkboxAllTable();
		binds.order_cod();
	},

	psTableAddDataOrder: function(pstable_jq_select) {
		$(pstable_jq_select).find('table tbody tr').each(function() {
			var id_order = $(this).find('td:eq(1) span').text();
			$(this).attr('data-id-order', id_order);
		});
	},

	psTableTrackingLinks: function(pstable_jq_select) {
		$(pstable_jq_select).find('table tbody tr').each(function() {
			var el = $(this).find('td:eq(8) span');
			var tracking = $(el).text();
			if (tracking.length > 0) {
				var tracking_link = '<a href="https://www.zasilkovna.cz/Z'+tracking+'" target="_blank">Z'+tracking+'</a>';
				$(el).html(tracking_link);
			}
		});
		binds.checkboxAllTable();
		binds.order_cod();
	},
	getmultilangfield: function(field_class) {
		var field_vars = {};
		$('.'+field_class).find('div[data-is="ps-input-text-lang-value"]').each(function() {
			var id_lang = $(this).attr('id-lang');
			var value = JSON.stringify($(this).find('.input').val());
			field_vars[id_lang] = value;
		});
		return field_vars;
	},
	ad_list_build: function() {
		var json_ad = decodeURIComponent($('#json_ad').val());
		$('#ad-carriers-list-table table tr td:nth-child(3)').each(function() {
			var id_branch = $(this).find('span').text();
			var select = tools.buildselect(json_ad, id_branch);
			$(this).html(select);
		});
		binds.ad_carrier_select();
	},
	buildselect: function(json, id_branch_default) {
		var html = '';
		html+= '<select name="selected_ad_carrier" id="selected_ad_carrier">';
		html+= '<option value="0">--</option>';
		var carriers = JSON.parse(json);
		var cnt = carriers.length;
		for (var i = 0; i < cnt; i++) {
			if (carriers[i]['id_branch'] == id_branch_default)
				var selected = 'selected';
			else
				var selected = '';
			html += '<option value="'+carriers[i]['id_branch']+'" data-currency="'+carriers[i]['currency']+'" '+selected+'>'+carriers[i]['name']+'</option>';
		}
		html+= '</select>';
		return html;
	},
};

binds = {
	readAjaxFields: function() {
		var raw = $('#ajaxfields').val();
		var json = decodeURIComponent(raw);
		lang_pac = JSON.parse(json);
	},
	tab_branch_list: function() {
		$('a[href="#tab-branch"]').click(function() {
			ajaxs.getCountBranches();
		});
	},
	

	order_update: function() {
		return true;
	},
	order_download_pdf: function () {
		$('#submit_download_pdf').click(function() {
			// get id_orders
			var orders = [];
			$('#packetery-orders-table table tbody input[type="checkbox"]:checked').each(function() {
				var id_order = $(this).parent().parent().parent().find('td:eq(1) span').text();
				orders.push(id_order);
			});
			var orders_id = orders.join();
			ajaxs.downloadPdf(orders_id);
		});
	},

	order_export: function() {
		$('.export_selected').click(function() {
			// get id_orders
			var orders = [];
			$('#packetery-orders-table table tbody input[type="checkbox"]:checked').each(function() {
				var id_order = $(this).parent().parent().parent().find('td:eq(1) span').text();
				orders.push(id_order);
			});
			var orders_id = orders.join();
			ajaxs.prepareOrdersBeforeExport(orders_id);
		});
	},
	add_new_packetery_carrier: function() {
		$('#submit_new_packetery_carrier').click(function(){
			var name = $('.new_carrier_name').val();
			var delay = $('.new_carrier_delay').val();
			var countries = [];
			$('#packetery_carrier_country option:selected').each(function(i) {
				countries[i] = $(this).val();
			});
			var country_str = countries.join();

			if ($('.new_carrier_is_cod').prop('checked') == true)
				var is_cod = 1;
			else
				var is_cod = 0;

			var logo = $('input[name=new_carrier_logo]:checked').data('option');
			if (logo == undefined)
				var logo = 'no';
			if (countries.length == 0) {
				$('#packetery_carrier_country').notify(lang_pac.error+' :'+lang_pac.err_country, "error",{position:"top"});
				return false;
			}
			ajaxs.new_carrier(name, delay, country_str, is_cod, logo);
			return false;
		});
	},


	uncheckboxAllTable: function() {
		$('#packetery-orders-table').find('.ps-table-checkbox').prop('checked', false);
	},

	checkboxAllTable: function() {
		$('.ps-table-checkbox-all').unbind();
		$('.ps-table-checkbox-all').click(function() {
			if ($(this).prop('checked') == true)
				var value = 1;
			else
				var value = 0;
			var table = $(this).parent().parent().parent().parent().parent();
			$(table).find('tbody tr').each(function() {
				if (value == 1)
					$(this).find('.ps-table-checkbox').prop('checked', true);
				else
					$(this).find('.ps-table-checkbox').prop('checked', false);
			});
		});

	},

	order_change_branch: function() {
		$('#packetery-orders-table table tr td:nth-child(7)').find('span').unbind();
		$('#packetery-orders-table table tr td:nth-child(7)').find('span').click(function(){
			var id_order = $(this).parent().parent().find('td:eq(1) span').text();
			$('#id_order_widget').val(id_order);
			$('#change-order-branch').popup('show');
		});
	},

	order_cod: function() {
		$('#packetery-orders-table table tr td:nth-child(6)').find('i.status').unbind();
		$('#packetery-orders-table table tr td:nth-child(6)').find('i.status').click(function(){
			var id_order = $(this).parent().parent().find('td:eq(1) span').text();
			if ($(this).hasClass('icon-remove'))
				var value = 1;
			else
				var value = 0;
			ajaxs.change_order_cod(id_order, value, this);
		});
	},

	payment_cod: function() {
		$('#payment-list-table i.status').unbind();
		$('#payment-list-table i.status').click(function(){
			var module_name = $(this).parent().next().find('span').text();
			if ($(this).hasClass('icon-remove'))
				var value = 1;
			else
				var value = 0;
			ajaxs.change_payment_cod(module_name, value, this);
		});
	},

	carrier_cod: function() {
		$('#packetery-carriers-list-table i.status').unbind();
		$('#packetery-carriers-list-table i.status').click(function(){
			var id_carrier = $(this).parent().parent().find('td').first().find('span').text();
			if ($(this).hasClass('icon-remove'))
				var value = 1;
			else
				var value = 0;
			ajaxs.change_carrier_cod(id_carrier, value, this);
		});
	},

	ad_carrier_cod: function() {
		$('#ad-carriers-list-table i.status').unbind();
		$('#ad-carriers-list-table i.status').click(function(){
			var id_carrier = $(this).parent().parent().find('td').first().find('span').text();
			if ($(this).hasClass('icon-remove'))
				var value = 1;
			else
				var value = 0;
			ajaxs.change_ad_carrier_cod(id_carrier, value, this);
		});
	},
	ad_carrier_select: function() {
		$('#ad-carriers-list-table select').unbind();
		$('#ad-carriers-list-table select').change(function(){
			var id_carrier = $(this).parent().parent().find('td').first().find('span').text();
			var id_branch = $(this).find('option:selected').val();
			var branch_name = $(this).find('option:selected').text();
			var currency = $(this).find('option:selected').data('currency');
			ajaxs.set_ad_carrier_association(id_carrier, id_branch, branch_name, currency);
		});
	},
}

ajaxs = {
	baseuri:  function(){
		return $('#baseuri').val();
	},		
	checkToken:  function(){
		var token = getUrlParameter('token');
		var check_e = $('#check_e').val();
		return '&token='+token+'&check_e='+check_e;
	},
	getOrdersRows: function(page) {
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=get_orders_rows'+ajaxs.checkToken(),
	        data: {'page':page},
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                tools.drawOrdersRow(msg);
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },		
	    });		
	},

	set_ad_carrier_association: function(id_carrier, id_branch, branch_name, currency){
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=set_ad_carrier_association'+ajaxs.checkToken(),
	        data: {'id_carrier':id_carrier, 'id_branch':id_branch, 'branch_name':branch_name, 'currency_branch':currency},
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                if (msg == 'ok') {
	                $('#ad-carriers-list-table .panel').notify(lang_pac.success, "success",{position:"top"});
                } else {
                	$('#ad-carriers-list-table .panel').notify(lang_pac.error, "error",{position:"top"});
                }
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },		
	    });
	},

	change_carrier_cod: function(id_carrier, value, container){
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=change_carrier_cod'+ajaxs.checkToken(),
	        data: {'id_carrier':id_carrier, 'value':value},
	        container: container,
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                if (msg == 'ok') {
                	if (value == 1) {
	                	$(this.container).replaceWith('<i class="icon-check status"></i>');
	                } else {
	                	$(this.container).replaceWith('<i class="icon-remove status"></i>');
	                }
	                binds.carrier_cod();
	                $('#packetery-carriers-list-table .panel').notify(lang_pac.success, "success",{position:"top"});
                } else {
                	$('#packetery-carriers-list-table .panel').notify(lang_pac.error+': '+msg, "error",{position:"top"});
                }
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },		
	    });
	},

	change_ad_carrier_cod: function(id_carrier, value, container){
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=change_ad_carrier_cod'+ajaxs.checkToken(),
	        data: {'id_carrier':id_carrier, 'value':value},
	        container: container,
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                if (msg == 'ok') {
                	if (value == 1) {
	                	$(this.container).replaceWith('<i class="icon-check status"></i>');
	                } else {
	                	$(this.container).replaceWith('<i class="icon-remove status"></i>');
	                }
	                binds.ad_carrier_cod();
	                $('#ad-carriers-list-table .panel').notify(lang_pac.success, "success",{position:"top"});
                } else {
                	$('#ad-carriers-list-table .panel').notify(lang_pac.error+': '+msg, "error",{position:"top"});
                }
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },		
	    });
	},

	downloadPdf: function(orders_id) {
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=download_pdf'+ajaxs.checkToken(),
	        data: {'orders_id':orders_id},
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                if (msg == 'ok' || msg.indexOf('asilkovna') !== -1) {
                	var url = ajaxs.baseuri()+'/modules/packetery/labels/'+msg;
                	url = window.location.origin + url;
  					$('.pdf_link').html('<a href="'+url+'">'+url+'</a>');
	                $('#packetery-orders-table .panel').notify(lang_pac.success, "success",{position:"top"});
                } else {
                	$('#packetery-orders-table .panel').notify(lang_pac.error+': '+msg, "error",{position:"top"});
                }
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },		
	    });		
	},

	prepareOrdersBeforeExport: function(orders_id) {
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=prepare_order_export'+ajaxs.checkToken(),
	        data: {'orders_id':orders_id},
	        id_orders: orders_id,
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                if (msg == 'ok') {
                	ajaxs.orderExport(this.id_orders);
				} else {
					$('#packetery-orders-table .panel').notify(lang_pac.err_no_branch+': '+msg, "error",{position:"top"});
				}
				$('#packetery-orders-table').find('.ps-table-checkbox').prop('checked', false);
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },
	    });
	},

	clearMassages: function() {
		$('#packetery-export-success').html('');
		$('.r-message-success').css('display', 'none');
		$('#packetery-export-error').html('');
		$('.validation-error').css('display', 'none');
	},

	orderExport: function(orders_id) {
		ajaxs.clearMassages();
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=order_export'+ajaxs.checkToken(),
	        data: {'orders_id':orders_id},
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                var IS_JSON = true;
				try {
					var orders = JSON.parse(msg);
				} catch(err) {
					IS_JSON = false;
				}

                if (IS_JSON == true) {
                	var cnt = orders.length;
                	for (var i = 0; i < cnt; i++) {
                		var id_order = orders[i][0];
                		if (orders[i][1] == 1) {
	                		var tracking_number = orders[i][2];
	                		var tr = $('#packetery-orders-table tr[data-id-order="'+id_order+'"]');
	                		$(tr).find('td:eq(8) i').replaceWith('<i class="icon-check status"></i>');
	                		$(tr).find('td:eq(9)').html('<span><a href="https://www.zasilkovna.cz/Z'+tracking_number+'" target="_blank">Z'+tracking_number+'</a></span>');
	                		$('#packetery-export-success').append('<div>Order '+id_order+' '+lang_pac.success_export+'</div>');
							$('.r-message-success').css('display', 'block');
                		} else {
                			var error = orders[i][2];
                			$('#packetery-export-error').append('<div>Order '+id_order+' '+lang_pac.error_export+' '+error+'</div>');
                			$('.validation-error').css('display', 'block');
                		}
                	}
                	setTimeout(function() {
                		$('#packetery-export-success').html('');
                		$('.r-message-success').css('display', 'none');
                	}, 10000);
				} else {
					$('#packetery-orders-table .panel').notify(lang_pac.error+': '+msg, "error",{position:"top"});
				}
				$('#packetery-orders-table').find('.ps-table-checkbox').prop('checked', false);
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },		
	    });
	},

	changeOrderBranch: function(id_order, id_branch, name_branch, is_ad) {
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=change_order_branch'+ajaxs.checkToken(),
	        data: {'id_branch': id_branch, 'id_order':id_order, 'name_branch':name_branch},
	        name_branch: name_branch,
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                if (msg == 'ok') {
                	$('#change-order-branch').popup('hide');
					var tr = $('#packetery-orders-table tr[data-id-order="'+id_order+'"]');
					$(tr).find('td:eq(6)').html('<span>'+this.name_branch+'</span>');
					// address delivery
                	if (is_ad == 1) {
	                	$(tr).find('td:eq(7) i').replaceWith('<i class="icon-check status"></i>');
	                } else {
	                	$(tr).find('td:eq(7) i').replaceWith('<i class="icon-remove status"></i>');
	                }
	                binds.order_change_branch();
	                $('#packetery-orders-table .panel').notify(lang_pac.success, "success",{position:"top"});
	                //end change data of tr object
                } else {
                	$('#packetery-orders-table .panel').notify(lang_pac.error, "error",{position:"top"});
                }
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },		
	    });		
	},

	change_order_cod: function(id_order, value, container){
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=change_order_cod'+ajaxs.checkToken(),
	        data: {'id_order':id_order, 'value':value},
	        container: container,
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                if (msg == 'ok') {
                	if (value == 1) {
	                	$(this.container).replaceWith('<i class="icon-check status"></i>');
	                } else {
	                	$(this.container).replaceWith('<i class="icon-remove status"></i>');
	                }
	                binds.order_cod();
	                $('#packetery-orders-table .panel').notify(lang_pac.success, "success",{position:"top"});
                } else {
                	$('#packetery-orders-table .panel').notify(lang_pac.error, "error",{position:"top"});
                }
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },		
	    });
	},

	change_payment_cod: function(module_name, value, container){
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=change_payment_cod'+ajaxs.checkToken(),
	        data: {'module_name':module_name, 'value':value},
	        container: container,
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                if (msg == 'ok') {
                	if (value == 1) {
	                	$(this.container).replaceWith('<i class="icon-check status"></i>');
	                } else {
	                	$(this.container).replaceWith('<i class="icon-remove status"></i>');
	                }
	                binds.payment_cod();
	                $('#payment-list-table .panel').notify(lang_pac.success, "success",{position:"top"});
                } else {
                	$('#payment-list-table .panel').notify(lang_pac.error, "error",{position:"top"});
                }
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },		
	    });
	},

	removecarrier: function(id_carrier){
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=remove_carrier'+ajaxs.checkToken(),
	        data: {'id_carrier':id_carrier},
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                if (msg == 'ok') {
					$('#packetery-carriers-list-table .panel').notify(lang_pac.success, "success",{position:"top"});
                } else {
                	$('#packetery-carriers-list-table .panel').notify(lang_pac.error, "error",{position:"top"});
                }
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },		
	    });
	},

	new_carrier: function(name, delay, countries, is_cod, logo){
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=new_carrier'+ajaxs.checkToken(),
	        data: {'name':name, 'delay':delay, 'countries':countries, 'is_cod':is_cod, 'logo':logo},
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                if (msg == 'ok') {
					$('#add-packetery-carrier-block').popup('hide');
	                setTimeout(function() {
	                	let href = location.href+'&active_tab=settings';
	                	location.href = href;
	                }, 500);
                } else {
                	$('#submit_new_packetery_carrier').notify(lang_pac.error, "error",{position:"top"});
                }

	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },		
	    });
	},

	getCountBranches: function(){
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=getcountbranches'+ajaxs.checkToken(),
	        data: {},
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                var res = JSON.parse(msg);
                var cnt = res[0];
                var last_update = res[1];
                $('.packetery-total-branches').html('<b>' + cnt + '</b>');
                $('.packetery-last-branches-update').html(last_update);
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },		
	    });
	},

	updateBranches: function(container, reload){
		$(container).notify(lang_pac.try_download_branches, "info",{position:"right"});
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=updatebranches'+ajaxs.checkToken(),
	        data: {},
	        container: container,
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                $(this.container).focus();
                if (msg != 'true')
                {
                    var res = JSON.parse(msg);
                    var id = res[0];
                    var message = res[1];
                    
                    $(this.container).notify(message, "error",{position:"top"});
                } else {
                	if (reload) {
                		var redirect_msg = ' ' + lang_pac.reload5sec;
                	} else {
                		var redirect_msg = '';
                	}
                	$(this.container).notify(lang_pac.success_download_branches + redirect_msg, "success",{position:"right"});

					if (reload) {
	                	setTimeout(function() {
	                		location.reload();
	                	}, 5000);
                	} else {
                		ajaxs.getCountBranches();
                	}
	        	}

	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },		
	    });
	},

	updateSettings: function(id, value){
	    $.ajax({
	        type: 'POST',
	        url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=updatesettings'+ajaxs.checkToken(),
	        data: {'value':value, 'id':id},
	        sid: id,
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                if (msg != 'true')
                {
                    var res = JSON.parse(msg);
                    var id = res[0];
                    var message = res[1];
                    $('#packetery-form input[data-id="'+id+'"]').focus();
                    $('#packetery-form input[data-id="'+id+'"]').notify(message, "error",{position:"top"});
                } else {
                	var id = this.sid;
	        		$('#packetery-form input[data-id="'+id+'"]').notify(lang_pac.success, "success",{position:"r"});
	        		$('#packetery-form select[data-id="'+id+'"]').notify(lang_pac.success, "success",{position:"r"});
	        		if (id == 2) {
		        		setTimeout(function() {
		        			ajaxs.updateBranches('#packetery-form input[data-id="2"]', true);
		        		}, 500);
	        		}
	        	}
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },
	    });
	},
};
