// naming convention: PacketerCheckoutModule + moduleId (first letter in upper case)

var PacketeryCheckoutModuleOpcZelarg = {

    submitButtonSelector: '.button-continue .confirm_button',

    submitButtonBackground: 'linear-gradient(to bottom, rgba(105,211,88,1) 0%,rgba(35,127,25,1) 100%)',

    isActive: function () {
        var isCorrectVersion = PacketaModule.tools.isPS16();
        var $button = $(this.submitButtonSelector);
        if ($button.length === 1) {
            this.submitButtonBackground = $button.css('background');
        }
        return isCorrectVersion && this.findDeliveryOptions().length > 0 && $('#opc_checkout').length > 0;
    },

    getSelectedInput: function () {
        return $('.delivery_option input:checked');
    },

    findDeliveryOptions: function () {
        return $('.delivery_option input');
    },

    enableSubmitButton: function () {
        var $button = $(this.submitButtonSelector);
        $button.css('background', this.submitButtonBackground);
        $button.css('cursor', 'pointer');
        $button.prop('disabled', false);
    },

    disableSubmitButton: function () {
        var $button = $(this.submitButtonSelector);
        $button.css('background', 'gray');
        $button.css('cursor', 'default');
        $button.prop('disabled', true);
    },

    hideValidationErrors: function () {
    },

    getExtraContentContainer: function ($selectedInput) {
        return $selectedInput.parent();
    },

};
