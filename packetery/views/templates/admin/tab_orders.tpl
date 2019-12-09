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
<!--Change Branch-->
{include file="./order_branch_change.tpl"}
<!--Change Branch-->
 
<!--Orders sheet-->
<form class="form-horizontal">
	<input type="hidden" name="po_pages" id="po_pages" value="{$po_pages|escape:'htmlall':'UTF-8'}">
	<div class="prestaui-paginator">
		{l s='Pages: ' mod='packetery'}
		{for $i=1 to $po_pages}
			<a href="#order-page-{$i|escape:'htmlall':'UTF-8'}" class="prestaui-paginator-page">{$i|escape:'htmlall':'UTF-8'}</a>
		{/for}
	</div>
	<ps-table id="packetery-orders-table" header="{l s='Orders' mod='packetery'}" icon="icon-users" content="{$packetery_orders|escape:'htmlall':'UTF-8'}" 
		no-items-text="{l s='No items found' mod='packetery'}"></ps-table>

	<div class=" r-message-success col-lg-9 col-lg-offset-3">
		<div class="alert alert-success clearfix">
        	<div id="packetery-export-success">
        		
        	</div>
	    </div>
	</div>

	<div class="validation-error col-lg-9 col-lg-offset-3">
		<div class="alert alert-danger" >
			<div id="packetery-export-error">
				
			</div>
		</div>
	</div>

	<input type="button" class="export_selected btn btn-default btn-block"
		id="submit_export_orders" value="{l s='Send selected orders and create shipment' mod='packetery'}">

	<input type="button" class="download_pdf btn btn-default btn-block"
		id="submit_download_pdf" value="{l s='Download pdf labels' mod='packetery'}">
	<small>{l s='You can download labels only for orders with tracking number (Sent by "Send selected orders" button)' mod='packetery'}</small>
	<div class="pdf_link"></div>

	<input type="button" class="export_selected_csv btn btn-default btn-block"
		   id="submit_export_orders_csv" value="{l s='CSV Export' mod='packetery'}">
</form>