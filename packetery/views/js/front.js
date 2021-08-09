PacketaModule = window.PacketaModule || {};

PacketaModule.tools = {
    isPS16: function() {
        return PacketaModule.config.prestashopVersion && PacketaModule.config.prestashopVersion.indexOf('1.6') === 0;
    },
};

PacketaModule.runner = {
    /**
     * Supposed to be called only once
     */
    onThisScriptLoad: function() {
        // non-blocking AJAX loading, speeds up page load
        $.getScript("https://widget.packeta.com/v6/www/js/library.js")
            .success(PacketaModule.runner.onWidgetLoad)
            .fail(function() {
                console.error('Unable to load Packeta Widget.');
            });
    },

    /**
     * Supposed to be called only once
     */
    onWidgetLoad: function() {
        // register on document load callback after widget is loaded
        $(PacketaModule.runner.onDocumentLoad);
    },

    /**
     * Supposed to be called only once
     */
    onDocumentLoad: function () {
        if (typeof PacketaModule.config === 'undefined') {
            return; // this script is not loaded on a page with a selection of shipping methods
        }

        PacketaModule.runner.onShippingLoad();
    },

    /**
     * May be called more than once in a lifetime of this script
     */
    onShippingLoad: function () {
        if (PacketaModule.tools.isPS16()) {
            PacketaModule.ui.addExtraContent(PacketaModule.runner.onExtraContentLoad);
        } else {
            PacketaModule.runner.onExtraContentLoad();
        }
    },

    onExtraContentLoad: function () {
        var module = packeteryModulesManager.detectModule();
        if (module === null) {
            return;
        }

        PacketaModule.ui.initializeWidget();
        PacketaModule.ui.handleAutoOpenWidget();

        PacketaModule.ui.toggleSubmit();
        PacketaModule.ui.toggleExtraContent();

        var $deliveryInputs = module.findDeliveryOptions();
        $deliveryInputs.off('change.packetery').on('change.packetery', function() {
            PacketaModule.runner.onShippingChange($(this));
        });

    },

    onShippingChange($selectedInput) {
        var module = packeteryModulesManager.detectModule();
        if (module === null) {
            return;
        }

        // just in case this script dies or an AJAX round trip delay would allow customer to continue without selecting a branch first
        module.disableSubmitButton();

        // PS 1.6 OPC re-creates the list of shipping methods, throwing out extra content in the process.
        // PS 1.6 5-steps checkout doesn't do that

        // todo: distinguish 5-steps to toggle visibility here, for OPC toggle in display-before-carrier via onShippingLoaded...

        PacketaModule.ui.toggleExtraContent();

        var $extra = packeteryModulesManager.getWidgetParent($selectedInput);

        // if selected carrier is not Packeta then enable Continue button and we're done here
        if (!$extra.length) {
            module.enableSubmitButton();
            return;
        }

        var branchId = $extra.find(".packeta-branch-id").val();
        if (branchId !== '') {
            var prestashopCarrierId = packeteryModulesManager.getCarrierId($selectedInput);
            var branchName = $extra.find(".packeta-branch-name").val();
            var pickupPointType = $extra.find(".packeta-pickup-point-type").val();
            var widgetCarrierId = $extra.find(".packeta-carrier-id").val();
            var carrierPickupPointId = $extra.find(".packeta-carrier-pickup-point-id").val();

            PacketaModule.ajax.saveSelectedBranch(
                prestashopCarrierId,
                branchId,
                branchName,
                pickupPointType,
                widgetCarrierId,
                carrierPickupPointId,
                PacketaModule.ui.toggleSubmit
            );
        } else {
            PacketaModule.ui.toggleSubmit();
        }
    },

    onBeforeCarrierLoad() {
        PacketaModule.runner.onShippingLoad(); // stub, tohle je právě potřeba vyřešit
    }
}

PacketaModule.ui = {
    toggleExtraContent: function () {
        // if template doesn't handle showing carrier-extra-content then we have to
        if ((! PacketaModule.config.toggleExtraContent) && (! PacketaModule.tools.isPS16())) {
            return;
        }

        var module = packeteryModulesManager.detectModule();
        if (module === null) {
            return;
        }

        // hide it for all carriers - if they happen to have it
        $('.carrier-extra-content').hide();

        // show it only for Packeta carriers (easier to do because we have id="packetery-carrier-{$carrier_id}")
        packeteryModulesManager.getWidgetParent(module.getSelectedInput())
            .closest('.carrier-extra-content')
            .show();
    },

    extraContentThrottling: {},

    /**
     * May be called multiple times, even in a very short time
     * @see display-before-carrier.tpl
     * @param onExtraContentLoad
     */
    addExtraContent: function (onExtraContentLoad) {
        var module = packeteryModulesManager.detectModule();
        if (module === null) {
            return;
        }

        var $deliveryOptions = module.findDeliveryOptions();

        var ajaxCalls = [];
        $deliveryOptions.each(function(i, e) {
            var $deliveryInput = $(e);
            var carrierId = packeteryModulesManager.getCarrierId($deliveryInput);

            if (typeof PacketaModule.ui.extraContentThrottling[carrierId] !== 'undefined') {
                return;
            }
            PacketaModule.ui.extraContentThrottling[carrierId] = true;

            var isCarrierWithDeliveryPoints = PacketaModule.config.deliveryPointCarrierIds.indexOf(carrierId) >= 0;
            if (!isCarrierWithDeliveryPoints) {
                return;
            }

            var $extraContentContainer = module.getExtraContentContainer($deliveryInput);

            var ajaxCall = PacketaModule.ajax.fetchExtraContent(carrierId).done(function(result) {
                $extraContentContainer.append(result);
                delete PacketaModule.ui.extraContentThrottling[carrierId];
            });
            ajaxCalls.push(ajaxCall);
        });

        if (ajaxCalls.length > 0) {
            $.when.apply(null, ajaxCalls).then(onExtraContentLoad);
        }
    },

    toggleSubmit() {
        var module = packeteryModulesManager.detectModule();
        if (module === null) {
            return;
        }

        var $widgetParent = packeteryModulesManager.getWidgetParent(module.getSelectedInput());
        var branchId = $widgetParent.find(".packeta-branch-id").val();
        if (branchId !== '') {
            module.enableSubmitButton();
            module.hideValidationErrors();
        } else {
            module.disableSubmitButton();
        }
    },

    initializeWidget: function() {
        if (PacketaModule.config.apiKey === '') {
            return;
        }

        var module = packeteryModulesManager.detectModule();
        if (module === null) {
            return;
        }

        var country = 'cz,sk';
        if (PacketaModule.config.customerCountry !== '') {
            country = PacketaModule.config.customerCountry;
        }

        var language = 'en';
        if (PacketaModule.config.shopLanguage !== '') {
            language = PacketaModule.config.shopLanguage;
        }

        $('.open-packeta-widget').click(function (e) {
            e.preventDefault();
            var widgetOptions = {
                appIdentity: PacketaModule.config.appIdentity,
                country: country,
                language: language,
            };
            var selectedInput = module.getSelectedInput();
            if (selectedInput.length === 0) {
                // in supercheckout after switching country and no delivery is selected
                return;
            }
            var $widgetParent = packeteryModulesManager.getWidgetParent(selectedInput);
            var widgetCarriers = $widgetParent.find('#widget_carriers').val();
            if (widgetCarriers !== '') {
                widgetOptions.carriers = widgetCarriers;
            }
            Packeta.Widget.pick(PacketaModule.config.apiKey, function (pickupPoint) {
                if (pickupPoint == null) {
                    return;
                }

                $widgetParent.find('.packeta-branch-id').val(pickupPoint.id);
                $widgetParent.find('.packeta-branch-name').val(pickupPoint.name);
                $widgetParent.find('.packeta-pickup-point-type').val(pickupPoint.pickupPointType);
                $widgetParent.find('.packeta-carrier-id').val(pickupPoint.carrierId);
                $widgetParent.find('.packeta-carrier-pickup-point-id').val(pickupPoint.carrierPickupPointId);

                // let the customer know which branch he picked
                $widgetParent.find('.picked-delivery-place').html(pickupPoint.name);

                var prestashopCarrierId = packeteryModulesManager.getCarrierId(selectedInput);

                /* Save packetery order without order ID - just cart id so we can access carrier data later */
                PacketaModule.ajax.saveSelectedBranch(
                    prestashopCarrierId,
                    pickupPoint.id,
                    pickupPoint.name,
                    pickupPoint.pickupPointType,
                    pickupPoint.carrierId,
                    pickupPoint.carrierPickupPointId,
                   PacketaModule.ui.toggleSubmit
                );
            }, widgetOptions);
        });
    },

    handleAutoOpenWidget: function () {
        if (!PacketaModule.config.widgetAutoOpen) {
            return;
        }

        var module = packeteryModulesManager.detectModule();
        if (module === null) {
            return;
        }

        var openWidget = function () {
            var $selectedDeliveryOption = module.getSelectedInput();
            if ($selectedDeliveryOption.length !== 1) {
                return;
            }

            var $widgetParent = packeteryModulesManager.getWidgetParent($selectedDeliveryOption);
            var $widgetButton = $widgetParent.find('.open-packeta-widget');
            if (
                $widgetButton.length === 1 &&
                $widgetParent.find('.packeta-branch-id').val() === ''
                // todo PePa: how could we reach this point with widget already open?
                // &&
                // $('iframe #packeta-widget').length === 0
            ) {
                $widgetButton.click();
            }
        };
        module.findDeliveryOptions().on('change', openWidget);
        openWidget();
    }
};

PacketaModule.ajax = {
    post: function (action, data, onSuccess) {
        var url =
            PacketaModule.config.baseUri +
            '/modules/packetery/ajax_front.php?action=' + action +
            '&token=' + PacketaModule.config.frontAjaxToken;

        return $.ajax({
            type: 'POST',
            url: url,
            data: data,
            beforeSend: function() {
                $("body").toggleClass("wait");
            },
            success: function () {
                if (typeof onSuccess !== 'undefined') {
                    onSuccess();
                }
            },
            complete: function() {
                $("body").toggleClass("wait");
            },
        });
    },

    saveSelectedBranch: function(prestashopCarrierId, branchId, branchName, pickupPointType, widgetCarrierId, carrierPickupPointId, onSuccess) {
        return PacketaModule.ajax.post('saveSelectedBranch', {
            'prestashop_carrier_id': prestashopCarrierId,
            'id_branch': branchId,
            'name_branch': branchName,
            'pickup_point_type': pickupPointType,
            'widget_carrier_id': widgetCarrierId,
            'carrier_pickup_point_id': carrierPickupPointId
        }, onSuccess);
    },

    fetchExtraContent: function(prestashopCarrierId) {
        return PacketaModule.ajax.post('fetchExtraContent', {
            'prestashop_carrier_id': prestashopCarrierId,
        });
    }
};


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


/**
 *  This function is called by third party checkout modules (e.g. Supercheckout) after shipping methods are fetched via AJAX
 */
function onShippingLoadedCallback() {
    PacketaModule.runner.onShippingLoad();
}

PacketaModule.runner.onThisScriptLoad();