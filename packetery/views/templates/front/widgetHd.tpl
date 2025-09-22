{**
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
 *}
<div id="packetery-carrier-{$carrier_id|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="customerStreet" name="customerStreet" value="{$customerStreet|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="customerHouseNumber" name="customerHouseNumber" value="{$customerHouseNumber|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="customerCity" name="customerCity" value="{$customerCity|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="customerZip" name="customerZip" value="{$customerZip|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="addressValidated" name="addressValidated" value="{$addressValidated|escape:'htmlall':'UTF-8'}">
    <div id="packetery-widget" class="clearfix">
        <div class="widget-left">
            <div class="col-md-12">
                <div class="zas-box">
                    <button class="btn btn-sm btn-success pull-left open-packeta-widget-hd"
                            id="open-packeta-widget-hd">{l s='Validate delivery address' d='Modules.Packetery.Widgethd'}
                    </button>
                    <br>
                    <ul>
                        <li>{l s='Selected delivery address' d='Modules.Packetery.Widgethd'}:
                            <span class="picked-delivery-place">
                                {assign var=addressInfo value=[]}
                                {if $customerStreet}{$addressInfo[]=$customerStreet}{/if}
                                {if $customerHouseNumber}{$addressInfo[]=$customerHouseNumber}{/if}
                                {if $customerCity}{$addressInfo[]=$customerCity}{/if}
                                {if $customerZip}{$addressInfo[]=$customerZip}{/if}
                                {', '|implode:$addressInfo}
                            </span>
                            <br>
                            <span class="address-validation-result{if $addressValidated} address-validated{/if}">
                                {if $addressValidated}
                                    {$addressValidatedMessage|escape:'htmlall':'UTF-8'}
                                {else}
                                    {$addressNotValidatedMessage|escape:'htmlall':'UTF-8'}
                                {/if}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
