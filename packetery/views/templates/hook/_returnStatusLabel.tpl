{*
* Shared return-status label. Expects a `status` variable (return status code).
*
* @author    Packeta s.r.o. <e-commerce.support@packeta.com>
* @copyright 2015-2026 Packeta s.r.o.
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
{if $status == 'pending'}{l s='Awaiting approval' mod='packetery'}
{elseif $status == 'created'}{l s='Created' mod='packetery'}
{elseif $status == 'cancelled'}{l s='Cancelled' mod='packetery'}
{elseif $status == 'rejected'}{l s='Rejected' mod='packetery'}
{else}{$status|escape:'htmlall':'UTF-8'}{/if}
