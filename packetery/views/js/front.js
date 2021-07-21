// non-blocking AJAX loading, speeds up page load
$.getScript("https://widget.packeta.com/v6/www/js/library.js")
    .fail(function() {
        console.error('Unable to load Packeta Widget.');
    });

var country = 'cz,sk'; /* Default countries */


function PacketeryCheckoutModulesManager() {
    // ids correspond to parts of class names in checkout-module/*.js - first letter in upper case
    this.supportedModules = ['Ps16', 'Standard', 'Unknown', 'Supercheckout'];
    this.loadedModules = [];
    this.detectedModule = null;

    this.loadModules = function() {
        this.loadedModules = [];
        var manager = this;

        this.supportedModules.forEach(function (moduleId) {
            // moduleId = 'Standard' => className = 'PacketeryCheckoutModuleStandard'
            var className = 'PacketeryCheckoutModule' + moduleId;

            // if really loaded via hookDisplayHeader()
            if (typeof window[className] !== 'undefined') {
                manager.loadedModules.push(window[className]);
            }
        });
    };

    this.detectModule = function () {
        if (this.detectedModule !== null) {
            return this.detectedModule;
        }

        if (this.loadedModules.length === 0) {
            this.loadModules();
        }

        var manager = this;
        this.loadedModules.forEach(function (module) {
            if ((manager.detectedModule === null) && module.isActive()) {
                manager.detectedModule = module;
            }
        });

        return this.detectedModule;
    };

    // in case we need to change this in the future
    this.getCarrierId = function ($selectedInput) {
        if($selectedInput.length === 0) {
            return null;
        }

        return $selectedInput.val().replace(',', '');
    }

    this.getWidgetParent = function ($selectedInput) {
        return $('#packetery-carrier-' + this.getCarrierId($selectedInput));
    }
}
var packeteryModulesManager = new PacketeryCheckoutModulesManager();
var widgetCarriers;

var PacketeryAjaxCallWaiter = function(onCompleted) {
    var promises = [];
    var start = false;
    var completedCalled = false;

    var promisesDone = function() {
        for(var key in promises) {
            if(promises.hasOwnProperty(key)) {
                if(promises[key].done !== true) {
                    return false
                }
            }
        }

        return true;
    };

    var checkCompleted = function() {
        if(start && promisesDone()) {
            if(completedCalled === false) {
                completedCalled = true;
                onCompleted(promises);
            }
        }
    };

    return {
        start: function() {
            start = true;
            checkCompleted();
        },
        addPromise: function(name, promise) {
            var wrapper = {
                name: name,
                done: null,
                response: null,
                promise: promise
            };

            promise.done(function(response) {
                wrapper.done = true;
                wrapper.response = response;
                checkCompleted();
            });

            promises.push(wrapper);
        }
    };
};

var packeteryCreateZasBoxes = function ($delivery_options, getExtraContentContainer, zpoint_carriers, onSuccess) {
    var waiter = PacketeryAjaxCallWaiter(onSuccess);
    $delivery_options.each(function (i, e) {
        if($(e).is(':checked') === false) {
            return;
        }

        // trim commas
        var carrierId = $(e).val().replace(/(^\,+)|(\,+$)/g, '');

        if (zpoint_carriers.includes(carrierId)) {
            /* Display button and inputs */
            // todo redo id attr to class attr ?
            var c = getExtraContentContainer($(e));

            if (c.find(".zas-box").length !== 0) {
                return; // continue to next option
            }

            var carrierPromise = packetery.createZasBoxHtml(carrierId).done(function(result) {
                c.find('.carrier-extra-content').remove();
                c.append(result);
            });

            waiter.addPromise(carrierId, carrierPromise);
        }

    });

    waiter.start();
}

var packeteryCheckBoxAndLoad = function() {
    if($(".zas-box").length === 0 && $('#zpoint_carriers').length === 0) {
        return; // incorrect context
    }

    var is16version = window.prestashop_version && window.prestashop_version.indexOf('1.6') === 0;
    if(is16version) {
        var zpointCarriers = $('#zpoint_carriers').val();
        var zpoint_carriers = JSON.parse(zpointCarriers);
        var module = packeteryModulesManager.detectModule();

        packeteryCreateZasBoxes(module.findDeliveryOptions(), function($input) {
            return module.getExtraContentContainer($input);
        }, zpoint_carriers, function() {
            onShippingLoadedCallback();
        });
    } else {
        onShippingLoadedCallback();
    }
};

$(document).ready(function ()
{
    packeteryCheckBoxAndLoad();
});

window.initializePacketaWidget = function ()
{
    // set YOUR Packeta API key
    var packetaApiKey = $("#packeta-api-key").val();

    // no Packetery carrier enabled
    if (typeof packetaApiKey === 'undefined') {
        return;
    }

    // parameters

    var customerCountry = $('#customer_country').val();
    if (customerCountry !== '') {
        country = customerCountry;
    }

    var language = 'en';

    var shopLanguage = $('#shop-language').val();
    if (shopLanguage !== '') {
        language = shopLanguage;
    }

    var module = packeteryModulesManager.detectModule();
    var $widgetParent = packeteryModulesManager.getWidgetParent(module.getSelectedInput());
    widgetCarriers = $widgetParent.find('#widget_carriers').val();

    $('.open-packeta-widget').click(function (e) {
        e.preventDefault();
        var app_identity = $('#app_identity').val(); // Get module version for widget
        var widgetOptions = {
            appIdentity: app_identity,
            country: country,
            language: language,
        };
        if (widgetCarriers !== '') {
            widgetOptions.carriers = widgetCarriers;
        }
        Packeta.Widget.pick(packetaApiKey, function (pickupPoint)
        {
            var $selectedDeliveryOption = module.getSelectedInput();
            $widgetParent = packeteryModulesManager.getWidgetParent($selectedDeliveryOption);

            if (pickupPoint != null)
            {
                /* Save needed pickup point attributes to inputs */
                $widgetParent.find('.packeta-branch-id').val(pickupPoint.id);
                $widgetParent.find('.packeta-branch-name').val(pickupPoint.name);
                $widgetParent.find('.packeta-pickup-point-type').val(pickupPoint.pickupPointType);
                $widgetParent.find('.packeta-carrier-id').val(pickupPoint.carrierId);
                $widgetParent.find('.packeta-carrier-pickup-point-id').val(pickupPoint.carrierPickupPointId);

                // We let customer know, which branch he picked by filling html inputs
                $widgetParent.find('.picked-delivery-place').html(pickupPoint.name);

                module.enableSubmitButton();

                /* Get ID of selected carrier */
                var prestashopCarrierId = packeteryModulesManager.getCarrierId($selectedDeliveryOption);

                /* Save packetery order without order ID - just cart id so we can access carrier data later */
                packetery.widgetSaveOrderBranch(
                    prestashopCarrierId,
                    pickupPoint.id,
                    pickupPoint.name,
                    pickupPoint.pickupPointType,
                    pickupPoint.carrierId,
                    pickupPoint.carrierPickupPointId
                );

                if (module !== null) {
                    module.hideValidationErrors();
                }
            }
            else
            {
                /* If point isn't selected - disable */
                if($widgetParent.find('.packeta-branch-id').val() === "") {
                    module.disableSubmitButton();
                }
            }
        }, widgetOptions);
    });
};

tools = {
    fixextracontent: function ()
    {
        var module = packeteryModulesManager.detectModule();

        if (module === null) {
            return;
        }

        var selectedCarrierId = packeteryModulesManager.getCarrierId(module.getSelectedInput());

        $('.carrier-extra-content').each(function ()
        {
            var $extra = $(this);
            if (! $extra.find('#packetery-widget').length) {
                return;
            }

            var carrierId = String($extra.find('#carrier_id').val());
            var zpointCarriers = $('#zpoint_carriers').val(); // todo move to global?? what about 1.7
            zpointCarriers = JSON.parse(zpointCarriers);
            if (selectedCarrierId === null || selectedCarrierId !== carrierId || !zpointCarriers.includes(carrierId)) {
                $extra.find('#open-packeta-widget').hide();
                $extra.find('#selected-branch').hide();
                $extra.find('#packetery-widget').hide();
            } else {
                $extra.find('#open-packeta-widget').show();
                $extra.find('#selected-branch').show();
                $extra.find('#packetery-widget').show();
            }

            /* Only displayed extra content */
            if ($extra.find('#packetery-widget').is(':hidden') === false) {
                /* And branch is not set, disable */
                var id_branch = $extra.find(".packeta-branch-id").val();
                if (id_branch <= 0) {
                    module.disableSubmitButton();
                }
            }
        });

        /* Enable / Disable continue buttons after carrier change */

        var $deliveryInputs = module.findDeliveryOptions();
        $deliveryInputs.off('change.packeteryFix').on('change.packeteryFix', function ()
        {
            module.disableSubmitButton();

            var
                $this = $(this),
                prestashop_carrier_id = packeteryModulesManager.getCarrierId($this),
                $extra = packeteryModulesManager.getWidgetParent($this);

            // if selected carrier is not Packetery then enable Continue button and we're done here
            if (! $extra.find('#packetery-widget').length) {
                module.enableSubmitButton();
                return;
            }

            if ($this.is(':checked')) {
                var $wrapper = $extra.closest('.carrier-extra-content');
                setTimeout(function () {
                    if ($wrapper.is(':hidden')) {
                        $wrapper.show();
                    }
                }, 500);
            }

            widgetCarriers = $extra.find("#widget_carriers").val();

            var id_branch = $extra.find(".packeta-branch-id").val();
            if (id_branch !== '') {
                var name_branch = $extra.find(".packeta-branch-name").val();
                var pickup_point_type = $extra.find(".packeta-pickup-point-type").val();
                var widget_carrier_id = $extra.find(".packeta-carrier-id").val();
                var carrier_pickup_point_id = $extra.find(".packeta-carrier-pickup-point-id").val();
                module.enableSubmitButton();
                packetery.widgetSaveOrderBranch(prestashop_carrier_id, id_branch, name_branch, pickup_point_type, widget_carrier_id, carrier_pickup_point_id);
            } else {
                module.disableSubmitButton();
            }

            packeteryCheckBoxAndLoad();
        });
    }
}

packetery = {
    widgetSaveOrderBranch: function (prestashop_carrier_id, id_branch, name_branch, pickup_point_type, widget_carrier_id, carrier_pickup_point_id)
    {
        $.ajax({
            type: 'POST',
            url: ajaxs.baseuri() + '/modules/packetery/ajax_front.php?action=widgetsaveorderbranch' + ajaxs.checkToken(),
            data: {
                'prestashop_carrier_id': prestashop_carrier_id,
                'id_branch': id_branch,
                'name_branch': name_branch,
                'pickup_point_type': pickup_point_type,
                'widget_carrier_id': widget_carrier_id,
                'carrier_pickup_point_id': carrier_pickup_point_id
            },
            beforeSend: function () {
                $("body").toggleClass("wait");
            },
            success: function (msg) {
                return true;
            },
            complete: function () {
                $("body").toggleClass("wait");
            },
        });
    },
    createZasBoxHtml: function(prestashop_carrier_id) {
        return $.ajax({
            type: 'POST',
            url: ajaxs.baseuri() + '/modules/packetery/ajax_front.php?action=createZasBoxHtml' + ajaxs.checkToken(),
            data: {
                'prestashop_carrier_id': prestashop_carrier_id,
            },
            beforeSend: function () {
                $("body").toggleClass("wait");
            },
            success: function (msg) {
                return true;
            },
            complete: function () {
                $("body").toggleClass("wait");
            },
        });
    }
}

ajaxs = {
    baseuri: function ()
    {
        return $('#baseuri').val();
    },
    checkToken: function ()
    {
        return '&token=' + window.packetery_ajax_front_token;
    },
}


function onShippingLoadedCallback() {
    initializePacketaWidget();
    tools.fixextracontent();
}
