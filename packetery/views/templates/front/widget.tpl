{*
 * (c) Packeta s.r.o. 2017-2026
 * SPDX-License-Identifier: AFL-3.0
 *}
<div id="packetery-carrier-{$carrier_id|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="widget_vendors" name="widget_vendors" value='{$widget_vendors|escape:'htmlall':'UTF-8'}'>
    <!--Packetery widget-->
    <div id="packetery-widget" class="clearfix">
        <div class="widget-left">
            <div class="col-md-12">
                <div class="zas-box">
                    <div class="clearfix">
                        <button class="btn btn-sm btn-success pull-left open-packeta-widget" id="open-packeta-widget">{l s='Select pick-up point:' mod='packetery'}</button>
                    </div>
                    <ul id="selected-branch">
                        <li>
                            {l s='Selected pick-up point:' mod='packetery'}
                            <br>
                            <span id="picked-delivery-place" class="picked-delivery-place">
                                {if $pickup_point_type === 'external'}
                                    {$name_branch|escape:'htmlall':'UTF-8'}
                                {else}
                                    {$point_place|escape:'htmlall':'UTF-8'}<br>
                                    {if $point_street}
                                        {$point_street|escape:'htmlall':'UTF-8'},
                                    {/if}
                                    {$point_city|escape:'htmlall':'UTF-8'}
                                    {$point_zip|escape:'htmlall':'UTF-8'}
                                {/if}
                            </span>
                        </li>
                    </ul>
                    <input type="hidden" id="packeta-branch-id" class="packeta-branch-id" name="packeta-branch-id"
                           value="{$id_branch|escape:'htmlall':'UTF-8'}">
                    <input type="hidden" id="packeta-branch-name" class="packeta-branch-name" name="packeta-branch-name"
                           value="{$name_branch|escape:'htmlall':'UTF-8'}">
                    <input type="hidden" id="packeta-branch-currency" class="packeta-branch-currency" name="packeta-branch-currency"
                           value="{$currency_branch|escape:'htmlall':'UTF-8'}">
                    <input type="hidden" id="packeta-pickup-point-type" class="packeta-pickup-point-type" name="packeta-pickup-point-type"
                           value="{$pickup_point_type|escape:'htmlall':'UTF-8'}">
                    <input type="hidden" id="packeta-carrier-id" class="packeta-carrier-id" name="packeta-carrier-id"
                           value="{$packeta_carrier_id|escape:'htmlall':'UTF-8'}">
                    <input type="hidden" id="packeta-carrier-pickup-point-id" class="packeta-carrier-pickup-point-id" name="packeta-carrier-pickup-point-id"
                           value="{$carrier_pickup_point_id|escape:'htmlall':'UTF-8'}">
                    <input type="hidden" id="packeta-point-place" class="packeta-point-place" name="packeta-point-place"
                           value="{$point_place|escape:'htmlall':'UTF-8'}">
                    <input type="hidden" id="packeta-point-street" class="packeta-point-street" name="packeta-point-street"
                           value="{$point_street|escape:'htmlall':'UTF-8'}">
                    <input type="hidden" id="packeta-point-city" class="packeta-point-city" name="packeta-point-city"
                           value="{$point_city|escape:'htmlall':'UTF-8'}">
                    <input type="hidden" id="packeta-point-zip" class="packeta-point-zip" name="packeta-point-zip"
                           value="{$point_zip|escape:'htmlall':'UTF-8'}">
                </div>
            </div>
        </div>
    </div>

    <div class="packetery-message-pickup-point-not-selected-error" data-content="{l s='Please select a pick up point before confirming your order' mod='packetery'}"></div>
</div>
