// naming convention: PacketerCheckoutModule + moduleId (first letter in upper case)

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
                $extra = packeteryModulesManager.getWidgetParent($selectedInput),
                selectedBranchId = $extra.find('.packeta-branch-id').val();

            if ($extra.length === 1 && !selectedBranchId) {
                var error_text = $('.packetery-message-pickup-point-not-selected-error').data('content');
                throw { message: error_text };
            }
        });
    }
});
