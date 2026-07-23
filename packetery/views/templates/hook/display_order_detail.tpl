{**
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

<section class="box">
    <h3>{l s='Packeta' mod='packetery'}</h3>

    {if $isPacketaOrder}
        <p>{$pickupPointLabel|escape:'htmlall':'UTF-8'}: <strong>{$pickupPointName|escape:'htmlall':'UTF-8'}</strong></p>
    {/if}

    {if $returnFlashCreated}
        <p class="alert alert-success">{l s='Your return has been created.' mod='packetery'}</p>
    {elseif $returnFlashPending}
        <p class="alert alert-success">{l s='Your return request has been submitted and is being processed by the e-shop.' mod='packetery'}</p>
    {elseif $returnFlashError}
        <p class="alert alert-danger">{l s='The return could not be created. Please check your e-mail and phone number and try again, or contact the e-shop.' mod='packetery'}</p>
    {/if}

    {if $returnHistory && $returnHistory|@count > 0}
        <p><strong>{l s='Your returns' mod='packetery'}:</strong></p>
        <ul class="packetery-return-history">
            {foreach from=$returnHistory item=historyItem}
                <li>
                    {include file="module:packetery/views/templates/hook/_returnStatusLabel.tpl" status=$historyItem.status}
                    {if $historyItem.claimId}
                        &mdash; {l s='Return number' mod='packetery'}:
                        {if $historyItem.trackingUrl}
                            <a href="{$historyItem.trackingUrl|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener noreferrer">{$historyItem.claimId|escape:'htmlall':'UTF-8'}</a>
                        {else}
                            <strong>{$historyItem.claimId|escape:'htmlall':'UTF-8'}</strong>
                        {/if}
                    {/if}
                    {if $historyItem.claimPassword}
                        &mdash; {l s='Password' mod='packetery'}: <strong>{$historyItem.claimPassword|escape:'htmlall':'UTF-8'}</strong>
                    {/if}
                </li>
            {/foreach}
        </ul>
    {/if}

    {if $returnState == 'pending'}
        <p>{l s='Your return request is being processed by the e-shop.' mod='packetery'}</p>
    {elseif $returnState == 'form'}
        <h4>{l s='Return goods' mod='packetery'}</h4>
        <form action="{$returnActionUrl|escape:'htmlall':'UTF-8'}" method="post">
            <input type="hidden" name="id_order" value="{$returnOrderId|intval}">
            <input type="hidden" name="token" value="{$returnToken|escape:'htmlall':'UTF-8'}">
            <div class="mb-3">
                <label for="packetery_return_email">{l s='E-mail address' mod='packetery'}</label>
                <input type="email" id="packetery_return_email" name="packetery_email" class="form-control" value="{$returnPrefillEmail|escape:'htmlall':'UTF-8'}" required>
            </div>
            <div class="mb-3">
                <label for="packetery_return_phone">{l s='Phone' mod='packetery'}</label>
                <input type="text" id="packetery_return_phone" name="packetery_phone" class="form-control" value="{$returnPrefillPhone|escape:'htmlall':'UTF-8'}">
            </div>
            <button type="submit" name="submitPacketeryReturn" class="btn btn-primary">
                {l s='Return goods via Packeta' mod='packetery'}
            </button>
        </form>
    {/if}
</section>
