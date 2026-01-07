{**
 * @copyright 2017-2026 Packeta s.r.o.
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 *}

{extends file="helpers/view/view.tpl"}

{block name=leadin}
{/block}

{block name=override_tpl}
    {if isset($carrierHelper)}
        {$carrierHelper}
    {/if}
{/block}
