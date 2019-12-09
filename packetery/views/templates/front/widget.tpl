{*
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
*  @copyright 2017 Zlab Solutions
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
<input type="hidden" name="baseuri" id="baseuri" value="{$baseuri|escape:'htmlall':'UTF-8'}">
<input type="hidden" name="ajaxfields" id="ajaxfields" value="{$ajaxfields|escape:'htmlall':'UTF-8'}">
<input type="hidden" name="widget_type" id="widget_type" value="{$widget_type|escape:'htmlall':'UTF-8'}">
<input type="hidden" name="widget_carrier" id="widget_carrier" value="{$widget_carrier|escape:'htmlall':'UTF-8'}">
<input type="hidden" name="widget_force_country" id="widget_force_country"
       value="{$force_country|escape:'htmlall':'UTF-8'}">
<input type="hidden" name="widget_force_language" id="widget_force_language"
       value="{$force_language|escape:'htmlall':'UTF-8'}">
<input type="hidden" id="shop-language" name="shop-language" value="{$language['iso_code']|escape:'htmlall':'UTF-8'}">
<input type="hidden" id="customer_country" name="customer_country" value="{$customer_country|escape:'htmlall':'UTF-8'}">
<input type="hidden" id="carrier_countries" name="carrier_countries" value="{$carrier_countries}">
<input type="hidden" id="allowed_countries" name="allowed_countries" value="{$allowed_countries}">
<input type="hidden" id="module_version" name="module_version" value="{$module_version}">
<!--Packetery widget-->
<div id="packetery-widget">
    <div class="widget-left">
        <div class="col-md-12">
            <div class="zas-box">
                <button class="btn btn-sm btn-success pull-left open-packeta-widget"
                        id="open-packeta-widget">{l s='Select pick-up point:' mod='packetery'}</button>
                <span class="pull-left" id="invalid-country-carrier"
                      style="display:none; color:red">{l s='This carrier is unavailable for your country' mod='packetery'}</span>
                <br>
                <ul id="selected-branch">
                    <li>{l s='Selected pick-up point:' mod='packetery'}
                        <span id="picked-delivery-place" class="picked-delivery-place">{$name_branch}</span>
                    </li>
                </ul>
                <input type="hidden" id="packeta-branch-id" class="packeta-branch-id" name="packeta-branch-id"
                       value="{$id_branch}">
                <input type="hidden" id="packeta-api-key" class="packeta-api-key" name="packeta-api-key"
                       value="{$packeta_api_key}">
                <input type="hidden" id="packeta-branch-name" class="packeta-branch-name" name="packeta-branch-name"
                       value="{$name_branch}">
            </div>
        </div>
    </div>
    <div class="widget-right">
        <div class="branch-foto">
        </div>
    </div>

    <div style="clear:both;margin:10px;"></div>

    <div class="branch_details">
        <div class="widget-left">
            <div class="col-md-6">
                <div>
                    <div class="widget_block_title"><strong>{l s='Opening Hours:' mod='packetery'}</strong></div>
                    <div class="pack-details-opening">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div style="clear:both;"></div>
    <div style="margin: 25px;"></div>
</div>
