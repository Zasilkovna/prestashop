/**
 * @copyright 2017-2026 Packeta s.r.o.
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

var PacketeryCheckoutModulePs17 = {

    isActive: function () {
        return this.findDeliveryOptions().length > 0;
    },

    getSelectedInput: function () {
        return $('.delivery-option input:checked');
    },

    findDeliveryOptions: function () {
        return $('.delivery-option input');
    },

    enableSubmitButton: function () {
        $('button[name="confirmDeliveryOption"]')
            .removeClass('disabled')
            .css("pointer-events", "auto");
    },

    disableSubmitButton: function () {
        $('button[name="confirmDeliveryOption"]')
            .addClass('disabled')
            .css("pointer-events", "none");
    },

    hideValidationErrors: function () {
    },

    getExtraContentSelector: function () {
        return '.carrier-extra-content';
    }
};
