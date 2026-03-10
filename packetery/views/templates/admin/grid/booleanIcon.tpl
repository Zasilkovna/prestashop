{**
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

{if isset($prependText)}{$prependText|escape:'htmlall':'UTF-8'}{/if}
<span class="list-action-enable action-{if $value}enabled{else}disabled{/if}"><i class="icon-{if $value}check{else}remove{/if}"></i></span>
