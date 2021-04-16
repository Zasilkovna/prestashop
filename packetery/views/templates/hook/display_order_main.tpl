<div class="card mt-2 packetery panel" id="packetaPickupPointChange">
    <div class="card-header">
        <h3 class="card-header-title">
            <i class="icon-dropbox"></i> {l s='Packeta' mod='packetery'}
        </h3>
    </div>
    <div class="card-body">
        {if $isCarrier}
            <p>{l s='Carrier' mod='packetery'}: <strong class="picked-delivery-place">{$branchName}</strong></p>
        {else}
            <form action="{$returnUrl}" method="post">
                <p>{l s='Pickup point' mod='packetery'}: <strong class="picked-delivery-place">{$branchName}</strong>
                </p>
                <p>
                    <a href="" class="btn btn-outline-secondary btn-default open-packeta-widget"
                       data-widget-options="{$widgetOptions|@json_encode|escape}">{l s='Change pickup point' mod='packetery'}</a>

                    <input type="hidden" name="order_id" value="{$orderId|intval}">
                    <input type="hidden" name="pickup_point">
                </p>
                <div class="text-right">
                    <button class="btn btn-primary">{l s='Save' mod='packetery'}</button>
                </div>
            </form>
            {if isset($messageSuccess)}
                <div class="alert alert-success">{$messageSuccess}</div>
            {/if}
            {if isset($messageError)}
                <div class="alert alert-danger">{$messageError}</div>
            {/if}
        {/if}
    </div>
</div>
