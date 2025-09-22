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
 */
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
