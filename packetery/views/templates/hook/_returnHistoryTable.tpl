{*
* Shared customer-facing list of the order's returns. Expects a `history` variable (newest first).
* Used by the account order detail (display_order_detail.tpl) and the guest return page (front/return/guest.tpl).
*
* @author    Packeta s.r.o. <e-commerce.support@packeta.com>
* @copyright 2015-2026 Packeta s.r.o.
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
<h4>{l s='Your returns' mod='packetery'}</h4>
<table class="table packetery-return-history">
    <thead>
        <tr>
            <th>{l s='Created' mod='packetery'}</th>
            <th>{l s='Status' mod='packetery'}</th>
            <th>{l s='Return number' mod='packetery'}</th>
            <th>{l s='Consignment code' mod='packetery'}</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$history item=historyItem}
            <tr>
                <td>{$historyItem.dateAdd|date_format:'%Y-%m-%d'|escape:'htmlall':'UTF-8'}</td>
                <td>{include file="module:packetery/views/templates/hook/_returnStatusLabel.tpl" status=$historyItem.status}</td>
                <td>
                    {if $historyItem.trackingUrl}
                        <a href="{$historyItem.trackingUrl|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener noreferrer">{$historyItem.claimId|escape:'htmlall':'UTF-8'}</a>
                    {else}
                        {$historyItem.claimId|escape:'htmlall':'UTF-8'}
                    {/if}
                </td>
                <td>{if $historyItem.claimPassword}{$historyItem.claimPassword|escape:'htmlall':'UTF-8'}{/if}</td>
            </tr>
        {/foreach}
    </tbody>
</table>
