{**
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

{extends file="helpers/view/view.tpl"}

{block name=leadin}
{/block}

{block name=override_tpl}
    {if isset($carrierHelper)}
        {$carrierHelper}
    {/if}
{/block}
