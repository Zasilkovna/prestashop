{**
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

<input type="text" value="{$weight|escape:'htmlall':'UTF-8'}" class="weight" {if $disabled}disabled="disabled"{else}name="weight_{$orderId|escape:'htmlall':'UTF-8'}"{/if}>
