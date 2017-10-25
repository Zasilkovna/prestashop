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
*
*}

<input type="hidden" name="baseuri" id="baseuri" value="{$baseuri|escape:'htmlall':'UTF-8'}">
<input type="hidden" name="ajaxfields" id="ajaxfields" value="{$ajaxfields}">

<!--Packetery widget-->
<div id="packetery-widget">
	<div class="widget-left">
		<div class="col-md-6">
			<select class="form-control form-control-select js-country" name="country" required="">
			    <option value="0" disabled="" {if $countries_count neq '1'}selected{/if}>{l s='Country' mod='packetery'}</option>
				{foreach $countries as $country}
					<option value="{$country.country|escape:'htmlall':'UTF-8'}{if $countries_count eq '1'}selected{/if}">{$country.name|escape:'htmlall':'UTF-8'}</option>
				{/foreach}
			</select>
		</div>
		<br>
		<div class="col-md-6">
		    <select class="form-control form-control-select js-city" name="city" required="">
		        <option value="0" disabled="" selected="">{l s='City' mod='packetery'}</option>
			</select>
		</div>
		<br>
		<div class="col-md-6">
		    <select class="form-control form-control-select js-name" name="name" required="">
		        <option value="0" disabled="" selected="">{l s='Branch' mod='packetery'}</option>
			</select>
		</div>
	</div>
	<div class="widget-right">
		<div class="branch-foto">
		</div>
	</div>

	<div style="clear:both;margin:10px;"></div>

	<div class="branch_details">
		<div class="widget-left">
			<div class="col-md-6">
				<div>
					<div class="widget_block_title"><strong>{l s='Opening Hours:' mod='packetery'}</strong></div>
					<div class="pack-details-opening">
					</div>
				</div>
			</div>
		</div>
		<div class="widget-right">
			<div class="col-md-6">
				<div>
					<div class="widget_block_title"><strong>{l s='Details:' mod='packetery'}</strong></div>
					<div class="pack-details-container">
					</div>
				</div>
			</div>
		</div>
	</div>
	<div style="clear:both;"></div>
	<div style="margin: 35px;"></div>
	<div id="widget-button-container"> 
		<input type="button" name="test" class="btn btn-default btn-block" id="bo-widget-save-branch" value="{l s='Save'  mod='packetery'}">
	</div>
	<input type="hidden" id="id_order_widget" value="0"></input>
</div>
