// naming convention: PacketerCheckoutModule + moduleId (first letter in upper case)

// this unknown module support already existed in front.js
var PacketeryCheckoutModuleUnknown = {

    deliveryInputSelector: '.delivery_option input',

    getSelectedInput: function () {
        return $('.delivery_option.selected input');
    },

    findDeliveryOptions: function () {
        return $(this.deliveryInputSelector);
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

    hideValidationErrors: function () {}
};
