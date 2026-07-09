{**
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

<a href="{$trackingUrl|escape:'htmlall':'UTF-8'}" target="_blank">{$trackingNumber|escape:'htmlall':'UTF-8'}</a>
{if isset($claimNumber) && $claimNumber}
    <br><span class="packetery-claim-line">{$claimLabel|escape:'htmlall':'UTF-8'} <a href="{$claimUrl|escape:'htmlall':'UTF-8'}" target="_blank">{$claimNumber|escape:'htmlall':'UTF-8'}</a></span>
{/if}
