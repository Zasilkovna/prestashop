
$(document).ready(function(){
	$('#delivery_option_7').click(function(){
		if($(this).attr('checked')){
			packetery.widget();
		}
	});
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
		bindsw.branchSave();
	});


});

bindsw = {
	branchSave: function() {
		$('#bo-widget-save-branch').click(function() {
			var id_order = $('#id_order_widget').val();
			var id_branch = $('.js-name option:selected').val();
			var name_branch = $('.js-name option:selected').text();
			var is_ad = $('.js-name option:selected').data('ad');
			
			ajaxs.changeOrderBranch(id_order, id_branch, name_branch, is_ad);
		});
	},
}

toolsf = {
	fixextracontent: function() {
		$('.delivery-option input').change(function() {
			var extra = $(this).parent().parent().parent().next();
			if ($(extra).hasClass('carrier-extra-content') == true) {
				setTimeout(function(){
					$(extra).css('display', 'block');
				}, 800);
				
			}
		});
	}
}

packetery = {
	widget: function(){
		alert('1');
	},
	widgetClearField: function(field){
		var find_field = '#packetery-widget .js-'+field;
		$(find_field).html('');
		$(find_field).html('<option value="0" disabled="" selected="">-- please choose --</option>');
	},
	widgetFillField: function(field, data){
		extra = $('html');
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
	widgetDetailsClear: function() {
		$('.widget_block_title').css('display', 'none');
		$('.branch-foto').html('');
		//left
		$('.pack-details-container').html('');
		//right
		$('.pack-details-opening').html('');		
	},

	widgetFillDetails: function(data) {
		var extra = $('html');
		var raw = $('#ajaxfields').val();
		var json = decodeURIComponent(raw);
		var lang = JSON.parse(json);
		if ($('.js-name option:selected').data('ad') == 1) {
			packetery.widgetDetailsClear();
			return;
		}
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
		$(extra).find('.pack-details-container .branch-details').append('<p>'+data.region+', '+data.city+'</p>');
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
	        url: ajaxsw.baseuri()+'/modules/packetery/ajax.php?action=widgetsaveorderbranch'+ajaxsw.checkToken(),
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

	widgetGetCities: function(){
		var country = $('#packetery-widget .js-country option:selected').val();
		packetery.widgetDetailsClear();
	    $.ajax({
			type: 'POST',
			url: ajaxsw.baseuri()+'/modules/packetery/ajax.php?action=widgetgetcities'+ajaxsw.checkToken(),
			data: {'country': country},
			beforeSend: function() {
				$("body").toggleClass("wait");
			},
	        success: function(msg) {
                data = JSON.parse(msg);
                packetery.widgetFillField('city', data);
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },		
	    });
	},

	widgetGetNames: function(){
		var extra = $('html');
		var country = $(extra).find('#packetery-widget .js-country option:selected').val();
		var city = $(extra).find('#packetery-widget .js-city option:selected').val();
		var is_ad = $(extra).find('#packetery-widget .js-city option:selected').data('ad');
		packetery.widgetDetailsClear();
	    $.ajax({
	        type: 'POST',
	        url: ajaxsw.baseuri()+'/modules/packetery/ajax.php?action=widgetgetnames'+ajaxsw.checkToken(),
	        data: {'country': country, 'city': city, 'is_ad':is_ad},
	        extra: extra,
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                data = JSON.parse(msg);
                packetery.widgetFillField('name', data)
	        },
	        complete: function() {
	            $("body").toggleClass("wait");
	        },
	    });
	},
	widgetGetDetails: function(){
		var country = $('#packetery-widget .js-country option:selected').val();
		var city = $('#packetery-widget .js-city option:selected').val();
		var id_branch = $('#packetery-widget .js-name option:selected').val();
	    $.ajax({
	        type: 'POST',
	        url: ajaxsw.baseuri()+'/modules/packetery/ajax.php?action=widgetgetdetails'+ajaxsw.checkToken(),
	        data: {'country': country, 'city': city, 'id_branch': id_branch},
	        beforeSend: function() {
	        	$("body").toggleClass("wait");
	        },
	        success: function(msg) {
                data = JSON.parse(msg);
                packetery.widgetFillDetails(data);
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
		var token = getUrlParameter('token');
		var check_e = $('#check_e').val();
		return '&token='+token+'&check_e='+check_e;
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