// naming convention: PacketerCheckoutModule + moduleId (first letter in upper case)

var PacketeryCheckoutModuleOpcZelarg = {

    submitButtonBackground: null,

    isActive: function () {
        var isCorrectVersion = PacketaModule.tools.isPS16();
        return isCorrectVersion && this.findDeliveryOptions().length > 0;
    },

    getSubmitButton: function () {
        return $('.button-continue .confirm_button');
    },

    getSelectedInput: function () {
        return $('.delivery_option input:checked');
    },

    findDeliveryOptions: function () {
        return $('form#carriers_section .delivery_option input');
    },

    enableSubmitButton: function () {
        var $button = this.getSubmitButton();
        if ($button.prop('disabled') !== true) {
            return;
        }
        $button.css('background', this.submitButtonBackground);
        $button.css('cursor', 'pointer');
        $button.prop('disabled', false);
    },

    disableSubmitButton: function () {
        var $button = this.getSubmitButton();
        if ($button.prop('disabled') !== false) {
            return;
        }
        this.submitButtonBackground = $button.css('background');
        $button.css('background', 'gray');
        $button.css('cursor', 'default');
        $button.prop('disabled', true);
    },

    hideValidationErrors: function () {
    },

    getExtraContentContainer: function ($selectedInput) {
        return $selectedInput.parent();
    },

    getExtraContentSelector: function () {
        return '.carrier-extra-content';
    }
};
