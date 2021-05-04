<div class="card mt-2 packetery panel" id="packetaPickupPointChange">
    <div class="card-header">
        <h3 class="card-header-title">
            <i class="icon-dropbox"></i> {l s='Packeta' mod='packetery'}
        </h3>
    </div>
    <div class="card-body">
        {if $isAddressDelivery}
            <p>{l s='Carrier' mod='packetery'}: <strong class="picked-delivery-place">{$pickupPointOrAddressDeliveryName}</strong></p>
        {else}
            <form action="{$returnUrl}" method="post">
                <p>{l s='Pickup point' mod='packetery'}: <strong class="picked-delivery-place">{$pickupPointOrAddressDeliveryName}</strong>
                </p>
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
            {if isset($message)}
                <div class="alert alert-{$message.class}">{$message.text}</div>
            {/if}
        {/if}
    </div>
</div>
