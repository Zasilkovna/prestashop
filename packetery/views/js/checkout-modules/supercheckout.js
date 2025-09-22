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
                $widgetParent = packeteryModulesManager.getWidgetParent($selectedInput);

            if ($widgetParent.length === 1) {
                if (PacketaModule.ui.isPickupPointInvalid($widgetParent)) {
                    throw {message: $('.packetery-message-pickup-point-not-selected-error').data('content')};
                }
                if (PacketaModule.ui.isAddressValidationUnsatisfied($widgetParent, $selectedInput)) {
                    throw {message: PacketaModule.config.addressNotValidatedMessage};
                }
            }
        });
    }
});
