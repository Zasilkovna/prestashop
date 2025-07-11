<div class="card mt-2 packetery panel" id="packetaPickupPointChange">
    <div class="card-header">
        <h3 class="card-header-title">
            <i class="icon-dropbox"></i> {l s='Packeta' mod='packetery'}
        </h3>
    </div>
    <div class="card-body">
        {if isset($packetStatusTranslatedCode)}
            <p>
                <span class="packetery-order-status {$statusCssClass}">{$packetStatusTranslatedCode}</span>
            </p>
        {/if}

        {if isset($logLink)}
            <a href="{$logLink}">{l s='Show log' mod='packetery'}</a>
        {/if}

        <form action="{$returnUrl}" method="post">
            {if $isAddressDelivery}
                <p>
                    {l s='Carrier' mod='packetery'}:
                    <strong class="picked-delivery-place" data-validated="{$isAddressValidated}">
                        {if $pickupPointOrAddressDeliveryName}
                            {$pickupPointOrAddressDeliveryName}
                        {else}
                            {l s='Please select shipment method again' mod='packetery'}
                        {/if}
                    </strong>
                </p>
                {if $isAddressValidated && isset($validatedAddress)}
                    <p class="validatedAddress">
                        {l s='Delivery address verified for order' mod='packetery'}:<br>
                        {l s='Street' mod='packetery'}: <strong class="packetery-street">{$validatedAddress['street']} {$validatedAddress['houseNumber']}</strong><br>
                        {l s='City' mod='packetery'}: <strong class="packetery-city">{$validatedAddress['city']}</strong><br>
                        {l s='Zip' mod='packetery'}: <strong class="packetery-zip">{$validatedAddress['zip']}</strong><br>
                        {l s='County' mod='packetery'}: <strong class="packetery-county">{$validatedAddress['county']}</strong><br>
                        {l s='GPS' mod='packetery'}: <strong class="packetery-gps">{$validatedAddress['latitude']}, {$validatedAddress['longitude']}</strong><br>
                    </p>
                {/if}
                {if isset($widgetOptions) && !$isExported}
                    <p>
                        <a href="" class="btn btn-outline-secondary btn-default open-packeta-hd-widget"
                           data-widget-options="{$widgetOptions|@json_encode|escape}">
                            {if isset($validatedAddress) && $validatedAddress['zip']}
                                {l s='Change validated delivery address' mod='packetery'}
                            {else}
                                {l s='Set validated delivery address' mod='packetery'}
                            {/if}
                        </a>

                        <input type="hidden" name="order_id" value="{$orderId|intval}">
                        <input type="hidden" name="address">
                    </p>
                {/if}
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
                {if $pickupPointChangeAllowed && !$isExported}
                    <p>
                        <a href="" class="btn btn-outline-secondary btn-default open-packeta-widget"
                           data-widget-options="{$widgetOptions|@json_encode|escape}">{l s='Change pickup point' mod='packetery'}</a>
                        <input type="hidden" name="order_id" value="{$orderId|intval}">
                        <input type="hidden" name="pickup_point">
                    </p>
                {/if}
            {/if}

            {if !$isExported}
                <div class="mt-4">
                    <div class="form-row align-items-center">
                        <div class="col-sm-2 col-12 my-1">
                            <label>{l s='Size (L x W x H):' mod='packetery'}</label>
                        </div>
                        <div class="col-sm-2 my-1">
                            <input class="form-control" name="length" value="{$orderDetails['length']}" placeholder="{l s='Length' mod='packetery'}">
                        </div>
                        <div class="col-auto my-1">
                            x
                        </div>
                        <div class="col-sm-2 my-1">
                            <input class="form-control" name="width" value="{$orderDetails['width']}" placeholder="{l s='Width' mod='packetery'}">
                        </div>
                        <div class="col-auto my-1">
                            x
                        </div>
                        <div class="col-sm-2 my-1">
                            <input class="form-control" name="height" value="{$orderDetails['height']}" placeholder="{l s='Height' mod='packetery'}">
                        </div>
                        <div class="col-auto my-1">
                            (mm)
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <button class="btn btn-primary" name="{$submitButton}">{l s='Save' mod='packetery'}</button>
                </div>
            {/if }
        </form>

        {* There will be more buttons aka more actions in future. If there is no button hide the divider *}
        {if $showActionButtonsDivider}
            <hr />
        {/if}
        {if $postParcelButtonAllowed}
            <form action="{$returnUrl}" method="post">
                <p>
                    <button class="btn btn-outline-secondary btn-default"
                            type="submit"
                            name="process_post_parcel"
                            id="process_post_parcel"
                    >
                        <i class="material-icons" aria-hidden="true">send</i>
                        {l s='Post parcel' mod='packetery'}
                    </button>
                    <input type="hidden" name="order_id" value="{$orderId|intval}">
                </p>
            </form>
        {/if}
        {if isset($messages)}
            {foreach from=$messages item=message}
                <div class="alert alert-{$message.class}">{$message.text|nl2br}</div>
            {/foreach}
        {/if}
    </div>
</div>
<script type="application/javascript">
    var process_post_parcel_confirmation = "{l s='Do you really wish to post the parcel?' mod='packetery'}";
</script>
