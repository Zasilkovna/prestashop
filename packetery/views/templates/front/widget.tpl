{*
 * (c) Packeta s.r.o. 2017-2026
 * SPDX-License-Identifier: AFL-3.0
 *}
<div id="packetery-carrier-{$carrier_id|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="widget_vendors" name="widget_vendors" value='{$widget_vendors|@json_encode|escape}'>
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
                    <input type="hidden" id="packeta-branch-currency" class="packeta-branch-currency" name="packeta-branch-currency"
                           value="{$currency_branch}">
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
