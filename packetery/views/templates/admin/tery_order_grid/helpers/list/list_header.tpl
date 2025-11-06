{*
* 2007-2017 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2017 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{extends file="helpers/list/list_header.tpl"}

{block name=leadin}
{if isset($prepareLabelsMode) && $prepareLabelsMode}
<div class="panel">
	<div class="panel-heading">
		{$translations['labelPrinting']}
	</div>
	<form action="{$REQUEST_URI}" method="post">
		<div class="radio">
			<label for="offset">
				<select id="offset" name="offset">
					{for $var=0 to $max_offset}
						{if $var === 0}
							<option value="{$var|intval}">{$translations['doNotSkipAnyFields']}</option>
						{elseif $var === 1}
							<option value="{$var|intval}">{$translations['skipOneField']}</option>
						{else}
							{* We do not fix range 2-4 - PrestaShop has no support: https://github.com/PrestaShop/PrestaShop/issues/15870 *}
							<option value="{$var|intval}">{$translations['skipNFields']|sprintf:$var}</option>
						{/if}
					{/for}
				</select>
			</label>
		</div>

		{foreach $POST as $key => $value}
			{if is_array($value)}
				{foreach $value as $val}
					<input type="hidden" name="{$key|escape:'html':'UTF-8'}[]" value="{$val|escape:'html':'UTF-8'}" />
				{/foreach}
			{elseif strtolower($key) !== 'offset'}
				<input type="hidden" name="{$key|escape:'html':'UTF-8'}" value="{$value|escape:'html':'UTF-8'}" />
			{/if}
		{/foreach}

		<div class="panel-footer">
			<button type="submit" class="btn btn-default" name="cancelOffsetSelection">
				<i class="icon-remove"></i>
				{$translations['cancel']}
			</button>
			<button type="submit" class="btn btn-default" name="submitPrepareLabels">
				<i class="icon-check"></i>
				{$translations['execute']}
			</button>
		</div>
	</form>
</div>
{/if}
{/block}
