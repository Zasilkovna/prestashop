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
                            <label for="weight">{l s='Weight' mod='packetery'}:</label>
                        </div>
                        <div class="col-sm-2 my-1">
                            <div class="input-group">
                                <input
                                        type="text"
                                        name="weight"
                                        id="weight"
                                        class="form-control"
                                        value="{$orderWeight}"
                                >
                                <div class="input-group-append">
                                    <span class="input-group-text">kg</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {if $carrierRequiresSize === true}
                    <div class="mt-1">
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
                {/if}

                <div class="mt-1">
                    <div class="form-row align-items-center">
                        <div class="col-sm-2 col-12 my-1">
                            <label for="price_total">{l s='Packet value' mod='packetery'}:</label>
                        </div>
                        <div class="col-sm-2 my-1">
                            <div class="input-group">
                                <input
                                        type="text"
                                        name="price_total"
                                        id="price_total"
                                        class="form-control"
                                        value="{$total}"
                                >
                                <div class="input-group-append">
                                    <span class="input-group-text">{$exportCurrency}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {if $isCod}
                    <div class="mt-1">
                        <div class="form-row align-items-center">
                            <div class="col-sm-2 col-12 my-1">
                                <label for="price_cod">{l s='COD value' mod='packetery'}:</label>
                            </div>
                            <div class="col-sm-2 my-1">
                                <div class="input-group">
                                    <input
                                            type="text"
                                            name="price_cod"
                                            id="price_cod"
                                            class="form-control"
                                            value="{$cod}"
                                    >
                                    <div class="input-group-append">
                                        <span class="input-group-text">{$exportCurrency}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                {/if}

                {* It is not possible to turn it off for other carriers in case that product for adults is present in the order. *}
                {if $carrierSupportsAgeVerification}
                    <div class="mt-1">
                        <div class="form-row align-items-center">
                            <div class="col-sm-2 col-12 my-1">
                            </div>
                            <div class="col-sm-2 my-1">
                                <div class="checkbox">
                                    <div class="md-checkbox md-checkbox-inline">
                                        <label>
                                            <input
                                                    type="checkbox"
                                                    id="age_verification_required"
                                                    name="age_verification_required"
                                                    value="1"
                                                    {if ($ageVerificationRequired === null && $isOrderForAdults) || $ageVerificationRequired === true}checked="checked"{/if}
                                            >
                                            <i class="md-checkbox-control"></i>{l s='Require age verification' mod='packetery'}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                {/if}

                <div class="text-right">
                    <button class="btn btn-primary" name="{$submitButton}">{l s='Save' mod='packetery'}</button>
                </div>
            {/if}
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
