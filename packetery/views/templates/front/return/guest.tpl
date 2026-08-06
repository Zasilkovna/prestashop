{**
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}
{extends file=$layout}

{block name='content'}
    <section id="packetery-return" class="page-content card card-body">
        <h1 class="h1">{l s='Return goods via Packeta' mod='packetery'}</h1>

        {if $guestError}
            <p class="alert alert-danger">{$guestError|escape:'htmlall':'UTF-8'}</p>
        {/if}

        {if $guestNotice}
            <p class="alert alert-warning">{$guestNotice|escape:'htmlall':'UTF-8'}</p>
        {/if}

        {if $guestView == 'created'}
            {* no message on a plain lookup: the list below already shows the return *}
            {if $guestJustCreated}
                <p class="alert alert-success">{l s='Your return has been created.' mod='packetery'}</p>
            {elseif $guestCreateAttempted}
                <p class="alert alert-info">{l s='No new return has been created.' mod='packetery'}</p>
            {/if}
            {include file="module:packetery/views/templates/hook/_returnHistoryTable.tpl" history=$guestHistory}
            <p>{l s='Packeta sends the return details to your e-mail. If the e-mail does not arrive, contact the e-shop.' mod='packetery'}</p>

        {elseif $guestView == 'confirm'}
            <p>{l s='We found your order. Do you want to create a return via Packeta?' mod='packetery'}</p>
            {if $guestHistory && $guestHistory|@count > 0}
                {include file="module:packetery/views/templates/hook/_returnHistoryTable.tpl" history=$guestHistory}
            {/if}
            <form action="{$returnActionUrl|escape:'htmlall':'UTF-8'}" method="post" class="packetery-return-form" novalidate>
                <input type="hidden" name="packetery_order_reference" value="{$guestReference|escape:'htmlall':'UTF-8'}">
                <input type="hidden" name="token" value="{$returnToken|escape:'htmlall':'UTF-8'}">
                <div class="mb-3">
                    <label for="packetery_email" class="packetery-required">{l s='E-mail address' mod='packetery'}</label>
                    <input type="email" id="packetery_email" name="packetery_email" class="form-control" value="{$guestEmail|escape:'htmlall':'UTF-8'}" required>
                </div>
                <div class="mb-3">
                    <label for="packetery_phone">{l s='Phone' mod='packetery'}</label>
                    <input type="text" id="packetery_phone" name="packetery_phone" class="form-control" value="{$guestPhone|escape:'htmlall':'UTF-8'}">
                </div>
                {include file="module:packetery/views/templates/hook/_returnConsentCheckbox.tpl" consentTermsTag=$consentTermsTag consentPrivacyTag=$consentPrivacyTag}
                <button type="submit" name="submitPacketeryReturnGuestCreate" class="btn btn-primary">
                    {l s='Return goods via Packeta' mod='packetery'}
                </button>
            </form>

        {elseif $guestView == 'pending'}
            {if $guestJustCreated}
                <p class="alert alert-success">{l s='Your return request has been submitted and is being processed by the e-shop.' mod='packetery'}</p>
            {elseif $guestCreateAttempted}
                <p class="alert alert-info">{l s='No new return has been created.' mod='packetery'}</p>
            {/if}
            {include file="module:packetery/views/templates/hook/_returnHistoryTable.tpl" history=$guestHistory}

        {else}
            {if $guestHistory && $guestHistory|@count > 0}
                {include file="module:packetery/views/templates/hook/_returnHistoryTable.tpl" history=$guestHistory}
            {/if}
            <p>{l s='Enter your order number and e-mail address to return goods via Packeta.' mod='packetery'}</p>
            <form action="{$returnActionUrl|escape:'htmlall':'UTF-8'}" method="post" class="packetery-return-form" novalidate>
                <div class="mb-3">
                    <label for="packetery_order_reference" class="packetery-required">{l s='Order number' mod='packetery'}</label>
                    <input type="text" id="packetery_order_reference" name="packetery_order_reference" class="form-control" value="{$guestReference|escape:'htmlall':'UTF-8'}" required>
                </div>
                <div class="mb-3">
                    <label for="packetery_email" class="packetery-required">{l s='E-mail address' mod='packetery'}</label>
                    <input type="email" id="packetery_email" name="packetery_email" class="form-control" value="{$guestEmail|escape:'htmlall':'UTF-8'}" required>
                </div>
                <input type="hidden" name="token" value="{$returnToken|escape:'htmlall':'UTF-8'}">
                <button type="submit" name="submitPacketeryReturnLookup" class="btn btn-primary">
                    {l s='Find order' mod='packetery'}
                </button>
            </form>
        {/if}
    </section>
{/block}
