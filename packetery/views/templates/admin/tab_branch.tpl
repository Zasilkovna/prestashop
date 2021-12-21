{*
* 2017 Zlab Solutions
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
*  @copyright 2017 Zlab Solutions
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}

<div class="clearfix">
    <label class="control-label col-lg-3">
        {l s='Total carriers' mod='packetery'}:
    </label>
    <div class="packetery-carriers-right-column"><strong>{$totalCarriers|escape:'htmlall':'UTF-8'}</strong></div>
</div>

<div class="clearfix">
    <label class="control-label col-lg-3">
        {l s='Last carriers update' mod='packetery'}:
    </label>
    <div class="packetery-carriers-right-column">{$lastCarriersUpdate|escape:'htmlall':'UTF-8'}</div>
</div>

<div class="clearfix">
    {if isset($messages)}
        <div class="col-lg-12">
            {foreach from=$messages item=message}
                <div class="alert alert-{$message.class}">{$message.text}</div>
            {/foreach}
        </div>
    {/if}
</div>

<div class="clearfix">
    <label class="control-label col-lg-3">
        {l s='Manually update the list of carriers' mod='packetery'}:
    </label>
    <div class="packetery-carriers-right-column">
        <a href="{$updateCarriersLink}"
           class="btn btn-default btn-block update-carriers">{l s='Update carriers'  mod='packetery'}</a>
    </div>
</div>

<div class="clearfix">
    <label class="control-label col-lg-3">
        {l s='Link for updating carriers by cron' mod='packetery'}:
    </label>
    <div class="packetery-carriers-right-column">
        {$updateCarriersCronLink}
    </div>
</div>
