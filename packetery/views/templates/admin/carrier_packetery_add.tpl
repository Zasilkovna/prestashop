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
<div id="add-packetery-carrier-block" class="bootstrap">

	<div class="panel">
		<div class="panel-heading">
			<i class="icon-tags"></i>{l s='Add new Packetery Carrier' mod='packetery'}
		</div>
	</div>

	<div class="add-carrier-form">
		<label class="control-label col-lg-3" for="new_carrier_name">
			{l s='Carrier Name:' mod='packetery'}
		</label>
		<input name="new_carrier_name" class="new_carrier_name" type="text" data-id="1" 
			value="{l s='Personal pick-up – Packetery' mod='packetery'}"></input>
		<br>
		<label class="control-label col-lg-3" for="new_carrier_delay">
			{l s='Delay:' mod='packetery'}
		</label>
		<input name="new_carrier_delay" class="new_carrier_delay" type="text" data-id="2" 
			value="{l s='1-3 days when in stock' mod='packetery'}"></input>
		<br>

		<!--Countries-->
		<label class="control-label col-lg-3" for="packetery_carrier_country">
			{l s='Countries:' mod='packetery'}
		</label>
		<select name="packetery_carrier_country" id="packetery_carrier_country" data-id="3" multiple="">
			{foreach from=$supported_countries item=c}
				<option value="{$c['country']|escape:'htmlall':'UTF-8'}">{$c.name|escape:'htmlall':'UTF-8'}</option>
			{/foreach}
		</select>
		<br>

		<!--Is COD-->
		<label class="control-label col-lg-3" for="new_carrier_is_cod">
			{l s='Is COD:' mod='packetery'}
		</label>
		<input name="new_carrier_is_cod" class="new_carrier_is_cod" type="checkbox" data-id="5" 
			value="1"></input>
		<br>
		<br>
		<br>

		<label class="control-label col-lg-3" for="action_buttons_pc">
		</label>

		<div class="action_buttons_pc" id="action_buttons_pc" name="action_buttons_pc">
			<input type="button" class="btn btn-default btn-block"
				id="submit_new_packetery_carrier" value="{l s='Save' mod='packetery'}">
		</div>
	</div>
</div>