// naming convention: PacketerCheckoutModule + moduleId (first letter in upper case)

var PacketeryCheckoutModulePs16 = {

    isActive: function() {
        var isCorrectVersion = tools.isPS16();
        return isCorrectVersion && this.findDeliveryOptions().length > 0;
    },

    getSelectedInput: function () {
        return $('.delivery_option input:checked');
    },

    findDeliveryOptions: function () {
        return $('.delivery_option input');
    },

    enableSubmitButton: function () {
        /* Reenable disabled elements if carrier is not packetery */
        $('#cgv').attr('disabled', false); // terms of service checkbox
        $('p.payment_module a').off('click.packeteryButtonDisabled');

        var processCarrier = $('button[name=processCarrier]');
        if (processCarrier.length > 0) {
            $('button[name=processCarrier]')
                .attr('disabled', false)
                .removeClass('disabled')
                .css('pointer-events', 'auto');
        }
    },

    disableSubmitButton: function () {
        var processCarrier = $('button[name=processCarrier]');
        if (processCarrier.length > 0) {
            $('button[name=processCarrier]')
                .attr('disabled', true)
                .addClass('disabled')
                .css('pointer-events', 'none');
        }

        /* disable cgv checkbox - cannot continue without selecting pickup point */
        $('#cgv').attr('disabled', true); // terms of service checkbox

        /* unbind click events from payment links and disable them - cannot continue without selecting a pickup point */
        $('p.payment_module a').off('click.packeteryButtonDisabled').on('click.packeteryButtonDisabled', function (e) {
            var errorText = $('.packetery-message-pickup-point-not-selected-error').data('content');
            alert(errorText);
            e.preventDefault();
            return false;
        });
    },

    hideValidationErrors: function () {
    },

    getExtraContentContainer: function ($selectedInput) {
        return $selectedInput.closest('tr').find('td:nth-child(3)');
    },

};
