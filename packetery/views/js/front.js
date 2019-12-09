$.getScript("https://widget.packeta.com/www/js/library.js");
var widget_type = $('#widget_type').val();
let widget_lang_pac = '';
var country = 'cz,sk'; /* Default countries */
$(document).ready(function ()
{
    if ($(".zas-box").length)
        initializePacketaWidget();

    tools.fixextracontent();

    $(document).on('change', '#packetery-widget .js-name', function ()
    {
        var extra = $(this).parentsUntil('.carrier-extra-content').parent();
        var id_branch = $("#packeta-branch-id").val();
        var name_branch = $("#packeta-branch-name").val();
        var currency_branch = $("#packeta-branch-currency").val();
        if (id_branch > 0 && id_branch !== "")
        {
            tools.continueSetEnabled();

            let id_carrier = $('.delivery-option input:checked').val();
            if (typeof id_carrier == 'undefined')
            {
                id_carrier = $('.delivery_option.selected input').val();
            }
            id_carrier = id_carrier.replace(',', '');

            var is_ad = $(this).find('option:selected').data('ad');
            packetery.widgetSaveOrderBranch(id_branch, id_carrier, name_branch);
            if (is_ad == 1)
            {
                packetery.widgetDetailsClear(extra);
            } else
            {
                packetery.widgetGetDetails(extra);
            }
        }

        return false;
    });
});

window.initializePacketaWidget = function ()
{
    // set YOUR Packeta API key
    var packetaApiKey = jQuery("#packeta-api-key").val();

    var countries = JSON.parse($('#allowed_countries').val());

    // parameters
    var language = 'en';

    if ($('#shop-language').val() != '')
    {
        language = $('#shop-language').val();
    }

    /* Overwrite default countries with forced country or customer country */
    if ($('#widget_force_country').val() != "" && countries.indexOf($('#widget_force_country').val()) != -1)
    {
        country = $('#widget_force_country').val();
    }
    else if ($('#customer_country').val() != '' && countries.indexOf($('#customer_country').val()) != -1)
    {
        country = $('#customer_country').val();
    }

    /* Override language with forced language if it's set */
    if ($('#widget_force_language').val() != "")
    {
        language = $('#widget_force_language').val();
    }

    $('.open-packeta-widget').click(function (e)
    {
        e.preventDefault();
        var module_version = $('#module_version').val(); // Get module version for widget
        Packeta.Widget.pick(packetaApiKey, function (pickupPoint)
        {
            if (pickupPoint != null)
            {
                /* Get carrier row */
                var parent = $('.delivery-option input:checked').parents('.delivery-option').next();

                /* Add ID and name to inputs */
                $(parent).find('.packeta-branch-id').val(pickupPoint.id);
                $(parent).find('.packeta-branch-name').val(pickupPoint.name);

                // We let customer know, which branch he picked by filling html inputs
                $(parent).find('.picked-delivery-place').html(pickupPoint.name);

                tools.continueSetEnabled();

                /* Get ID of selected carrier */
                let id_carrier = $('.delivery-option input:checked').val();
                if (typeof id_carrier == 'undefined')
                {
                    id_carrier = $('.delivery_option.selected input').val();
                }
                id_carrier = id_carrier.replace(',', '');

                /* Save packetery order without order ID - just cart id so we can access carrier data later */
                packetery.widgetSaveOrderBranch(pickupPoint.id, id_carrier, pickupPoint.name);
            }
            else
            {
                /* Get carrier row */
                var parent = $('.delivery-option input:checked').parents('.delivery-option').next();

                /* If point isn't selected - disable */
                if($(parent).find('.packeta-branch-id').val() == "")
                {
                    tools.continueSetDisabled();
                }
            }
        }, {appIdentity: 'prestashop-1.7-packeta-' + module_version, country: country, language: language});
    });
};

tools = {
    readAjaxFields: function ()
    {
        let raw = $('#ajaxfields').val();
        if (typeof raw != 'undefined')
        {
            let json = decodeURIComponent(raw);
            widget_lang_pac = JSON.parse(json);
        }
    },

    continueSetDisabled: function ()
    {
        if ($('.continue').hasClass('disabled') == false)
        {
            $('.continue').addClass('disabled');
            $('.continue').css("pointer-events", "none");
        }
        if ($('#onepagecheckoutps_step_review .btn_place_order').hasClass('disabled') == false)
        {
            $('#onepagecheckoutps_step_review .btn_place_order').addClass('disabled');
        }
    },

    continueSetEnabled: function ()
    {
        if ($('.continue').hasClass('disabled') == true)
        {
            $('.continue').removeClass('disabled');
            $('.continue').css("pointer-events", "auto");
        }
        $('#onepagecheckoutps_step_review .btn_place_order').removeClass('disabled');
    },
    preCheckOneCountryWidget: function ()
    {
        if ((widget_type == 0) || (typeof widget_type == 'undefined'))
        {
            if ($('select.js-country:visible option').length == 1)
            {
                $el = $('select.js-country:visible option');
                var extra = $el.parentsUntil('.carrier-extra-content').parent();
                packetery.widgetGetCities(extra);
            }
        } else
        {
            $('select.js-country option').each(function ()
            {
                $el = $(this);
                let extra = $el.parentsUntil('.carrier-extra-content').parent();
                packetery.widgetGetCities(extra);
            });
        }
    },
    fixextracontent: function ()
    {
        if ($('.js_packetery_carriers').length == 0)
        {
            $('.carrier-extra-content').each(function ()
            {

                var widget_carrier = $(this).find('#widget_carrier');
                var valid_countries = $(this).find('#carrier_countries').val();

                if (typeof valid_countries != 'undefined' && widget_carrier.length)
                {
                    widget_carrier = $(widget_carrier).val();
                    valid_countries = JSON.parse(valid_countries);
                    if (typeof valid_countries[widget_carrier] != 'undefined')
                    {
                        if (valid_countries[widget_carrier].indexOf(country) == -1)
                        {
                            $(this).find('#open-packeta-widget').hide();
                            $(this).find('#selected-branch').hide();
                            $(this).find('#invalid-country-carrier').show();
                        }
                    }
                }

                /* Only displayed extra content */
                if ($(this).css('display') == 'block')
                {
                    /* If there's packetery-widget div */
                    if ($(this).find('#packetery-widget').length)
                    {
                        /* And branch is not set, disable */
                        var id_branch = $(this).find(".packeta-branch-id").val();
                        if (id_branch == 0 || id_branch == "")
                        {
                            $('button[name="confirmDeliveryOption"]').addClass('disabled');
                            $('button[name="confirmDeliveryOption"]').css("pointer-events", "none");
                        }
                    }
                }
            });

            /* Enable / Disable continue buttons after carrier change */
            $('.delivery-option input').change(function ()
            {
                var id_carrier = $(this).val();
                id_carrier = id_carrier.replace(',', '');

                var extra = $(this).parents('.delivery-option').next();

                if ($(extra).hasClass('carrier-extra-content') == true)
                {
                    if ($(extra).find('#widget_carrier').length > 0)
                    {
                        if (id_carrier != $(extra).find('#widget_carrier').val())
                        {
                            tools.continueSetEnabled();
                        }
                    }
                }

                if ($(extra).hasClass('carrier-extra-content') == true)
                {
                    if ($(extra).find('#packetery-widget').length)
                    {
                        var id_branch = $(extra).find(".packeta-branch-id").val();
                        var name_branch = $(extra).find(".packeta-branch-name").val();
                        if (id_branch > 0 && id_branch !== "")
                        {
                            $('button[name="confirmDeliveryOption"]').removeClass('disabled');
                            $('button[name="confirmDeliveryOption"]').css("pointer-events", "auto");
                            packetery.widgetSaveOrderBranch(id_branch, id_carrier, name_branch);
                        } else
                        {
                            $('button[name="confirmDeliveryOption"]').addClass('disabled');
                            $('button[name="confirmDeliveryOption"]').css("pointer-events", "none");
                        }
                    } else
                    {
                        if (widget_type == 1)
                        {
                            $('button[name="confirmDeliveryOption"]').removeClass('disabled');
                            $('button[name="confirmDeliveryOption"]').css("pointer-events", "auto");
                        } else
                        {
                            $('.confirmPacketeryDeliveryOption').removeClass('disabled');
                            $('.confirmPacketeryDeliveryOption').css("pointer-events", "auto");
                        }
                    }
                }
                var id_carrier = $(this).val();
            });
        }
        tools.preCheckOneCountryWidget();
    }
}

packetery = {
    widgetClearField: function (field, extra)
    {
        var find_field = '#packetery-widget .js-' + field;
        $(extra).find(find_field).html('');
        $(extra).find(find_field).html('<option value="0" disabled="" selected>--' + widget_lang_pac.please_choose + '--</option>');
    },
    widgetFillField: function (field, data, extra)
    {
        if (field == 'country')
        {
            packetery.widgetClearField('city', extra);
            packetery.widgetClearField('name', extra);
            $(extra).find('.pack-details-container').html('');
        } else if (field == 'city')
        {
            packetery.widgetClearField('city', extra);
            packetery.widgetClearField('name', extra);
            $(extra).find('.pack-details-container').html('');
        } else if (field == 'name')
        {
            packetery.widgetClearField('name', extra);
            var id_branch = 1;
        }

        var find_field = '#packetery-widget .js-' + field;
        var cnt = data.length;
        for (var i = 0; i < cnt; i++)
        {
            if (cnt == 1)
            {
                var selected = 'selected';
                $(extra).find(find_field + ' option:selected').prop('selected', false);
            } else
            {
                var selected = '';
            }
            if (id_branch > 0)
            {
                var is_ad = 0;
                is_ad = data[i]['is_ad'];
                id_branch = data[i]['id_branch'];
                $(extra).find(find_field).append('<option value="' + id_branch + '" data-ad="' + is_ad + '" ' + selected + '>' + data[i][field] + '</option>');
            } else
            {
                if (field == 'city')
                {
                    var is_ad = 0;
                    is_ad = data[i]['is_ad'];
                    $(extra).find(find_field).append('<option value="' + data[i][field] + '" data-ad="' + is_ad + '" ' + selected + '>' + data[i][field] + '</option>');
                } else
                {
                    $(extra).find(find_field).append('<option value="' + data[i][field] + '" ' + selected + '>' + data[i][field] + '</option>');
                }

            }
        }
        if (cnt == 1)
        {
            $(extra).find(find_field).change();
        }
    },
    widgetDetailsClear: function (extra)
    {
        $(extra).find('.widget_block_title').css('display', 'none');
        $(extra).find('.branch-foto').html('');
        //left
        $(extra).find('.pack-details-container').html('');

        //right
        $(extra).find('.pack-details-opening').html('');
    },
    widgetFillDetails: function (data, extra)
    {
        var lang = JSON.parse($('#ajaxfields').val());

        $(extra).find('.widget_block_title').each(function ()
        {
            if ($(this).css('display') == 'none')
            {
                $(this).css('display', 'block');
            }
        });

        // foto
        $(extra).find('.branch-foto').html('<div class="col-md-6"><a href="' + data.url + '" target="_blank"><img src="' + data.img + '" id="branch-image" /></a></div>');

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
            $(extra).find('.pack-details-container .branch-details').append('<p>' + data.region + ', ' + data.city + '</p>');
        else
            $(extra).find('.pack-details-container .branch-details').append('<p>' + data.city + '</p>');
        $(extra).find('.pack-details-container .branch-details').append('<p>' + data.street + '</p>');
        $(extra).find('.pack-details-container .branch-details').append('<p>' + data.place + '</p>');
        $(extra).find('.pack-details-container .branch-details').append('<p>' + lang.zip + ': ' + data['zip'] + '</p>');
        $(extra).find('.pack-details-container .branch-details').append('<p>' + lang.max_weight + ': ' + data.max_weight + '</p>');
        $(extra).find('.pack-details-container .branch-details').append('<p>' + lang.dressing_room + ': ' + dressing_room + '</p>');
        $(extra).find('.pack-details-container .branch-details').append('<p>' + lang.claim_assistant + ': ' + claim_assistant + '</p>');
        $(extra).find('.pack-details-container .branch-details').append('<p>' + lang.packet_consignment + ': ' + packet_consignment + '</p>');
        $(extra).find('.pack-details-container .branch-details').append('<p><a href="' + data['url'] + '" target="_blank">' + lang.moredetails + '</a></p>');

        //left
        $(extra).find('.pack-details-opening').html('<div class="col-md-6"></div>');
        var ohcs_html = data.opening_hours_short;
        var ohcl_html = data.opening_hours_long;
        var ohtable_html = data.opening_hours;
        if (ohcl_html.length > 0)
        {
            $(extra).find('.pack-details-opening').append('<div>' + ohcl_html + '</div>');
            return true;
        }
        if (ohtable_html.length > 0)
        {
            $(extra).find('.pack-details-opening').append('<div>' + ohtable_html + '</div>');
            return true;
        }
        if (ohcs_html.length > 0)
        {
            $(extra).find('.pack-details-opening').append('<div>' + ohcs_html + '</div>');
            return true;
        }
    },

    widgetSaveOrderBranch: function (id_branch, id_carrier, name_branch)
    {
        $.ajax({
            type: 'POST',
            url: ajaxs.baseuri() + '/modules/packetery/ajax_front.php?action=widgetsaveorderbranch' + ajaxs.checkToken(),
            data: {'id_branch': id_branch, 'id_carrier': id_carrier, 'name_branch': name_branch},
            beforeSend: function ()
            {
                $("body").toggleClass("wait");
            },
            success: function (msg)
            {
                return true;
            },
            complete: function ()
            {
                $("body").toggleClass("wait");
            },
        });
    },

    widgetGetCities: function (extra)
    {
        var country = $(extra).find('#packetery-widget .js-country option:selected').val();
        packetery.widgetDetailsClear();
        $.ajax({
            type: 'POST',
            url: ajaxs.baseuri() + '/modules/packetery/ajax_front.php?action=widgetgetcities' + ajaxs.checkToken(),
            data: {'country': country},
            extra: extra,
            beforeSend: function ()
            {
                $("body").toggleClass("wait");
            },
            success: function (msg)
            {
                data = JSON.parse(msg);
                packetery.widgetFillField('city', data, this.extra);
            },
            complete: function ()
            {
                $("body").toggleClass("wait");
            },
        });
    },

    widgetGetNames: function (extra)
    {
        var country = $(extra).find('#packetery-widget .js-country option:selected').val();
        var city = $(extra).find('#packetery-widget .js-city option:selected').val();
        var is_ad = $(extra).find('#packetery-widget .js-city option:selected').data('ad');
        packetery.widgetDetailsClear(extra);
        $.ajax({
            type: 'POST',
            url: ajaxs.baseuri() + '/modules/packetery/ajax_front.php?action=widgetgetnames' + ajaxs.checkToken(),
            data: {'country': country, 'city': city, 'is_ad': is_ad},
            extra: extra,
            beforeSend: function ()
            {
                $("body").toggleClass("wait");
            },
            success: function (msg)
            {
                data = JSON.parse(msg);
                packetery.widgetFillField('name', data, this.extra)
            },
            complete: function ()
            {
                $("body").toggleClass("wait");
            },
        });
    },
    widgetGetDetails: function (extra)
    {
        var country = $(extra).find('#packetery-widget .js-country option:selected').val();
        var city = $(extra).find('#packetery-widget .js-city option:selected').val();
        var id_branch = $(extra).find('#packetery-widget .js-name option:selected').val();
        $.ajax({
            type: 'POST',
            url: ajaxs.baseuri() + '/modules/packetery/ajax_front.php?action=widgetgetdetails' + ajaxs.checkToken(),
            data: {'country': country, 'city': city, 'id_branch': id_branch},
            extra: extra,
            beforeSend: function ()
            {
                $("body").toggleClass("wait");
            },
            success: function (msg)
            {
                data = JSON.parse(msg);
                if (data)
                {
                    packetery.widgetFillDetails(data, this.extra);
                }
            },
            complete: function ()
            {
                $("body").toggleClass("wait");
            },
        });
    },
}

ajaxs = {
    baseuri: function ()
    {
        return $('#baseuri').val();
    },
    checkToken: function ()
    {
        return '&token=' + prestashop.static_token;
    },
}

var getUrlParameter = function getUrlParameter(sParam)
{
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++)
    {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam)
        {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};

var getStringParameter = function getUrlParameter(sParam, url)
{
    var sPageURL = decodeURIComponent(url),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;
    for (i = 0; i < sURLVariables.length; i++)
    {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam)
        {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};
