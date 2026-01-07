/**
 * @copyright 2017-2026 Packeta s.r.o.
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

// naming convention: PacketerCheckoutModule + moduleId (first letter in upper case)

// todo remove? Untestable.
// this unknown module support already existed in front.js
var PacketeryCheckoutModuleUnknown = {

    isActive: function () {
        return this.findDeliveryOptions().length > 0;
    },

    getSelectedInput: function () {
        return $('.delivery_option.selected input');
    },

    findDeliveryOptions: function () {
        return $('.delivery_option input');
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

    hideValidationErrors: function () {},

    getExtraContentContainer: function () {
        return $('.delivery_option');
    },

    getExtraContentSelector: function () {
        return '.carrier-extra-content';
    }
};
