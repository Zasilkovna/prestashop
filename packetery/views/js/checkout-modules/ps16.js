/**
 * 2017 Zlab Solutions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
 *  @copyright Since 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */// naming convention: PacketerCheckoutModule + moduleId (first letter in upper case)

var PacketeryCheckoutModulePs16 = {

    isActive: function () {
        var isCorrectVersion = PacketaModule.tools.isPS16();
        return isCorrectVersion && this.findDeliveryOptions().length > 0;
    },

    getSelectedInput: function () {
        return $('.delivery_option input:checked');
    },

    findDeliveryOptions: function () {
        return $('#carrier_area .delivery_option input');
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

    getExtraContentSelector: function () {
        return '.carrier-extra-content';
    }
};
