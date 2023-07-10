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

{if isset($messages)}
	<div class="col-lg-12">
		{foreach from=$messages item=message}
			<div class="alert alert-{$message.class}">{$message.text}</div>
		{/foreach}
	</div>
{/if}

<div class="settings-input">
	<label class="control-label col-lg-3" for="apipass">
		{l s='API password' mod='packetery'}
	</label>
	<input name="apipass" class="setting_input" type="text" data-id="PACKETERY_APIPASS" value="{$settings['PACKETERY_APIPASS']|escape:'htmlall':'UTF-8'}" />
	<br>

	<label class="control-label col-lg-3" for="eshop_id">
		{l s='Sender indication' mod='packetery'}
	</label>
	<input name="eshop_id" class="setting_input" type="text" data-id="PACKETERY_ESHOP_ID" value="{$settings['PACKETERY_ESHOP_ID']|escape:'htmlall':'UTF-8'}" />

	<label class="control-label col-lg-3"></label>
	{assign 'clientSectionLink' 'https://client.packeta.com/senders/'}
	<div class="help-block">{l s='You can find the sender indication in the client section: [1]%s[/1] in the "indication" field.' mod='packetery' tags=['<a href="%s">'|packetery_sprintf:$clientSectionLink] sprintf=[$clientSectionLink]}</div>

	{assign 'labels_format' $settings['PACKETERY_LABEL_FORMAT']}
	<label class="control-label col-lg-3" for="labels_format">
		{l s='Labels format' mod='packetery'}
	</label>
	<select name="labels_format" class="setting_input labels_format" data-id="PACKETERY_LABEL_FORMAT" >
		<option value="A7 on A4" {if $labels_format eq 'A7 on A4'}selected{/if}>
			{l s='1/8 of A4, printed on A4, 8 labels per page' mod='packetery'}
		</option>
		<option value="105x35mm on A4" {if $labels_format eq '105x35mm on A4'}selected{/if}>
			{l s='105x35mm, printed on A4, 16 labels per page' mod='packetery'}
		</option>
		<option value="A6 on A4" {if $labels_format eq 'A6 on A4'}selected{/if}>
			{l s='1/4 of A4, printed on A4, 4 labels per page' mod='packetery'}
		</option>
		<option value="A7 on A7" {if $labels_format eq 'A7 on A7'}selected{/if}>
			{l s='1/8 of A4, direct printing, 1 label per page' mod='packetery'}
		</option>
		<option value="A8 on A8" {if $labels_format eq 'A8 on A8'}selected{/if}>
			{l s='1/16 of A4, direct printing, 1 label per page' mod='packetery'}
		</option>
	</select>
	<br>

	<label class="control-label col-lg-3" for="address_validation">
		{l s='Address validation in cart' mod='packetery'}
	</label>
	<select name="address_validation" id="address_validation" data-id="PACKETERY_ADDRESS_VALIDATION">
		{foreach from=$addressValidationOptions key=addressValidationValue item=addressValidationOption}
		<option value="{$addressValidationValue}"
				{if $settings['PACKETERY_ADDRESS_VALIDATION'] eq $addressValidationValue}selected{/if}>
		{$addressValidationOption}
		</option>
		{/foreach}
	</select>
	<br>
</div>

<!--Address Delivery Carriers List-->
<input type="hidden" name="carriers_json" id="carriers_json" value='{$carriers_json}'>
<input type="hidden" name="zpoint" id="zpoint" value='{$zpoint}'>
<input type="hidden" name="pp_all" id="pp_all" value='{$pp_all}'>
<input type="hidden" name="packeta_pickup_points" id="packeta_pickup_points" value='{$packeta_pickup_points}'>
<input type="hidden" name="all_packeta_pickup_points" id="all_packeta_pickup_points" value='{$all_packeta_pickup_points}'>
<label class="control-label col-lg-3" for="ad-carriers-list-table">
</label>
<ps-table id="ad-carriers-list-table" header="{l s='Carriers List' mod='packetery'}" icon="icon-users" content="{$packetery_list_ad_carriers|escape:'htmlall':'UTF-8'}" no-items-text="{l s='No items found' mod='packetery'}"></ps-table>
<br><hr><br>

<!--Payment list-->
<label class="control-label col-lg-3" for="payment-list-table">
</label>
<ps-table id="payment-list-table" header="{l s='Payment List' mod='packetery'}" icon="icon-users" content="{$payment_list|escape:'htmlall':'UTF-8'}" no-items-text="{l s='No items found' mod='packetery'}"></ps-table>
<br><hr><br>

