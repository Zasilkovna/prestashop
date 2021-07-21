// naming convention: PacketerCheckoutModule + moduleId (first letter in upper case)

var PacketeryCheckoutModulePs16 = {

    isActive: function() {
        var isCorrectVersion = window.prestashop_version && window.prestashop_version.indexOf('1.6') === 0;
        return isCorrectVersion && this.findDeliveryOptions().length > 0;
    },

    getSelectedInput: function () {
        return $('.delivery_option input:checked');
    },

    findDeliveryOptions: function () {
        return $('.delivery_option input');
    },

    enableSubmitButton: function () {
        var processCarrier = $('button[name=processCarrier]');
        /* Reenable disabled elements if carrier is not packetery */
        $('#cgv').attr('disabled', false);
        $('#cgv').parent().parent().removeClass('disabled');
        $('p.payment_module a').off('click.packeteryButtonDisabled');

        if (processCarrier.length > 0) {
            $('button[name=processCarrier]')
                .attr('disabled', false)
                .removeClass('disabled')
                .css("pointer-events", "auto");
        }
    },

    disableSubmitButton: function () {
        var processCarrier = $('button[name=processCarrier]');
        if (processCarrier.length > 0) {
            $('button[name=processCarrier]')
                .attr('disabled', true)
                .addClass('disabled')
                .css("pointer-events", "none");
        }

        /* disable cgv checkbox - cannot continue without selecting a branch */
        $('#cgv').attr('disabled', true);

        /* unbind click events from payment links and disable them - cannot continue without selecting a branch */
        $('p.payment_module a').off('click.packeteryButtonDisabled').on('click.packeteryButtonDisabled', function (e) {
            alert(packetery_must_select_text);
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
