{**
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

{l s='Packeta returns are awaiting your approval: %count%.' sprintf=['%count%'=>$pendingReturnsCount] mod='packetery'}
{if $pendingReturnsLink}
    <a href="{$pendingReturnsLink|escape:'htmlall':'UTF-8'}">{l s='Review returns' mod='packetery'}</a>
{/if}
