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

<!--Address Delivery Carriers List-->
<input type="hidden" name="carriers_json" id="carriers_json" value='{$carriers_json}'>
<label class="control-label col-lg-3" for="ad-carriers-list-table">
</label>
<ps-table id="ad-carriers-list-table" header="{l s='Carriers List' mod='packetery'}" icon="icon-users" content="{$packetery_list_ad_carriers|escape:'htmlall':'UTF-8'}" no-items-text="{l s='No items found' mod='packetery'}"></ps-table>
