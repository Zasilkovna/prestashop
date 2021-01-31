// non-blocking AJAX loading, speeds up page load
$.getScript("https://widget.packeta.com/www/js/library.js")
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
}
var packeteryModulesManager = new PacketeryCheckoutModulesManager();


$(document).ready(function ()
{
    if ($(".zas-box").length) {
        onShippingLoadedCallback();
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

    var allowedCountries = JSON.parse($('#allowed_countries').val());

    // parameters

    var customerCountry = $('#customer_country').val();
    if (customerCountry !== '' && allowedCountries.indexOf(customerCountry) !== -1) {
        country = customerCountry;
    }

    /* Overwrite default countries with forced country or customer country */
    var forceCountry = $('#widget_force_country').val();
    if (forceCountry !== "" && allowedCountries.indexOf(forceCountry) !== -1) {
        country = forceCountry;
    }


    var language = 'en';

    var shopLanguage = $('#shop-language').val();
    if (shopLanguage !== '') {
        language = shopLanguage;
    }

    /* Override language with forced language if it's set */
    var forceLanguage = $('#widget_force_language').val();
    if (forceLanguage !== "") {
        language = forceLanguage;
    }

    $('.open-packeta-widget').click(function (e) {
        e.preventDefault();
        var module_version = $('#module_version').val(); // Get module version for widget
        Packeta.Widget.pick(packetaApiKey, function (pickupPoint)
        {
            var
              module = packeteryModulesManager.detectModule(),
              $selectedDeliveryOption = module.getSelectedInput(),
              $widgetParent = module.getWidgetParent($selectedDeliveryOption);

            if (pickupPoint != null)
            {
                /* Add ID and name to inputs */
                $widgetParent.find('.packeta-branch-id').val(pickupPoint.id);
                $widgetParent.find('.packeta-branch-name').val(pickupPoint.name);

                // We let customer know, which branch he picked by filling html inputs
                $widgetParent.find('.picked-delivery-place').html(pickupPoint.name);

                module.enableSubmitButton();

                /* Get ID of selected carrier */
                var id_carrier = packeteryModulesManager.getCarrierId($selectedDeliveryOption);

                /* Save packetery order without order ID - just cart id so we can access carrier data later */
                packetery.widgetSaveOrderBranch(pickupPoint.id, id_carrier, pickupPoint.name);

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
        }, {appIdentity: 'prestashop-1.7-packeta-' + module_version, country: country, language: language});
    });
};

tools = {
    fixextracontent: function (country)
    {
        var module = packeteryModulesManager.detectModule();

        if (module === null) {
            return;
        }

        $('.carrier-extra-content').each(function ()
        {
            var $extra = $(this);
            if (! $extra.find('#packetery-widget').length) {
                return;
            }

            var widget_carrier = $extra.find('#widget_carrier').val();
            var carrierCountries = $extra.find('#carrier_countries').val();

            carrierCountries = JSON.parse(carrierCountries);
            if (carrierCountries[widget_carrier].indexOf(country) === -1) {
                $extra.find('#open-packeta-widget').hide();
                $extra.find('#selected-branch').hide();
                $extra.find('#invalid-country-carrier').show();
            }

            /* Only displayed extra content */
            if ($extra.css('display') === 'block') {
                /* And branch is not set, disable */
                var id_branch = $extra.find(".packeta-branch-id").val();
                if (id_branch <= 0) {
                    module.disableSubmitButton();
                }
            }
        });

        /* Enable / Disable continue buttons after carrier change */

        var $deliveryInputs = module.findDeliveryOptions();
        $deliveryInputs.change(function ()
        {
            var
                $this = $(this),
                id_carrier = packeteryModulesManager.getCarrierId($this)
                $extra = module.getWidgetParent($this);

            // if selected carrier is not Packetery then enable Continue button and we're done here
            if (! $extra.find('#packetery-widget').length) {
                module.enableSubmitButton();
                return;
            }

            var id_branch = $extra.find(".packeta-branch-id").val();
            if (id_branch > 0) {
                var name_branch = $extra.find(".packeta-branch-name").val();
                module.enableSubmitButton();
                packetery.widgetSaveOrderBranch(id_branch, id_carrier, name_branch);
            } else {
                module.disableSubmitButton();
            }
        });
    }
}

packetery = {
    widgetSaveOrderBranch: function (id_branch, id_carrier, name_branch)
    {
        $.ajax({
            type: 'POST',
            url: ajaxs.baseuri() + '/modules/packetery/ajax_front.php?action=widgetsaveorderbranch' + ajaxs.checkToken(),
            data: {'id_branch': id_branch, 'id_carrier': id_carrier, 'name_branch': name_branch},
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
        return '&token=' + prestashop.static_token;
    },
}


function onShippingLoadedCallback() {
    initializePacketaWidget();
    tools.fixextracontent(country);
}
