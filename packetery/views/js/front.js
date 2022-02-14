PacketaModule = window.PacketaModule || {};

PacketaModule.tools = {
    isPS16: function () {
        return PacketaModule.config.prestashopVersion.indexOf('1.6') === 0;
    },
};

PacketaModule.runner = {
    /**
     * Supposed to be called only once
     */
    onThisScriptLoad: function () {
        // non-blocking AJAX loading, speeds up page load
        var dependencies = [];
        dependencies.push($.getScript('https://widget.packeta.com/v6/www/js/library.js'));
        dependencies.push($.getScript('https://widget-hd.packeta.com/www/js/library-hd.js'));

        $.when.apply(null, dependencies).done(function () {
            PacketaModule.runner.onWidgetLoad();
        });
    },

    /**
     * Supposed to be called only once
     */
    onWidgetLoad: function () {
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
     * May be called more than once in a lifetime of this script, when shipping methods are updated via AJAX
     */
    onShippingLoad: function () {
        if (PacketaModule.tools.isPS16()) {
            PacketaModule.ui.addAllExtraContents(PacketaModule.runner.onExtraContentLoad);
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
        PacketaModule.ui.autoOpenWidget();

        PacketaModule.ui.toggleSubmit();
        PacketaModule.ui.toggleExtraContent();

        var $deliveryInputs = module.findDeliveryOptions();
        $deliveryInputs.off('change.packetery').on('change.packetery', function () {
            PacketaModule.runner.onShippingChange($(this));
        });
    },

    onShippingChange: function ($selectedInput) {
        var module = packeteryModulesManager.detectModule();
        if (module === null) {
            return;
        }

        // just in case this script dies, or an AJAX round trip delay would allow customer to continue without selecting a branch first
        module.disableSubmitButton();

        if (PacketaModule.config.toggleExtraContentOnShippingChange) {
            PacketaModule.ui.autoOpenWidget();
            PacketaModule.ui.toggleExtraContent();
        }

        var $widgetParent = packeteryModulesManager.getWidgetParent($selectedInput);

        // if selected carrier is not Packeta then enable Continue button and we're done here
        if (!$widgetParent.length) {
            module.enableSubmitButton();
            return;
        }

        var prestashopCarrierId = packeteryModulesManager.getCarrierId($selectedInput);
        var validatedAddress = PacketaModule.storage.getAddress(prestashopCarrierId);
        if (PacketaModule.ui.isPickupPointValid($widgetParent)) {
            var branchId = $widgetParent.find(".packeta-branch-id").val();
            var branchName = $widgetParent.find(".packeta-branch-name").val();
            var branchCurrency = $widgetParent.find(".packeta-branch-currency").val();
            var pickupPointType = $widgetParent.find(".packeta-pickup-point-type").val();
            var widgetCarrierId = $widgetParent.find(".packeta-carrier-id").val();
            var carrierPickupPointId = $widgetParent.find(".packeta-carrier-pickup-point-id").val();
            if (branchId) {
                PacketaModule.ajax.savePickupPointInCart(
                    prestashopCarrierId,
                    branchId,
                    branchName,
                    pickupPointType,
                    widgetCarrierId,
                    carrierPickupPointId,
                    branchCurrency,
                    PacketaModule.ui.toggleSubmit
                );
            } else {
                PacketaModule.ui.toggleSubmit();
            }
        } else if (PacketaModule.ui.isAddressValidationSatisfied($widgetParent, $selectedInput) && validatedAddress) {
            PacketaModule.ajax.saveAddressInCart(validatedAddress, PacketaModule.ui.toggleSubmit)
        } else {
            PacketaModule.ui.toggleSubmit();
        }
    },

    /**
     * Called in two scenarios:
     * - during initial load - at this point, delivery methods have not been downloaded yet and module detection probably fails
     * - on AJAX update of delivery methods
     */
    onBeforeCarrierLoad: function () {
        var module = packeteryModulesManager.detectModule();
        if (module === null) {
            return;
        }

        if (module.findDeliveryOptions().length !== 0) {
            PacketaModule.runner.onShippingLoad();
        }
    }
}

PacketaModule.ui = {
    toggleExtraContent: function () {
        var module = packeteryModulesManager.detectModule();
        if (module === null) {
            return;
        }

        // if template doesn't handle showing carrier-extra-content then we have to
        var toggleExtraContent = PacketaModule.config.toggleExtraContent;
        if (typeof module.toggleExtraContent !== 'undefined') {
            toggleExtraContent = module.toggleExtraContent;
        }
        if (!toggleExtraContent && !PacketaModule.tools.isPS16()) {
            return;
        }

        // hide it for all carriers - if they happen to have it
        $(module.getExtraContentSelector()).hide();

        // show it only for Packeta carriers (easier to do because we have id="packetery-carrier-{$carrier_id}")
        var $selectedInput = module.getSelectedInput();
        if ($selectedInput.length === 0) {
            return;
        }
        packeteryModulesManager.getWidgetParent($selectedInput)
            .closest(module.getExtraContentSelector())
            .show();
    },

    extraContentCache: {},

    /**
     * May be called multiple times, even in a very short time, especially in Supercheckout PS 1.6
     * @see display-before-carrier.tpl
     */
    addAllExtraContents: function (onExtraContentLoad) {
        var module = packeteryModulesManager.detectModule();
        if (module === null) {
            return;
        }

        var $deliveryOptions = module.findDeliveryOptions();
        var ajaxCalls = [],
            loadedFromCache = false;

        $deliveryOptions.each(function (i, e) {
            var $deliveryInput = $(e);
            var carrierId = packeteryModulesManager.getCarrierId($deliveryInput);

            var isCarrierWithDeliveryPoints = PacketaModule.config.deliveryPointCarrierIds.indexOf(carrierId) >= 0;
            if (!isCarrierWithDeliveryPoints) {
                return;
            }

            if (typeof PacketaModule.ui.extraContentCache[carrierId] !== 'undefined') {
                if (PacketaModule.ui.extraContentCache[carrierId] !== 'pending') {
                    PacketaModule.ui.addOneExtraContent($deliveryInput, PacketaModule.ui.extraContentCache[carrierId]);
                    loadedFromCache = true;
                }
                return;
            }

            PacketaModule.ui.extraContentCache[carrierId] = 'pending';

            var ajaxCall = PacketaModule.ajax.fetchExtraContent(carrierId).done(function (result) {
                PacketaModule.ui.addOneExtraContent($deliveryInput, result);
                PacketaModule.ui.extraContentCache[carrierId] = result;
            });
            ajaxCalls.push(ajaxCall);
        });

        if (ajaxCalls.length > 0) {
            $.when.apply(null, ajaxCalls).then(onExtraContentLoad);
        }

        if (loadedFromCache) {
            onExtraContentLoad();
        }
    },

    addOneExtraContent: function ($deliveryInput, html) {
        var isAlreadyThere = packeteryModulesManager.getWidgetParent($deliveryInput).length > 0;
        if (isAlreadyThere) {
            return;
        }

        var module = packeteryModulesManager.detectModule();
        if (module === null) {
            return;
        }

        module.getExtraContentContainer($deliveryInput).append(html);
    },

    toggleSubmit: function () {
        var module = packeteryModulesManager.detectModule();
        if (module === null) {
            return;
        }

        var $selectedInput = module.getSelectedInput();
        if ($selectedInput.length === 0) {
            return;
        }
        var $widgetParent = packeteryModulesManager.getWidgetParent($selectedInput);
        if (
            PacketaModule.ui.isPickupPointInvalid($widgetParent) ||
            PacketaModule.ui.isAddressValidationUnsatisfied($widgetParent, $selectedInput)
        ) {
            module.disableSubmitButton();
        } else {
            module.enableSubmitButton();
            module.hideValidationErrors();
        }
    },

    initializeWidget: function () {
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
            var $selectedInput = module.getSelectedInput();
            if ($selectedInput.length === 0) {
                // in supercheckout after switching country and no delivery is selected
                return;
            }
            var $widgetParent = packeteryModulesManager.getWidgetParent($selectedInput);
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
                $widgetParent.find('.packeta-branch-currency').val(pickupPoint.currency);
                $widgetParent.find('.packeta-pickup-point-type').val(pickupPoint.pickupPointType);
                $widgetParent.find('.packeta-carrier-id').val(pickupPoint.carrierId);
                $widgetParent.find('.packeta-carrier-pickup-point-id').val(pickupPoint.carrierPickupPointId);

                // let the customer know which branch he picked
                $widgetParent.find('.picked-delivery-place').html(pickupPoint.name);

                var prestashopCarrierId = packeteryModulesManager.getCarrierId($selectedInput);

                /* Save packetery order without order ID - just cart id so we can access carrier data later */
                PacketaModule.ajax.savePickupPointInCart(
                    prestashopCarrierId,
                    pickupPoint.id,
                    pickupPoint.name,
                    pickupPoint.pickupPointType,
                    pickupPoint.carrierId,
                    pickupPoint.carrierPickupPointId,
                    pickupPoint.currency,
                    function (jsonResponse) {
                        if (jsonResponse.result === true) {
                            PacketaModule.ui.toggleSubmit();
                        } else {
                            console.error(jsonResponse.message);
                        }
                    }
                );
            }, widgetOptions);
        });

        $('.open-packeta-widget-hd').click(function (e) {
            e.preventDefault();
            var $selectedInput = module.getSelectedInput();
            if ($selectedInput.length === 0) {
                return;
            }
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
                carrierId: widgetCarriers
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
            PacketaHD.Widget.pick(PacketaModule.config.apiKey, function (result) {
                if (result != null && result.address != null) {
                    // there is also property packetaWidgetMessage which is true
                    var address = result.address;
                    var $addressValidationResult = $widgetParent.find('.address-validation-result');
                    if (address.country === country) {
                        PacketaModule.ajax.saveAddressInCart(address);
                        var prestashopCarrierId = packeteryModulesManager.getCarrierId($selectedInput);
                        PacketaModule.storage.setAddress(address, prestashopCarrierId)
                        $widgetParent.find('#addressValidated').val(true);
                        $addressValidationResult.addClass('address-validated');
                        $addressValidationResult.text(PacketaModule.config.addressValidatedMessage);
                        $widgetParent.find('.picked-delivery-place').html(
                            address.street + ' ' + address.houseNumber + ', ' + address.city + ', ' + address.postcode
                        );
                        PacketaModule.ui.toggleSubmit();
                    } else {
                        $widgetParent.find('#addressValidated').val(false);
                        $addressValidationResult.removeClass('address-validated');
                        $addressValidationResult.text(PacketaModule.config.countryDiffersMessage);
                        PacketaModule.ui.toggleSubmit();
                    }
                }
            }, widgetOptions);
        });
    },

    autoOpenWidget: function () {
        if (!PacketaModule.config.widgetAutoOpen) {
            return;
        }

        var module = packeteryModulesManager.detectModule();
        if (module === null) {
            return;
        }

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
    },

    isHdCarrier: function ($widgetParent) {
        return !!$widgetParent.find('#open-packeta-widget-hd').length;
    },

    isPpCarrier: function ($widgetParent) {
        return !!$widgetParent.find('#open-packeta-widget').length;
    },

    isPickupPointValid: function ($widgetParent) {
        var selectedBranchId = $widgetParent.find('.packeta-branch-id').val();
        return this.isPpCarrier($widgetParent) && selectedBranchId;
    },

    isPickupPointInvalid: function ($widgetParent) {
        var selectedBranchId = $widgetParent.find('.packeta-branch-id').val();
        return this.isPpCarrier($widgetParent) && !selectedBranchId;
    },

    isAddressValidationSatisfied: function ($widgetParent, $selectedInput) {
        var prestashopCarrierId = packeteryModulesManager.getCarrierId($selectedInput);
        var addressValidationSetting = PacketaModule.config.addressValidationLevels[prestashopCarrierId];
        var addressValidated = PacketaModule.ui.makeBoolean($widgetParent.find('#addressValidated').val());
        return (addressValidationSetting === 'required' || addressValidationSetting === 'optional') && addressValidated;
    },

    isAddressValidationUnsatisfied: function ($widgetParent, $selectedInput) {
        var prestashopCarrierId = packeteryModulesManager.getCarrierId($selectedInput);
        var addressValidationSetting = PacketaModule.config.addressValidationLevels[prestashopCarrierId];
        var addressValidated = PacketaModule.ui.makeBoolean($widgetParent.find('#addressValidated').val());
        return (this.isHdCarrier($widgetParent) && addressValidationSetting === 'required' && !addressValidated);
    },

    makeBoolean: function (value) {
        if (value === 'false') {
            return false;
        }
        return !!value;
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
            beforeSend: function () {
                // todo: To which checkout module does this css class belong? Not Supercheckout PS 1.7, not PS 1.7, not PS 1.6 5-step nor OPC
                $("body").toggleClass("wait");
            },
            success: function (response) {
                if (typeof onSuccess !== 'undefined') {
                    onSuccess(response);
                }
            },
            complete: function () {
                $("body").toggleClass("wait");
            },
        });
    },

    savePickupPointInCart: function (prestashopCarrierId, branchId, branchName, pickupPointType, widgetCarrierId, carrierPickupPointId, branchCurrency, onSuccess) {
        return PacketaModule.ajax.post('savePickupPointInCart', {
            'prestashop_carrier_id': prestashopCarrierId,
            'id_branch': branchId,
            'name_branch': branchName,
            'currency_branch': branchCurrency,
            'pickup_point_type': pickupPointType,
            'widget_carrier_id': widgetCarrierId,
            'carrier_pickup_point_id': carrierPickupPointId
        }, onSuccess);
    },

    saveAddressInCart: function (address, onSuccess) {
        return PacketaModule.ajax.post('saveAddressInCart', {'address': address}, onSuccess);
    },

    fetchExtraContent: function (prestashopCarrierId) {
        return PacketaModule.ajax.post('fetchExtraContent', {
            'prestashop_carrier_id': prestashopCarrierId,
        });
    }
};

PacketaModule.storage = {
    setAddress: function (address, carrierId) {
        this.addresses[carrierId] = address;
    },

    getAddress: function (carrierId) {
        if (typeof this.addresses[carrierId] === 'undefined') {
            return null;
        }
        return this.addresses[carrierId];
    },

    addresses: {}
};

function PacketeryCheckoutModulesManager()
{
    // ids correspond to parts of class names in checkout-module/*.js - first letter in upper case
    // order is important because of false positives (Unknown has to be last)
    this.supportedModules = ['Ps16', 'Ps17', 'Supercheckout', 'OpcZelarg', 'Unknown'];
    this.loadedModules = [];
    this.detectedModule = null;

    this.loadModules = function () {
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
        return $selectedInput.val().replace(',', '');
    }

    this.getWidgetParent = function ($selectedInput) {
        return $('#packetery-carrier-' + this.getCarrierId($selectedInput));
    }
}

var packeteryModulesManager = new PacketeryCheckoutModulesManager();


/**
 *  This function is called by third party checkout modules (e.g. Supercheckout) after shipping methods are fetched via AJAX
 */
function onShippingLoadedCallback()
{
    PacketaModule.runner.onShippingLoad();
}

PacketaModule.runner.onThisScriptLoad();
