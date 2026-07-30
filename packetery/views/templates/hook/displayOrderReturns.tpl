{**
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * Returns section of the admin order detail (history + create/approve/reject/cancel actions).
 * Shared by displayOrderMain.tpl (Packeta orders) and displayOrderMainReturnsOnly.tpl (other carriers),
 * so it also carries its own action-confirmation vars.
 *}

<div class="packetery-returns">
    <h3>{l s='Returns' mod='packetery'}</h3>
    {if isset($returns) && $returns|@count > 0}
        <table class="table">
            <thead>
                <tr>
                    <th>{l s='Return number' mod='packetery'}</th>
                    <th>{l s='Consignment code' mod='packetery'}</th>
                    <th>{l s='Status' mod='packetery'}</th>
                    <th>{l s='Created' mod='packetery'}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$returns item=return}
                    <tr>
                        <td>
                            {if $return.claim_id && $return.tracking_url}
                                <a href="{$return.tracking_url|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener noreferrer">{$return.claim_id|escape:'htmlall':'UTF-8'}</a>
                            {else}
                                {$return.claim_id|escape:'htmlall':'UTF-8'}
                            {/if}
                        </td>
                        <td>{$return.claim_password|escape:'htmlall':'UTF-8'}</td>
                        <td>{include file="module:packetery/views/templates/hook/_returnStatusLabel.tpl" status=$return.status}</td>
                        <td>{$return.date_add|escape:'htmlall':'UTF-8'}</td>
                        <td>
                            {if $return.is_pending}
                                <form action="{$returnUrl|escape:'htmlall':'UTF-8'}" method="post">
                                    <input type="hidden" name="id_return" value="{$return.id_return|intval}">
                                    <button class="btn btn-outline-secondary btn-default" type="submit" name="process_approve_return" onclick="return confirm(process_approve_return_confirmation);">
                                        <i class="material-icons" aria-hidden="true">check</i>
                                        {l s='Approve' mod='packetery'}
                                    </button>
                                    <button class="btn btn-outline-secondary btn-default" type="submit" name="process_reject_return" onclick="return confirm(process_reject_return_confirmation);">
                                        <i class="material-icons" aria-hidden="true">block</i>
                                        {l s='Reject' mod='packetery'}
                                    </button>
                                </form>
                            {elseif $return.is_active}
                                <form action="{$returnUrl|escape:'htmlall':'UTF-8'}" method="post" onsubmit="return confirm(process_cancel_return_confirmation);">
                                    <input type="hidden" name="id_return" value="{$return.id_return|intval}">
                                    <button class="btn btn-outline-secondary btn-default" type="submit" name="process_cancel_return">
                                        <i class="material-icons" aria-hidden="true">block</i>
                                        {l s='Cancel return' mod='packetery'}
                                    </button>
                                </form>
                            {/if}
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    {else}
        <p>{l s='No returns for this order yet.' mod='packetery'}</p>
    {/if}
    {if isset($returnCreationAllowed) && $returnCreationAllowed}
        <form action="{$returnUrl|escape:'htmlall':'UTF-8'}" method="post" onsubmit="return confirm(process_create_return_confirmation);">
            <input type="hidden" name="id_order" value="{$orderId|intval}">
            <button class="btn btn-outline-secondary btn-default" type="submit" name="process_create_return">
                <i class="material-icons" aria-hidden="true">reply</i>
                {l s='Create return' mod='packetery'}
            </button>
        </form>
    {/if}
</div>

<script type="application/javascript">
    var process_create_return_confirmation = "{l s='Do you really wish to create the return? The customer will be notified by email.' mod='packetery'}";
    var process_cancel_return_confirmation = "{l s='Do you really wish to cancel the return?' mod='packetery'}";
    var process_approve_return_confirmation = "{l s='Do you really wish to approve the return? It will be sent to Packeta.' mod='packetery'}";
    var process_reject_return_confirmation = "{l s='Do you really wish to reject the return?' mod='packetery'}";
</script>
