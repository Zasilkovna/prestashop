// naming convention: PacketerCheckoutModule + moduleId (first letter in upper case)

var PacketeryCheckoutModuleSupercheckout = {

    getSelectedInput: function () {
        return $('#shipping-method input:checked');
    },

    findDeliveryOptions: function () {
        return $('#shipping-method input');
    },

    // we're not able to enable/disable Supercheckout submit button, we register our own validator instead
    enableSubmitButton: function () {},
    disableSubmitButton: function () {},

    hideValidationErrors: function () {
        hideGeneralError();
    },

    getExtraContentSelector: function () {
        return '.kbshippingparceloption';
    }
};

$(function () {
    if (typeof addSupercheckoutOrderValidator !== 'undefined') {
        addSupercheckoutOrderValidator(function () {
            var $selectedInput = PacketeryCheckoutModuleSupercheckout.getSelectedInput(),
                $widgetParent = packeteryModulesManager.getWidgetParent($selectedInput);

            if ($widgetParent.length === 1) {
                if (PacketaModule.ui.isPickupPointInvalid($widgetParent)) {
                    throw {message: $('.packetery-message-pickup-point-not-selected-error').data('content')};
                }
                if (PacketaModule.ui.isAddressValidationUnsatisfied($widgetParent)) {
                    throw {message: $('.packetery-address-not-validated-message').data('content')};
                }
            }
        });
    }
});
