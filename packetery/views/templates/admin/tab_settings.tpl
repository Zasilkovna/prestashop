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
<div class="settings-input">
	<label class="control-label col-lg-3" for="apipass">
		{l s='API password' mod='packetery'}
	</label>
	<input name="apipass" class="setting_input" type="text" data-id="1" value="{$settings[1][1]|escape:'htmlall':'UTF-8'}"></input>
	<br>

	<label class="control-label col-lg-3" for="eshop_id">
		{l s='E-shop ID' mod='packetery'}
	</label>
	<input name="eshop_id" class="setting_input" type="text" data-id="2" value="{$settings[2][1]|escape:'htmlall':'UTF-8'}" />
	<br>

	<label class="control-label col-lg-3" for="labels_format">
		{l s='Labels format' mod='packetery'}
	</label>
	<select name="labels_format" class="setting_input labels_format" data-id="3" >
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

	<label class="control-label col-lg-3" for="force_country">
		{l s='Fixed Country' mod='packetery'}
	</label>
	<select name="force_country" class="setting_input" data-id="6">
		<option value="" {if $force_country eq ''}selected{/if}>
			{l s='---' mod='packetery'}
		</option>
		{foreach from=$supported_countries item=c}
			<option value="{$c['country']|escape:'htmlall':'UTF-8'}" {if $force_country eq $c['country']}selected{/if}>{$c.name|escape:'htmlall':'UTF-8'}</option>
		{/foreach}
	</select>
	<br>

	<label class="control-label col-lg-3" for="force_language">
		{l s='Fixed Language' mod='packetery'}
	</label>
	<select name="force_language" class="setting_input" data-id="7">
		<option value="" {if $force_language eq ''}selected{/if}>
			{l s='---' mod='packetery'}
		</option>
		{foreach  from=$supported_languages key=k item=c}
			<option value="{$k|escape:'htmlall':'UTF-8'}" {if $force_language eq $k}selected{/if}>{$c|escape:'htmlall':'UTF-8'}</option>
		{/foreach}
	</select>
	<br>
</div>

<!--Address Delivery Carriers List-->
<input type="hidden" name="json_ad" id="json_ad" value='{$ad_array}'>
<input type="hidden" name="zpoint" id="zpoint" value='{$zpoint}'>
<input type="hidden" name="packeta_pickup_point" id="packeta_pickup_point" value='{$packeta_pickup_point}'>
<label class="control-label col-lg-3" for="ad-carriers-list-table">
</label>
<ps-table id="ad-carriers-list-table" header="{l s='Carriers List' mod='packetery'}" icon="icon-users" content="{$packetery_list_ad_carriers|escape:'htmlall':'UTF-8'}" no-items-text="{l s='No items found' mod='packetery'}"></ps-table>
<br><hr><br>

<!--Payment list-->
<label class="control-label col-lg-3" for="payment-list-table">
</label>
<ps-table id="payment-list-table" header="{l s='Payment List' mod='packetery'}" icon="icon-users" content="{$payment_list|escape:'htmlall':'UTF-8'}" no-items-text="{l s='No items found' mod='packetery'}"></ps-table>
<br><hr><br>

