PacketaModule = window.PacketaModule || {};

PacketaModule.runner = {
    onThisScriptLoad: function() {
        // non-blocking AJAX loading, speeds up page load
        $.getScript("https://widget.packeta.com/v6/www/js/library.js")
            .success(PacketaModule.runner.onWidgetLoad)
            .fail(function() {
                console.error('Unable to load Packeta Widget.');
            });
    },

    onWidgetLoad: function() {
        $(function() {
            onShippingLoadedCallback();
        });
    },
}

PacketaModule.ui = {
    toggleExtraContent: function () {
        // if template doesn't handle showing carrier-extra-content then we have to
        if ((! PacketaModule.config.toggleExtraContent) && (! tools.isPS16())) {
            return;
        }

        var module = packeteryModulesManager.detectModule();
        if (module === null) {
            return;
        }

        // hide it for all carriers, even for those that are not Packeta (emulate PS 1.7 behaviour)
        $('.carrier-extra-content').hide();
        packeteryModulesManager.getWidgetParent(module.getSelectedInput())
            .closest('.carrier-extra-content')
            .show();
    }
};

var country = 'cz,sk'; /* Default countries */

function PacketeryCheckoutModulesManager() {
    // ids correspond to parts of class names in checkout-module/*.js - first letter in upper case
    this.supportedModules = ['Ps16', 'Ps17', 'Unknown', 'Supercheckout'];
    this.loadedModules = [];
    this.detectedModule = null;

    this.loadModules = function() {
        this.loadedModules = [];
        var manager = this;

        this.supportedModules.forEach(function(moduleId) {
            // moduleId = 'Standard' => className = 'PacketeryCheckoutModuleStandard'
            var className = 'PacketeryCheckoutModule' + moduleId;

            // if really loaded via hookDisplayHeader()
            if (typeof window[className] !== 'undefined') {
                manager.loadedModules.push(window[className]);
            }
        });
    };

    this.detectModule = function() {
        if (this.detectedModule !== null) {
            return this.detectedModule;
        }

        if (this.loadedModules.length === 0) {
            this.loadModules();
        }

        var manager = this;
        this.loadedModules.forEach(function(module) {
            if ((manager.detectedModule === null) && module.isActive()) {
                manager.detectedModule = module;
            }
        });

        return this.detectedModule;
    };

    // in case we need to change this in the future
    this.getCarrierId = function($selectedInput) {
        return $selectedInput.val().replace(',', '');
    }

    this.getWidgetParent = function($selectedInput) {
        return $('#packetery-carrier-' + this.getCarrierId($selectedInput));
    }
}

var packeteryModulesManager = new PacketeryCheckoutModulesManager();

var packeteryCreateExtraContent = function(onSuccess) {
    var zpointCarriers = $('#zpoint_carriers').val();
    zpointCarriers = JSON.parse(zpointCarriers);
    var module = packeteryModulesManager.detectModule();
    if (module === null) {
        return;
    }

    var $delivery_options = module.findDeliveryOptions();

    var deferreds = [];
    $delivery_options.each(function(i, e) {
        // trim commas
        var carrierId = packeteryModulesManager.getCarrierId($(e));
        if (zpointCarriers.indexOf(carrierId) >= 0) {
            /* Display button and inputs */
            // todo redo id attr to class attr ?
            var c = module.getExtraContentContainer($(e));
            var carrierDeferred = packetery.packeteryCreateExtraContent(carrierId).done(function(result) {
                c.find('.carrier-extra-content').remove();
                c.append(result);
            });

            deferreds.push(carrierDeferred);
        }

    });

    $.when.apply(null, deferreds).then(onSuccess);
}


window.initializePacketaWidget = function() {
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
    if (module === null) {
        return;
    }

    $('.open-packeta-widget').click(function(e) {
        e.preventDefault();
        var app_identity = $('#app_identity').val(); // Get module version for widget
        var widgetOptions = {
            appIdentity: app_identity,
            country: country,
            language: language,
        };
        var $selectedDeliveryOption = module.getSelectedInput();
        if ($selectedDeliveryOption.length === 0) {
            // in supercheckout after switching country and no delivery is selected
            return;
        }
        var $widgetParent = packeteryModulesManager.getWidgetParent($selectedDeliveryOption);
        var widgetCarriers = $widgetParent.find('#widget_carriers').val();
        if (widgetCarriers !== '') {
            widgetOptions.carriers = widgetCarriers;
        }
        Packeta.Widget.pick(packetaApiKey, function(pickupPoint) {
            if (pickupPoint != null) {
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
            } else {
                /* If point isn't selected - disable */
                if ($widgetParent.find('.packeta-branch-id').val() === "") {
                    module.disableSubmitButton();
                }
            }
        }, widgetOptions);
    });

    if (PacketaModule.config.widgetAutoOpen) {
        var openWidget = function () {
            tools.openSelectedDeliveryWidget(module.getSelectedInput());
        };
        module.findDeliveryOptions().on('change', openWidget);
        openWidget();
    }
};

tools = {
    isPS16: function() {
        return PacketaModule.config.prestashopVersion && PacketaModule.config.prestashopVersion.indexOf('1.6') === 0;
    },
    checkExtraContentVisibility: function() {
        var module = packeteryModulesManager.detectModule();
        if (module === null) {
            return;
        }

        PacketaModule.ui.toggleExtraContent();

        var selectedCarrierId = packeteryModulesManager.getCarrierId(module.getSelectedInput());
        $('.carrier-extra-content').each(function() {
            var $extra = $(this);
            if (!$extra.find('#packetery-widget').length) {
                return;
            }

            var carrierId = String($extra.find('#carrier_id').val());
            if (selectedCarrierId === carrierId) {
                var id_branch = $extra.find(".packeta-branch-id").val();
                if (id_branch <= 0) {
                    module.disableSubmitButton();
                }
            }
        });
    },
    fixextracontent: function() {
        var module = packeteryModulesManager.detectModule();
        if (module === null) {
            return;
        }

        tools.checkExtraContentVisibility();

        /* Enable / Disable continue buttons after carrier change */

        var $deliveryInputs = module.findDeliveryOptions();
        $deliveryInputs.off('change.packeteryFix').on('change.packeteryFix', function() {
            module.disableSubmitButton();

            // PS 1.6 OPC re-creates the list of shipping methods, throwing out extra content in the process.
            // PS 1.6 5-steps checkout doesn't do that

            // todo: distinguish 5-steps to toggle visibility here, for OPC toggle in display-before-carrier via onShippingLoaded...

            tools.checkExtraContentVisibility();

            var
                $this = $(this),
                prestashop_carrier_id = packeteryModulesManager.getCarrierId($this),
                $extra = packeteryModulesManager.getWidgetParent($this);

            // if selected carrier is not Packetery then enable Continue button and we're done here
            if (!$extra.find('#packetery-widget').length) {
                module.enableSubmitButton();
                return;
            }

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
        });
    },

    openSelectedDeliveryWidget: function ($selectedDeliveryOption) {
        if ($selectedDeliveryOption.length !== 1) {
            return;
        }
        var $widgetParent = packeteryModulesManager.getWidgetParent($selectedDeliveryOption);
        var $widgetButton = $widgetParent.find('.open-packeta-widget');
        if (
            $widgetButton.length === 1 &&
            $widgetParent.find('.packeta-branch-id').val() === '' &&
            $('iframe #packeta-widget').length === 0
        ) {
            $widgetButton.click();
        }
    },
}

packetery = {
    widgetSaveOrderBranch: function(prestashop_carrier_id, id_branch, name_branch, pickup_point_type, widget_carrier_id, carrier_pickup_point_id) {
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
    packeteryCreateExtraContent: function(prestashop_carrier_id) {
        return $.ajax({
            type: 'POST',
            url: ajaxs.baseuri() + '/modules/packetery/ajax_front.php?action=packeteryCreateExtraContent' + ajaxs.checkToken(),
            data: {
                'prestashop_carrier_id': prestashop_carrier_id,
            },
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
    }
}

ajaxs = {
    baseuri: function() {
        return $('#baseuri').val();
    },
    checkToken: function() {
        return '&token=' + PacketaModule.config.frontAjaxToken;
    },
}

/**
 *  After document load or actions (new shipping methods load) of 3rd party checkouts to allow packeta initialization. E.g.: Supercheckout.
 */
function onShippingLoadedCallback() {
    if ($('#zpoint_carriers').length === 0) {
        return; // incorrect context
    }

    if (tools.isPS16()) {
        packeteryCreateExtraContent(function() {
            initializePacketaWidget();
            tools.fixextracontent();
        });
    } else {
        initializePacketaWidget();
        tools.fixextracontent();
    }
}

PacketaModule.runner.onThisScriptLoad();