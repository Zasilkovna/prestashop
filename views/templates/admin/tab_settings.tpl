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
*  @author    Eugene Zubkov <magrabota@gmail.com>
*  @copyright 2017 Zlab Solutions
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
<div class="settings-input">

	<label class="control-label col-lg-3" for="apipass">
		{l s='API password' mod='packetery'}
	</label>
	<input name="apipass" class="setting_input" type="text" data-id="2" value="{$settings[2][1]|escape:'htmlall':'UTF-8'}"></input>
	<br>

	<label class="control-label col-lg-3" for="labels_format">
		{l s='Labels format' mod='packetery'}
	</label>
	<select name="labels_format" class="setting_input labels_format" data-id="4" >
		<option value="A7 on A7" {if $labels_format eq 'A7 on A7'}selected{/if}>
			{l s='A7 on A7' mod='packetery'}
		</option>
		<option value="A6 on A4" {if $labels_format eq 'A6 on A4'}selected{/if}>
			{l s='A6 on A4' mod='packetery'}
		</option>
		<option value="A7 on A4" {if $labels_format eq 'A7 on A4'}selected{/if}>
			{l s='A7 on A4' mod='packetery'}
		</option>
		<option value="105x35mm on A4" {if $labels_format eq '105x35mm on A4'}selected{/if}>
			{l s='105x35mm on A4' mod='packetery'}
		</option>
		<option value="A8 on A8" {if $labels_format eq 'A8 on A8'}selected{/if}>
			{l s='A8 on A8' mod='packetery'}
		</option>
		<option value="A9 on A4" {if $labels_format eq 'A9 on A4'}selected{/if}>
			{l s='A9 on A4' mod='packetery'}
		</option>
	</select>
	<br>

	<label class="control-label col-lg-3" for="widget_type">
		{l s='Widget type' mod='packetery'}
	</label>
	<select name="widget_type" class="setting_input labels_format" data-id="6">
		<option value="0" {if $widget_type eq '0'}selected{/if}>
			{l s='Popup branches widget' mod='packetery'}
		</option>
		<option value="1" {if $widget_type eq '1'}selected{/if}>
			{l s='Stadart branches widget' mod='packetery'}
		</option>
	</select>
	<br>
</div>
<!--Add carrier-->
{include file="./carrier_packetery_add.tpl" }
 <!--End add-packetery-carrier-block-->
<br><hr><br>

<!--Packetery Carriers list-->
<label class="control-label col-lg-3" for="packetery-carriers-list-table">
</label>
<ps-table id="packetery-carriers-list-table" name="packetery-carriers-list-table" header="{l s='Packetery Carriers' mod='packetery'}" icon="icon-users" content="{$packetery_carriers_list|escape:'htmlall':'UTF-8'}" no-items-text="{l s='No items found' mod='packetery'}"></ps-table>
<br><hr><br>

<!--Address Delivery Carriers List-->
<input type="hidden" name="json_ad" id="json_ad" value='{$ad_array}'>
<label class="control-label col-lg-3" for="ad-carriers-list-table">
</label>
<ps-table id="ad-carriers-list-table" header="{l s='Address Delivery Carriers List' mod='packetery'}" icon="icon-users" content="{$packetery_list_ad_carriers|escape:'htmlall':'UTF-8'}" no-items-text="{l s='No items found' mod='packetery'}"></ps-table>
<br><hr><br>

<!--Payment list-->
<label class="control-label col-lg-3" for="payment-list-table">
</label>
<ps-table id="payment-list-table" header="{l s='Payment List' mod='packetery'}" icon="icon-users" content="{$payment_list|escape:'htmlall':'UTF-8'}" no-items-text="{l s='No items found' mod='packetery'}"></ps-table>