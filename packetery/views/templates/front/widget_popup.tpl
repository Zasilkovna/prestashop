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

<div style="display:none;">

	<div id="packetery_widget_popup_{$id_carrier|escape:'htmlall':'UTF-8'}" data-carrier="{$id_carrier|escape:'htmlall':'UTF-8'}" class="carrier-extra-content packetery_widget_wrapper" >
		<!--Packetery widget-->
		<div id="packetery-widget">
			<div class="packetery-widget-status">
				<a href="#" class="packetery_widget_popup_{$id_carrier|escape:'htmlall':'UTF-8'}_open" data-status="{if $choosed_branch neq '0'}1{else}0{/if}">
					{if $choosed_branch neq '0'}
						{$choosed_branch|escape:'htmlall':'UTF-8'}
					{else}
						{l s='Please choose delivery branch' mod='packetery'}
					{/if}
				</a>
			</div>

			<h3>{l s='Zasilkovna delivery branch' mod='packetery'}</h3>
			<hr>
			<div class="widget-left">
				<div class="col-md-6">
					<select class="form-control form-control-select js-country" name="country" >
					{if $countries_count neq '1'}
					    <option value="0" disabled="" selected="">{l s='Country' mod='packetery'}</option>
					{/if}
						{foreach $countries as $country}
							<option value="{$country.country|escape:'htmlall':'UTF-8'}" {if $countries_count eq '1'}selected{/if}>{$country.name|escape:'htmlall':'UTF-8'}</option>
						{/foreach}
					</select>
				</div>
				<br>
				<div class="col-md-6">
				    <select class="form-control form-control-select js-city" name="city" >
				        <option value="0" disabled="" selected="">{l s='City' mod='packetery'}</option>
					</select>
				</div>
				<br>
				<div class="col-md-6">
				    <select class="form-control form-control-select js-name" name="name" >
				        <option value="0" disabled="" selected="">{l s='Branch' mod='packetery'}</option>
					</select>
				</div>
			</div>
			<div class="widget-right">
				<div class="branch-foto"  >
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
			<br>
			<div style="clear:both;"></div>
			<div style="margin: 25px;"></div>
			<button type="submit" class="packetery_widget_popup_{$id_carrier|escape:'htmlall':'UTF-8'}_close continue btn btn-primary pull-xs-right confirmPacketeryDeliveryOption" name="confirmDeliveryOption" value="1" style="pointer-events: auto;">
	        	{l s='Continue' mod='packetery'}
	        </button>
			<div class="packetery-widget-close packetery_widget_popup_{$id_carrier|escape:'htmlall':'UTF-8'}_close">X</div>
		</div>
	</div>
</div>
<input type="hidden" id="id_carrier_widget" value="0"></input>
