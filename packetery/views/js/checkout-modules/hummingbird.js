// naming convention: PacketerCheckoutModule + moduleId (first letter in upper case)

var PacketeryCheckoutModuleHummingbird = {

    isActive: function () {
        var isSupportedVersion = PacketaModule.tools.isPS8() || PacketaModule.tools.isPS9();
        return isSupportedVersion && this.findDeliveryOptions().length > 0;
    },

    getSelectedInput: function () {
        return $('.js-delivery-option input[id^=delivery_option]:checked');
    },

    findDeliveryOptions: function () {
        return $('.js-delivery-option input[id^=delivery_option]');
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
};
