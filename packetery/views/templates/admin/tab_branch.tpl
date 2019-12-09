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
<!--Branches count-->
<label class="control-label col-lg-3" for="packetery-total-branches">
	{l s='Total Branches' mod='packetery'}:
</label>
<div name="packetery-total-branches" class="packetery-total-branches"><b>{$total_branches|escape:'htmlall':'UTF-8'}</b></div>
<br style="clear: both;">
<label class="control-label col-lg-3" for="packetery-last-branches-update">
	{l s='Last branches update' mod='packetery'}:
</label>
<div name="packetery-last-branches-update" class="packetery-last-branches-update">{$last_branches_update|escape:'htmlall':'UTF-8'}</div>
<!--End Branches count-->
<br>
<br style="clear: both;">
<label class="control-label col-lg-3" for="update-branches">
	{l s='Manual Branches List Update' mod='packetery'}:
</label>
<p><input type="button" name="update-branches" class="btn btn-default btn-block" id="update-branches" value="{l s='Update branches'  mod='packetery'}" style="padding-left:10px;padding-right:10px;"></p>