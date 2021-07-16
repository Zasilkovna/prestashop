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
        $('p.payment_module a').unbind('click.packeteryButtonDisabled');

        if (processCarrier.length > 0) {
            $('button[name=processCarrier]')
                .attr('disabled', false)
                .removeClass('disabled')
                .css("pointer-events", "auto");
        }
    },

    disableSubmitButton: function () {
        console.log('disableSubmitButton');

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
        $('p.payment_module a').on('click.packeteryButtonDisabled', function (e) {
            alert(packetery_must_select_text);
            e.preventDefault();
            return false;
        });
    },

    hideValidationErrors: function () {
    },

    createZasBoxes: function (zpoint_carriers, packetery_select_text, packetery_selected_text, data) {
        data = data || {};

        $("input.delivery_option_radio:checked").each(function (i, e) {
            // trim commas
            var carrierId = $(e).val().replace(/(^\,+)|(\,+$)/g, '');
            var carrierData = data[carrierId];

            if (zpoint_carriers.includes(carrierId)) {
                /* Display button and inputs */
                // todo redo id attr to class attr ?
                c = $(e).closest('tr').find('td:nth-child(3)');
                c.append(
                    '<div class="carrier-extra-content">' +
                        '<div id="packetery-widget">' +
                            '<div id="packetery-carrier-' + carrierId + '">' +
                                '<div class="zas-box"><h3><button class="btn btn-success btn-md open-packeta-widget" id="open-packeta-widget">' + packetery_select_text + '</h3>' +
                                    '<div id="selected-branch">' +
                                        '<b>' + packetery_selected_text + '</b>: <span class="picked-delivery-place">' + (carrierData.name_branch ? carrierData.name_branch : '') + '</span>' +
                                    '</div>' +
                                    '<input type="hidden" id="carrier_id" class="carrier_id" name="carrier_id" value="' + carrierId + '">' +
                                    '<input type="hidden" id="packeta-branch-id" class="packeta-branch-id" name="packeta-branch-id" value="' + (carrierData.id_branch ? carrierData.id_branch : '') + '">' +
                                    '<input type="hidden" id="widget_carriers" class="widget_carriers" name="widget_carriers" value="' + (carrierData.widget_carriers ? carrierData.widget_carriers : '') + '">' +
                                    '<input type="hidden" id="packeta-branch-name" class="packeta-branch-name" name="packeta-branch-name" value="' + (carrierData.name_branch ? carrierData.name_branch : '') + '">' +
                                    '<input type="hidden" id="packeta-pickup-point-type" class="packeta-pickup-point-type" name="packeta-pickup-point-type" value="' + (carrierData.pickup_point_type ? carrierData.pickup_point_type : '') + '">' +
                                    '<input type="hidden" id="packeta-carrier-id" class="packeta-carrier-id" name="packeta-carrier-id" value="' + (carrierData.carrier_id ? carrierData.carrier_id : '') + '">' +
                                    '<input type="hidden" id="packeta-carrier-pickup-point-id" class="packeta-carrier-pickup-point-id" name="packeta-carrier-pickup-point-id" value="' + (carrierData.carrier_pickup_point_id ? carrierData.carrier_pickup_point_id : '') + '">' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>');
            }
        });
    }

};
