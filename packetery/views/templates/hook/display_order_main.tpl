<div class="card mt-2 packetery panel" id="packetaPickupPointChange">
    <div class="card-header">
        <h3 class="card-header-title">
            <i class="icon-dropbox"></i> {l s='Packeta' mod='packetery'}
        </h3>
    </div>
    <div class="card-body">
        {if $isAddressDelivery}
            <p>
                {l s='Carrier' mod='packetery'}:
                <strong class="picked-delivery-place">
                    {if $pickupPointOrAddressDeliveryName}
                        {$pickupPointOrAddressDeliveryName}
                    {else}
                        {l s='Please select shipment method again' mod='packetery'}
                    {/if}
                </strong>
            </p>
        {else}
            <p>{l s='Pickup point' mod='packetery'}:
                <strong class="picked-delivery-place">
                    {if $pickupPointOrAddressDeliveryName}
                        {$pickupPointOrAddressDeliveryName}
                    {else}
                        {l s='Please select pickup point' mod='packetery'}
                    {/if}
                </strong>
            </p>
            {if $pickupPointChangeAllowed}
                <form action="{$returnUrl}" method="post">
                    <p>
                        <a href="" class="btn btn-outline-secondary btn-default open-packeta-widget"
                           data-widget-options="{$widgetOptions|@json_encode|escape}">{l s='Change pickup point' mod='packetery'}</a>

                        <input type="hidden" name="order_id" value="{$orderId|intval}">
                        <input type="hidden" name="pickup_point">
                    </p>
                    <div class="text-right">
                        <button class="btn btn-primary" name="pickup_point_change">{l s='Save' mod='packetery'}</button>
                    </div>
                </form>
            {/if}
            {if isset($messages)}
                {foreach from=$messages item=message}
                    <div class="alert alert-{$message.class}">{$message.text}</div>
                {/foreach}
            {/if}
        {/if}
    </div>
</div>
