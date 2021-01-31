// naming convention: PacketerCheckoutModule + moduleId (first letter in upper case)

var PacketeryCheckoutModuleSupercheckout = {

    getSelectedInput: function () {
        return $('#shipping-method input:checked');
    },

    findDeliveryOptions: function () {
        return $('#shipping-method input');
    },

    getWidgetParent: function ($selectedInput) {
        return $selectedInput.closest('li').find('.carrier-extra-content');
    },

    // we're not able to enable/disable Supercheckout submit button, we register our own validator instead
    enableSubmitButton: function () {},
    disableSubmitButton: function () {},

    hideValidationErrors: function () {
        hideGeneralError();
    }
};

$(function () {
    if (typeof addSupercheckoutOrderValidator !== 'undefined') {
        addSupercheckoutOrderValidator(function() {

            var $selectedInput = PacketeryCheckoutModuleSupercheckout.getSelectedInput(),
                $extra = PacketeryCheckoutModuleSupercheckout.getWidgetParent($selectedInput),
                selectedBranchId = $extra.find('.packeta-branch-id').val();

            if (!selectedBranchId) {
                var error_text = $('.packetery-message-pickup-point-not-selected-error').data('content');
                throw { message: error_text };
            }
        });
    }
});
