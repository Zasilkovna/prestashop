{*
* Shared return-status label. Expects a `status` variable (return status code).
* Colours live in front.css and back.css.
*
* @author    Packeta s.r.o. <e-commerce.support@packeta.com>
* @copyright 2015-2026 Packeta s.r.o.
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
{if $status == 'pending'}<span class="packetery-return-status pending">{l s='Awaiting approval' mod='packetery'}</span>
{elseif $status == 'created'}<span class="packetery-return-status created">{l s='Created' mod='packetery'}</span>
{elseif $status == 'cancelled'}<span class="packetery-return-status cancelled">{l s='Cancelled' mod='packetery'}</span>
{elseif $status == 'rejected'}<span class="packetery-return-status rejected">{l s='Rejected' mod='packetery'}</span>
{else}<span class="packetery-return-status">{$status|escape:'htmlall':'UTF-8'}</span>{/if}
