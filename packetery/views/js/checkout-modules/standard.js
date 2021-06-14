// naming convention: PacketerCheckoutModule + moduleId (first letter in upper case)

var PacketeryCheckoutModuleStandard = {

    deliveryInputSelector: '.delivery-option input',

    getSelectedInput: function () {
        return $('.delivery-option input:checked');
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

    hideValidationErrors: function () {
    }
};
