{**
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * Admin order detail for an order NOT shipped via Packeta: only the returns section is offered
 * (a return can still be created via Packeta — ticket bod 5/8), the Packeta shipping controls are not.
 *}

<div class="card mt-2 packetery panel">
    <div class="card-header">
        <h3 class="card-header-title">
            <i class="icon-dropbox"></i> {l s='Packeta' mod='packetery'}
        </h3>
    </div>
    <div class="card-body">
        {include file="module:packetery/views/templates/hook/displayOrderReturns.tpl"}

        {if isset($messages)}
            {foreach from=$messages item=message}
                <div class="alert alert-{$message.class|escape:'htmlall':'UTF-8'}">
                    {if isset($message.errors)}
                        {foreach from=$message.errors item=error name=errorLoop}{$error|escape:'htmlall':'UTF-8'}{if !$smarty.foreach.errorLoop.last}<br />{/if}{/foreach}
                    {/if}
                    {if isset($message.text)}
                        {$message.text|escape:'htmlall':'UTF-8'}{if isset($message.trackingNumber) && isset($message.trackingUrl)} {include file="module:packetery/views/templates/admin/trackingLink.tpl" trackingUrl=$message.trackingUrl trackingNumber=$message.trackingNumber}{/if}
                    {/if}
                </div>
            {/foreach}
        {/if}
    </div>
</div>
