$(document).ready(function () {
    if ($('#ajaxfields').length === 0) {
        return;
    }
    var lang_pac = {};

    tools = {
        ad_list_build: function () {
            var carriers_json = decodeURIComponent($('#carriers_json').val());
            $('#ad-carriers-list-table table tr td:nth-child(5)').each(function () {
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
        buildselect: function (carriers_json, id_branch_chosen, zpoint, packeta_pickup_points, pp_all, all_packeta_pickup_points, pickup_point_type) {
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
                if (carriers[i]['id_branch'] == id_branch_chosen) {
                    var selected = 'selected';
                } else {
                    var selected = '';
                }
                html += '<option value="' + carriers[i]['id_branch'] + '" data-currency="' + carriers[i]['currency'] + '"' +
                    'data-pickup-point-type="' + carriers[i]['pickup_point_type'] + '" ' + selected + '>' +
                    carriers[i]['name'] + '</option>';
            }
            html+= '</select>';
            return html;
        },
    };

    binds = {
        readAjaxFields: function () {
            var raw = $('#ajaxfields').val();
            var json = decodeURIComponent(raw);
            lang_pac = JSON.parse(json);
        },
        tab_branch_list: function () {
            $('a[href="#tab-branch"]').click(function () {
                ajaxs.getCountBranches();
            });
        },

        ad_carrier_cod: function () {
            $('#ad-carriers-list-table i.status').unbind();
            $('#ad-carriers-list-table i.status').click(function () {
                var id_carrier = $(this).parent().parent().find('td').first().find('span').text();
                if ($(this).hasClass('icon-remove')) {
                    var value = 1;
                } else {
                    var value = 0;
                }
                ajaxs.change_ad_carrier_cod(id_carrier, value, this);
            });
        },
        ad_carrier_select: function () {
            $('#ad-carriers-list-table select').unbind();
            $('#ad-carriers-list-table select').change(function () {
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
        baseuri:  function () {
            return $('#baseuri').val();
        },

        set_ad_carrier_association: function (id_carrier, id_branch, branch_name, currency, pickup_point_type) {
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
                beforeSend: function () {
                    $("body").toggleClass("wait");
                },
                success: function (msg) {
                    if (msg == 'ok') {
                        $('#ad-carriers-list-table .panel').notify(lang_pac.success, "success",{position:"top"});
                    } else {
                        $('#ad-carriers-list-table .panel').notify(lang_pac.error, "error",{position:"top"});
                    }
                },
                complete: function () {
                    $("body").toggleClass("wait");
                },
            });
        },

        change_ad_carrier_cod: function (id_carrier, value, container) {
            $.ajax({
                type: 'POST',
                url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=change_ad_carrier_cod',
                data: {'id_carrier':id_carrier, 'value':value},
                container: container,
                beforeSend: function () {
                    $("body").toggleClass("wait");
                },
                success: function (msg) {
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
                complete: function () {
                    $("body").toggleClass("wait");
                },
            });
        },

        getCountBranches: function () {
            $.ajax({
                type: 'POST',
                url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=getcountbranches',
                data: {},
                beforeSend: function () {
                    $("body").toggleClass("wait");
                },
                success: function (msg) {
                    var res = JSON.parse(msg);
                    var cnt = res[0];
                    var last_update = res[1];
                    $('.packetery-total-branches').html('<b>' + cnt + '</b>');
                    $('.packetery-last-branches-update').html(last_update);
                },
                complete: function () {
                    $("body").toggleClass("wait");
                },
            });
        },

        updateBranches: function (container, reload) {
            $(container).notify(lang_pac.try_download_branches, "info",{position:"right"});
            $.ajax({
                type: 'POST',
                url: ajaxs.baseuri()+'/modules/packetery/ajax.php?action=updatebranches',
                data: {},
                container: container,
                beforeSend: function () {
                    $("body").toggleClass("wait");
                },
                success: function (msg) {
                    $(this.container).focus();

                    if (msg != 'true') {
                        /* TODO: Uncaught SyntaxError: JSON.parse: unexpected character at line 1 column 1 of the JSON data
                        loader stays shown, everything seems ok after reload */
                        var res = JSON.parse(msg);
                        var id = res[0];
                        var message = res[1];

                        if (message == "") {
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
                            setTimeout(function () {
                                location.reload();
                            }, 5000);
                        } else {
                            ajaxs.getCountBranches();
                        }
                    }

                },
                error: function () {
                    // TODO: prepare message for user
                    console.log('Branches update failed. Is API key provided?');
                },
                complete: function () {
                    $("body").toggleClass("wait");
                },
            });
        },
    };

    binds.readAjaxFields();
    binds.ad_carrier_cod();
    /*End SETTINGS ACTIONS*/

    $('#update-branches').click(function () {
        ajaxs.updateBranches('#update-branches', false);
    });
    tools.ad_list_build();
    binds.tab_branch_list();
});

$(document).ready(function () {
    var $widgetHdButton = $('.open-packeta-hd-widget');
    if ($widgetHdButton.length === 1) {
        $.getScript('https://widget-hd.packeta.com/www/js/library-hd.js').fail(function () {
            console.error('Unable to load Packeta home delivery widget.');
        });

        if ($('.picked-delivery-place').data('validated') === '') {
            $('.validatedAddress').hide();
        }

        var widgetHdOptionsData = $widgetHdButton.data('widget-options');
        var widgetHdOptions = {
            layout: 'hd',
            carrierId: widgetHdOptionsData['carrierId'],
            country: widgetHdOptionsData['country'],
            language: widgetHdOptionsData['language'],
            street: widgetHdOptionsData['street'],
            houseNumber: widgetHdOptionsData['houseNumber'],
            city: widgetHdOptionsData['city'],
            postcode: widgetHdOptionsData['zip']
        };
        $widgetHdButton.on('click', function (event) {
            event.preventDefault();
            PacketaHD.Widget.pick(widgetHdOptionsData['apiKey'], function (result) {
                if (result !== null && result.address !== null) {
                    var address = result.address;
                    $('.packetery form input[name="address"]').val(JSON.stringify(address));
                    $('.packetery-street').text(address.street + ' ' + address.houseNumber);
                    $('.packetery-city').text(address.city);
                    $('.packetery-zip').text(address.postcode);
                    $('.packetery-county').text(address.county);
                    $('.packetery-gps').text(address.latitude + ', ' + address.longitude);
                    $('.validatedAddress').show();
                }
            }, widgetHdOptions);
        });
    }

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
