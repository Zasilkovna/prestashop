/**
 * @copyright 2017-2026 Packeta s.r.o.
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

var PacketeryCheckoutModuleSupercheckout = {

    isActive: function () {
        return this.findDeliveryOptions().length > 0;
    },

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

    // used in PS 1.6 version
    getExtraContentContainer: function ($selectedInput) {
        return $selectedInput.closest('li');
    },

    getExtraContentSelector: function () {
        return '.kbshippingparceloption';
    },

    toggleExtracContent: true
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
                if (PacketaModule.ui.isAddressValidationUnsatisfied($widgetParent, $selectedInput)) {
                    throw {message: PacketaModule.config.addressNotValidatedMessage};
                }
            }
        });
    }
});
