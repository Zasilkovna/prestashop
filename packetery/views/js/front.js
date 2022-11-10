// non-blocking AJAX loading, speeds up page load
$.getScript("https://widget.packeta.com/v6/www/js/library.js")
    .fail(function() {
        console.error('Unable to load Packeta Widget.');
    });

var country = 'cz,sk'; /* Default countries */

function PacketeryCheckoutModulesManager() {
    // ids correspond to parts of class names in checkout-module/*.js - first letter in upper case
    this.supportedModules = ['Standard', 'Unknown', 'Supercheckout'];
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
            if ((manager.detectedModule === null) && module.findDeliveryOptions().length) {
                manager.detectedModule = module;
            }
        });

        return this.detectedModule;
    };

    // in case we need to change this in the future
    this.getCarrierId = function ($selectedInput) {
        return $selectedInput.val().replace(',', '');
    }

    this.getWidgetParent = function ($selectedInput) {
        return $('#packetery-carrier-' + this.getCarrierId($selectedInput));
    }
}
var packeteryModulesManager = new PacketeryCheckoutModulesManager();
var widgetInitialized = false;
var $selectedInput;

$(document).ready(function () {
    if ($('.zas-box').length) {
        onShippingLoadedCallback();
        widgetInitialized = true;
    }
});

$(window).load(function () {
    if ($('.zas-box').length && widgetInitialized === false) {
        onShippingLoadedCallback();
        widgetInitialized = true;
    }
});

window.initializePacketaWidget = function ()
{
    // set YOUR Packeta API key
    var packetaApiKey = $("#packeta-api-key").val();

    // no Packetery carrier enabled
    if (typeof packetaApiKey === 'undefined') {
        return;
    }

    // parameters common to all carriers
    var customerCountry = $('#customerCountry').val();
    if (customerCountry !== '') {
        country = customerCountry;
    }
    var language = 'en';
    var shopLanguage = $('#shop-language').val();
    if (shopLanguage !== '') {
        language = shopLanguage;
    }
    var app_identity = $('#app_identity').val(); // Get module version for widgets

    var module = packeteryModulesManager.detectModule();
    $selectedInput = module.getSelectedInput();
    if ($selectedInput.length === 0) {
        $(module.getExtraContentSelector()).hide();
        return;
    }

    $('.open-packeta-widget').click(function (e) {
        e.preventDefault();
        var widgetOptions = {
            appIdentity: app_identity,
            country: country,
            language: language,
        };
        var $widgetParent = packeteryModulesManager.getWidgetParent($selectedInput);
        var widgetCarriers = $widgetParent.find('#widget_carriers').val();
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
                $widgetParent.find('.packeta-branch-currency').val(pickupPoint.currency);
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
                    pickupPoint.carrierPickupPointId,
                    pickupPoint.currency
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

    $('.open-packeta-widget-hd').click(function (e) {
        e.preventDefault();
        var $widgetParent = packeteryModulesManager.getWidgetParent($selectedInput);
        var widgetCarriers = $widgetParent.find('#widget_carriers').val();
        var customerStreet = $widgetParent.find('#customerStreet').val();
        var customerHouseNumber = $widgetParent.find('#customerHouseNumber').val();
        var customerCity = $widgetParent.find('#customerCity').val();
        var customerZip = $widgetParent.find('#customerZip').val();
        var widgetOptions = {
            layout: 'hd',
            language: language,
            country: country,
            // in this case, there will always be one carrier
            carrierId: widgetCarriers,
        };
        if (customerStreet) {
            widgetOptions.street = customerStreet;
        }
        if (customerHouseNumber) {
            widgetOptions.houseNumber = customerHouseNumber;
        }
        if (customerCity) {
            widgetOptions.city = customerCity;
        }
        if (customerZip) {
            widgetOptions.postCode = customerZip;
        }
        Packeta.Widget.pick(packetaApiKey, function (result) {
            var $selectedDeliveryOption = module.getSelectedInput();
            $widgetParent = packeteryModulesManager.getWidgetParent($selectedDeliveryOption);

            if (result != null && result.address != null) {
                // there is also property packetaWidgetMessage which is true
                var address = result.address;
                var $addressValidationResult = $widgetParent.find('.address-validation-result');
                if (address.country === country) {
                    packetery.widgetSaveOrderAddress(address);
                    $widgetParent.find('#addressValidated').val(true);
                    $addressValidationResult.addClass('address-validated');
                    $addressValidationResult.text($widgetParent.find('#addressValidatedMessage').val());
                    $widgetParent.find('.picked-delivery-place').html(
                        address.street + ' ' + address.houseNumber + ', ' + address.city + ', ' + address.postcode
                    );
                    module.enableSubmitButton();
                } else {
                    $widgetParent.find('#addressValidated').val(false);
                    $addressValidationResult.removeClass('address-validated');
                    $addressValidationResult.text($widgetParent.find('#countryDiffersMessage').val());
                    if (PacketaModule.ui.isAddressValidationUnsatisfied($widgetParent)) {
                        module.disableSubmitButton();
                    }
                }
            }
        }, widgetOptions);
    });
};

PacketaModule = window.PacketaModule || {};

PacketaModule.ui = {
    isHdCarrier: function ($widgetParent) {
        return !!$widgetParent.find('#open-packeta-widget-hd').length;
    },

    isPpCarrier: function ($widgetParent) {
        return !!$widgetParent.find('#open-packeta-widget').length;
    },

    isPickupPointInvalid: function ($widgetParent) {
        var selectedBranchId = $widgetParent.find('.packeta-branch-id').val();
        return this.isPpCarrier($widgetParent) && !selectedBranchId;
    },

    isAddressValidationUnsatisfied: function ($widgetParent) {
        var addressValidated = PacketaModule.ui.makeBoolean($widgetParent.find('#addressValidated').val());
        var addressValidationSetting = $('#addressValidationSetting').val();
        return (this.isHdCarrier($widgetParent) && addressValidationSetting === 'required' && !addressValidated);
    },

    makeBoolean: function (value) {
        if (value === 'false') {
            return false;
        }
        return !!value;
    }
};

tools = {
    fixextracontent: function ()
    {
        var module = packeteryModulesManager.detectModule();

        if (module === null) {
            return;
        }

        $(module.getExtraContentSelector()).each(function ()
        {
            var $extra = $(this);
            if (!$extra.find('#open-packeta-widget').length && !$extra.find('#open-packeta-widget-hd').length) {
                return;
            }

            if ($extra.find('#open-packeta-widget').length) {
                var carrierId = String($extra.find('#carrier_id').val());
                var zpointCarriers = $extra.find('#zpoint_carriers').val();
                zpointCarriers = JSON.parse(zpointCarriers);
                if (!zpointCarriers.includes(carrierId)) {
                    $extra.find('#open-packeta-widget').hide();
                    $extra.find('#selected-branch').hide();
                }

                /* Only displayed extra content */
                if ($extra.is(':visible')) {
                    /* And branch is not set, disable */
                    var id_branch = $extra.find(".packeta-branch-id").val();
                    if (id_branch <= 0) {
                        module.disableSubmitButton();
                    }
                }
            }
            if ($extra.is(':visible') &&
                $extra.find('#open-packeta-widget-hd').length &&
                PacketaModule.ui.isAddressValidationUnsatisfied($extra)
            ) {
                module.disableSubmitButton();
            }
        });

        /* Enable / Disable continue buttons after carrier change */

        var $deliveryInputs = module.findDeliveryOptions();
        $deliveryInputs.change(function ()
        {
            var
                $this = $(this),
                prestashop_carrier_id = packeteryModulesManager.getCarrierId($this),
                $extra = packeteryModulesManager.getWidgetParent($this);

            // if selected carrier has no Packeta widget then enable Continue button and we're done here
            if (!$extra.find('#open-packeta-widget').length && !$extra.find('#open-packeta-widget-hd').length) {
                module.enableSubmitButton();
                return;
            }

            if ($this.is(':checked')) {
                $selectedInput = $this;
                var $wrapper = $extra.closest(module.getExtraContentSelector());
                setTimeout(function () {
                    if ($wrapper.is(':hidden')) {
                        $wrapper.show();
                    }
                }, 700);
            }

            if ($extra.find('#open-packeta-widget').length) {
                var id_branch = $extra.find(".packeta-branch-id").val();
                if (id_branch !== '') {
                    var name_branch = $extra.find(".packeta-branch-name").val();
                    var currency_branch = $extra.find(".packeta-branch-currency").val();
                    var pickup_point_type = $extra.find(".packeta-pickup-point-type").val();
                    var widget_carrier_id = $extra.find(".packeta-carrier-id").val();
                    var carrier_pickup_point_id = $extra.find(".packeta-carrier-pickup-point-id").val();
                    module.enableSubmitButton();
                    packetery.widgetSaveOrderBranch(prestashop_carrier_id, id_branch, name_branch, pickup_point_type, widget_carrier_id, carrier_pickup_point_id, currency_branch);
                } else {
                    module.disableSubmitButton();
                }
            }
            if ($extra.find('#open-packeta-widget-hd').length) {
                if (PacketaModule.ui.isAddressValidationUnsatisfied($extra)) {
                    module.disableSubmitButton();
                } else {
                    module.enableSubmitButton();
                }
            }
        });
    }
}

packetery = {
    widgetSaveOrderBranch: function (prestashop_carrier_id, id_branch, name_branch, pickup_point_type, widget_carrier_id, carrier_pickup_point_id, currency_branch)
    {
        $.ajax({
            type: 'POST',
            url: ajaxs.baseuri() + '/modules/packetery/ajax_front.php?action=widgetsaveorderbranch' + ajaxs.checkToken(),
            data: {
                'prestashop_carrier_id': prestashop_carrier_id,
                'id_branch': id_branch,
                'name_branch': name_branch,
                'currency_branch': currency_branch,
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
    widgetSaveOrderAddress: function (address) {
        $.ajax({
            type: 'POST',
            url: ajaxs.baseuri() + '/modules/packetery/ajax_front.php?action=widgetSaveOrderAddress' + ajaxs.checkToken(),
            data: {
                'address': address
            },
            beforeSend: function () {
                $("body").toggleClass("wait");
            },
            complete: function () {
                $("body").toggleClass("wait");
            }
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
        return '&token=' + prestashop.static_token;
    },
}


function onShippingLoadedCallback() {
    initializePacketaWidget();
    tools.fixextracontent();
}
