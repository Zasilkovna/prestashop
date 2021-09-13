$(document).ready(function(){
	if ($('#ajaxfields').length === 0) {
		return;
	}
	var lang_pac = {};

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

	// starting with 0 before calling psTableAddCheckbox
	var orderColumnId = 0;
	var orderColumnCod = 4;
	var orderColumnExported = 7;
	var orderColumnTracking = 8;
	var orderColumnWeight = 9;

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

		// this shifts column order
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
				var id_order = $(this).find('td:eq(' + orderColumnId + ') span').text();
				$(this).attr('data-id-order', id_order);
			});
		},

		psTableTrackingLinks: function(pstable_jq_select) {
			$(pstable_jq_select).find('table tbody tr').each(function() {
				var el = $(this).find('td:eq(' + orderColumnTracking + ') span');
				var tracking = $(el).text();
				if (tracking.length > 0) {
					var tracking_link = '<a href="https://www.zasilkovna.cz/Z'+tracking+'" target="_blank">Z'+tracking+'</a>';
					$(el).html(tracking_link);
				}
			});
			binds.checkboxAllTable();
			binds.order_cod();
		},

		ordersAddWeightInputs: function (pstable_jq_select) {
			$(pstable_jq_select).find('table tbody tr').each(function () {
				var orderId = $(this).data('id-order');
				var $weightColumn = $(this).find('td:eq(' + orderColumnWeight + ')');
				$weightColumn.html(
					'<input type="text" value="' + $weightColumn.text().trim() + '" name="weight_' + orderId + '" class="weight">' +
					'<div class="notifyAnchor"></div>'
				);
			});
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
			var carriers_json = decodeURIComponent($('#carriers_json').val());
			var carrierColumnForSelect = 5;
			$('#ad-carriers-list-table table tr td:nth-child(' + carrierColumnForSelect + ')').each(function () {
				var id_branch_chosen = $(this).find('span').text();
				var zpoint = $('#zpoint').val();
				var pp_all = $('#pp_all').val();
				var packeta_pickup_points = $('#packeta_pickup_points').val();
				var all_packeta_pickup_points = $('#all_packeta_pickup_points').val();
				var pickup_point_type = $(this).parent().find('.hidden span').text();
				var select = tools.buildselect(carriers_json, id_branch_chosen, zpoint, packeta_pickup_points, pp_all, all_packeta_pickup_points, pickup_point_type);
				$(this).html(select);
			});
			binds.ad_carrier_select();
		},
		buildselect: function(carriers_json, id_branch_chosen, zpoint, packeta_pickup_points, pp_all, all_packeta_pickup_points, pickup_point_type) {
			// TODO: show hint to update branches if no carriers available
			var carriers = JSON.parse(carriers_json);
			var cnt = carriers.length;
			var html = '';
			html+= '<select name="selected_ad_carrier" id="selected_ad_carrier">';
			html+= '<option value="">--</option>';
			html+= '<option value="' + zpoint + '" data-pickup-point-type="internal"' +
				(pickup_point_type === 'internal' ? ' selected' : '') + '>' + packeta_pickup_points + '</option>';
			html+= '<option value="' + pp_all + '" data-pickup-point-type="external"' +
				((pickup_point_type === 'external' && id_branch_chosen === '') ? ' selected' : '') + '>' +
				all_packeta_pickup_points + '</option>';
			for (var i = 0; i < cnt; i++) {
				if (carriers[i]['id_branch'] == id_branch_chosen)
					var selected = 'selected';
				else
					var selected = '';
				html += '<option value="' + carriers[i]['id_branch'] + '" data-currency="' + carriers[i]['currency'] + '"' +
					'data-pickup-point-type="' + carriers[i]['pickup_point_type'] + '" ' + selected + '>' +
					carriers[i]['name'] + '</option>';
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
					var id_order = $(this).parents('tr.odd').data('id-order');
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
				var confirmed = true;

				$('#packetery-orders-table table tbody input[type="checkbox"]:checked').each(function() {
					var id_order = $(this).parents('tr.odd').data('id-order');
					var tracking_number = $(this).parents('tr.odd').find('td:last').find('a').text();

					if(tracking_number != "")
					{
						if(confirm(lang_pac.confirm_tracking_exists))
						{
							orders.push(id_order);
						}
						else
						{
							confirmed = false;
							return false;
						}
					}
					else
					{
						orders.push(id_order);
					}
				});

				if(confirmed)
				{
					var orders_id = orders.join();
					ajaxs.prepareOrdersBeforeExport(orders_id);
				}
			});
		},
		order_export_csv: function() {
			$('.export_selected_csv').click(function() {
				// get id_orders
				var orders = [];
				$('#packetery-orders-table table tbody input[type="checkbox"]:checked').each(function() {
					var id_order = $(this).parents('tr.odd').data('id-order');
					orders.push(id_order);
				});
				orders = orders.join(',');

				if(orders != "")
				{
					window.location = window.location.origin+ajaxs.baseuri()+"/modules/packetery/csv_export.php?orders="+orders;
				}
				else
				{
					alert("Nejsou vybrány žádné objednávky");
				}
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

		order_cod: function() {
			$('#packetery-orders-table table tr td:nth-child(' + (orderColumnCod + 1) + ')').find('i.status').unbind();
			$('#packetery-orders-table table tr td:nth-child(' + (orderColumnCod + 1) + ')').find('i.status').click(function () {
				var id_order = $(this).parents('tr.odd').data('id-order');
				if ($(this).hasClass('icon-remove'))
					var value = 1;
				else
					var value = 0;
				ajaxs.change_order_cod(id_order, value, this);
			});
		},

		watchWeights: function () {
			$('#tab-orders').on('change', 'input.weight', function () {
				$(this).data('changed', 'true');
			});
		},

		setWeights: function () {
			$('#tab-orders').on('click', 'input[name="set_weights"]', function () {
				var tableSelector = '#packetery-orders-table';
				var orderWeights = {};
				$(tableSelector).find('table tbody tr').each(function () {
					var $input = $(this).find('td:eq(' + (orderColumnWeight + 1) + ') input');
					if ($input.data('changed') === 'true') {
						var orderId = $(this).data('id-order');
						var weight = $input.val();
						orderWeights[orderId] = weight;
					}
				});
				$.ajax({
					type: 'POST',
					url: ajaxs.baseuri() + '/modules/packetery/ajax.php?action=setWeights',
					data: { 'orderWeights': orderWeights },
					dataType: 'json',
					beforeSend: function () {
						$('body').toggleClass('wait');
					},
					success: function (result) {
						if (result.info) {
							$(tableSelector + ' .panel').notify(result.info, 'info');
						}
						var successCount = 0;
						var errorCount = 0;
						for (var orderId in result) {
							if (result.hasOwnProperty(orderId)) {
								var $orderTr = $('tr[data-id-order="' + orderId + '"]');
								if (result[orderId].value) {
									$orderTr.find('td:eq(' + (orderColumnWeight + 1) + ') input').val(result[orderId].value);
									$orderTr.find('.notifyAnchor').notify(lang_pac.success, "success");
									successCount++;
								} else if (result[orderId].error) {
									$orderTr.find('.notifyAnchor').notify(result[orderId].error, "error");
									errorCount++;
								}
							}
						}
						if (errorCount !== 0 || successCount !== 0) {
							$(tableSelector + ' .panel').notify(
								(errorCount !== 0 ? lang_pac.weights_error + ': ' + errorCount + ' ' : '') +
								(successCount !== 0 ? lang_pac.weights_ok + ': ' + successCount : ''),
								(errorCount !== 0 ? 'error' : 'success')
							);
						}
					},
					error: function() {
						$(tableSelector + ' .panel').notify(lang_pac.error, "error");
					},
					complete: function () {
						$('body').toggleClass('wait');
					},
				});
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
				var pickup_point_type = $(this).find('option:selected').data('pickup-point-type');
				ajaxs.set_ad_carrier_association(id_carrier, id_branch, branch_name, currency, pickup_point_type);
			});
		},
	}

	ajaxs = {
		baseuri:  function(){
			return $('#baseuri').val();
		},
		getOrdersRows: function(page) {
			$.ajax({
				type: 'POST',
				url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=get_orders_rows',
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

		set_ad_carrier_association: function(id_carrier, id_branch, branch_name, currency, pickup_point_type){
			$.ajax({
				type: 'POST',
				url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=set_ad_carrier_association',
				data: {
					'id_carrier': id_carrier,
					'id_branch': id_branch,
					'branch_name': branch_name,
					'currency_branch': currency,
					'pickup_point_type': pickup_point_type,
				},
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

		change_ad_carrier_cod: function(id_carrier, value, container){
			$.ajax({
				type: 'POST',
				url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=change_ad_carrier_cod',
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
				url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=download_pdf',
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
				url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=prepare_order_export',
				data: {'orders_id':orders_id},
				id_orders: orders_id,
				beforeSend: function() {
					$("body").toggleClass("wait");
				},
				success: function(msg) {
					if (msg == 'ok') {
						ajaxs.orderExport(this.id_orders);
					} else {
						$('#packetery-orders-table .panel').notify(lang_pac.err_no_branch + ' - : ' + msg, "error", {position: "top"});
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
				url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=order_export',
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
								$(tr).find('td:eq(' + (orderColumnExported + 1) + ') i').replaceWith('<i class="icon-check status"></i>');
								$(tr).find('td:eq(' + (orderColumnTracking + 1) + ')').html('<span><a href="https://www.zasilkovna.cz/Z' + tracking_number + '" target="_blank">Z' + tracking_number + '</a></span>');
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

		change_order_cod: function(id_order, value, container){
			$.ajax({
				type: 'POST',
				url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=change_order_cod',
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
				url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=change_payment_cod',
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

		getCountBranches: function(){
			$.ajax({
				type: 'POST',
				url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=getcountbranches',
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
				url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=updatebranches',
				data: {},
				container: container,
				beforeSend: function() {
					$("body").toggleClass("wait");
				},
				success: function(msg) {
					$(this.container).focus();

					if (msg != 'true')
					{
						/* TODO: Uncaught SyntaxError: JSON.parse: unexpected character at line 1 column 1 of the JSON data
                        loader stays shown, everything seems ok after reload */
						var res = JSON.parse(msg);
						var id = res[0];
						var message = res[1];

						if(message == "")
						{
							message = lang_pac.error_export_unknown;
						}

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
				error: function() {
					// TODO: prepare message for user
					console.log('Branches update failed. Is API key provided?');
				},
				complete: function() {
					$("body").toggleClass("wait");
				},
			});
		},

		updateSettings: function(id, value){
			$.ajax({
				type: 'POST',
				url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=updatesettings',
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
						$('#packetery-form select[data-id="'+id+'"]').focus();
						$('#packetery-form select[data-id="'+id+'"]').notify(message, "error",{position:"top"});
					} else {
						var id = this.sid;
						$('#packetery-form input[data-id="'+id+'"]').notify(lang_pac.success, "success",{position:"r"});
						$('#packetery-form select[data-id="'+id+'"]').notify(lang_pac.success, "success",{position:"r"});
					}
				},
				complete: function() {
					$("body").toggleClass("wait");
				},
			});
		},
	};

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
	binds.ad_carrier_cod();
	/*End Change cod payment*/
	/*End SETTINGS ACTIONS*/

	$('#update-branches').click(function(){
		ajaxs.updateBranches('#update-branches', false);
	});
	tools.ad_list_build();

	tools.psTableTrackingLinks('#packetery-orders-table');
	tools.psTablePaginationChange('#packetery-orders-table');
	tools.psTableAddDataOrder('#packetery-orders-table');
	tools.ordersAddWeightInputs('#packetery-orders-table');
	tools.psTableAddCheckbox('#packetery-orders-table');

	binds.order_update();
	binds.order_download_pdf();
	binds.order_export();
	binds.order_export_csv();
	binds.tab_branch_list();
	binds.watchWeights();
	binds.setWeights();
});

$(document).ready(function () {
	var $widgetButton = $('.open-packeta-widget');
	if ($widgetButton.length === 1) {
		$.getScript("https://widget.packeta.com/v6/www/js/library.js")
			.fail(function () {
				console.error('Unable to load Packeta Widget.');
			});

		var widgetOptionsData = $widgetButton.data('widget-options');
		var widgetOptions = {
			appIdentity: widgetOptionsData['app_identity'],
			country: widgetOptionsData['country'],
			language: widgetOptionsData['lang']
		};
		if (widgetOptionsData['carriers']) {
			widgetOptions.carriers = widgetOptionsData['carriers'];
		}

		$widgetButton.on('click', function (event) {
			event.preventDefault();
			Packeta.Widget.pick(widgetOptionsData['api_key'], function (pickupPoint) {
				if (pickupPoint !== null) {
					$('.packetery form input[name="pickup_point"]').val(JSON.stringify(pickupPoint));
					$('.picked-delivery-place').text(pickupPoint.name);
				}
			}, widgetOptions);
		});
	}
});
