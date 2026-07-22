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
            <p class="alert alert-success">{l s='Your return has been created.' mod='packetery'}</p>
            <p>
                {l s='Return number' mod='packetery'}:
                {if $guestTrackingUrl}
                    <a href="{$guestTrackingUrl|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener noreferrer">{$guestClaimId|escape:'htmlall':'UTF-8'}</a>
                {else}
                    <strong>{$guestClaimId|escape:'htmlall':'UTF-8'}</strong>
                {/if}
            </p>

        {elseif $guestView == 'confirm'}
            <p>{l s='We found your order. Do you want to create a return via Packeta?' mod='packetery'}</p>
            <form action="{$returnActionUrl|escape:'htmlall':'UTF-8'}" method="post">
                <input type="hidden" name="packetery_order_reference" value="{$guestReference|escape:'htmlall':'UTF-8'}">
                <input type="hidden" name="token" value="{$returnToken|escape:'htmlall':'UTF-8'}">
                <div class="mb-3">
                    <label for="packetery_email">{l s='E-mail address' mod='packetery'}</label>
                    <input type="email" id="packetery_email" name="packetery_email" class="form-control" value="{$guestEmail|escape:'htmlall':'UTF-8'}" required>
                </div>
                <div class="mb-3">
                    <label for="packetery_phone">{l s='Phone' mod='packetery'}</label>
                    <input type="text" id="packetery_phone" name="packetery_phone" class="form-control" value="{$guestPhone|escape:'htmlall':'UTF-8'}">
                </div>
                <button type="submit" name="submitPacketeryReturnGuestCreate" class="btn btn-primary">
                    {l s='Return goods via Packeta' mod='packetery'}
                </button>
            </form>

        {elseif $guestView == 'pending'}
            <p class="alert alert-success">{l s='Your return request has been submitted and is being processed by the e-shop.' mod='packetery'}</p>

        {else}
            <p>{l s='Enter your order number and e-mail address to return goods via Packeta.' mod='packetery'}</p>
            <form action="{$returnActionUrl|escape:'htmlall':'UTF-8'}" method="post">
                <div class="mb-3">
                    <label for="packetery_order_reference">{l s='Order number' mod='packetery'}</label>
                    <input type="text" id="packetery_order_reference" name="packetery_order_reference" class="form-control" value="{$guestReference|escape:'htmlall':'UTF-8'}" required>
                </div>
                <div class="mb-3">
                    <label for="packetery_email">{l s='E-mail address' mod='packetery'}</label>
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
