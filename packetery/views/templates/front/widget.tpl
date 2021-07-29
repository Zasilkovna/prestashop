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
<div id="packetery-carrier-{$carrier_id|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="carrier_id" id="carrier_id" value="{$carrier_id|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="widget_carriers" name="widget_carriers" value="{$widget_carriers}">
    <input type="hidden" id="widgetAutoOpen" name="widgetAutoOpen" value="{$widgetAutoOpen}">
    <!--Packetery widget-->
    <div id="packetery-widget" class="clearfix">
        <div class="widget-left">
            <div class="col-md-12">
                <div class="zas-box">
                    <div class="clearfix">
                        <button class="btn btn-sm btn-success pull-left open-packeta-widget" id="open-packeta-widget">{l s='Select pick-up point:' mod='packetery'}</button>
                    </div>
                    <ul id="selected-branch">
                        <li>{l s='Selected pick-up point:' mod='packetery'}
                            <span id="picked-delivery-place" class="picked-delivery-place">{$name_branch}</span>
                        </li>
                    </ul>
                    <input type="hidden" id="packeta-branch-id" class="packeta-branch-id" name="packeta-branch-id"
                           value="{$id_branch}">
                    <input type="hidden" id="packeta-branch-name" class="packeta-branch-name" name="packeta-branch-name"
                           value="{$name_branch}">
                    <input type="hidden" id="packeta-pickup-point-type" class="packeta-pickup-point-type" name="packeta-pickup-point-type"
                           value="{$pickup_point_type}">
                    <input type="hidden" id="packeta-carrier-id" class="packeta-carrier-id" name="packeta-carrier-id"
                           value="{$packeta_carrier_id}">
                    <input type="hidden" id="packeta-carrier-pickup-point-id" class="packeta-carrier-pickup-point-id" name="packeta-carrier-pickup-point-id"
                           value="{$carrier_pickup_point_id}">
                </div>
            </div>
        </div>
    </div>

    <div class="packetery-message-pickup-point-not-selected-error" data-content="{l s='Please select a pick up point before confirming your order' mod='packetery'}"></div>
</div>
