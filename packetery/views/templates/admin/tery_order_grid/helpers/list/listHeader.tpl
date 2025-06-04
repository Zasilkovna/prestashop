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
		{l s='Label printing' d='Modules.Packetery.Listheader'}
	</div>
	<form action="{$REQUEST_URI}" method="post">
		<div class="radio">
			<label for="offset">
				<select id="offset" name="offset">
					{for $var=0 to $max_offset}
						{if $var === 0}
							<option value="{$var|intval}">{l s='Do not skip any fields' d='Modules.Packetery.Listheader' sprintf=[$var]}</option>
						{else if $var === 1}
							<option value="{$var|intval}">{l s='Skip 1 field' d='Modules.Packetery.Listheader' sprintf=[$var]}</option>
						{else}
							{* We do not fix range 2-4 - PrestaShop has no support: https://github.com/PrestaShop/PrestaShop/issues/15870 *}
							<option value="{$var|intval}">{l s='Skip %s fields' d='Modules.Packetery.Listheader' sprintf=[$var]}</option>
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
			<button type="submit" name="cancel" class="btn btn-default">
				<i class="icon-remove"></i>
				{l s='Cancel' d='Modules.Packetery.Listheader'}
			</button>
			<button type="submit" class="btn btn-default" name="submitPrepareLabels">
				<i class="icon-check"></i>
				{l s='Execute' d='Modules.Packetery.Listheader'}
			</button>
		</div>
	</form>
</div>
{/if}
{/block}
