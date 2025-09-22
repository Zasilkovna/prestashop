{**
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
 *  @copyright Since 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}
<div id="module-packetery" class="panel product-tab">
    {if $isPrestaShop16}
        <input type="hidden" name="submitted_tabs[]" value="ModulePacketery"/>
    {/if}
    <input type="hidden" id="packetery_product_extra_hook" name="packetery_product_extra_hook" value="1">
    <h3>
        {l s='Packetery product settings' d='Modules.Packetery.Displayadminproductextra'}
    </h3>
    <div class="form-group">
        <div class="col-lg-1">
				<span class="pull-right">
				</span>
        </div>
        <div class="col-lg-9">
            <div class="checkbox">
                <label>
                    <input
                            type="checkbox"
                            id="packetery_age_verification"
                            name="packetery_age_verification"
                            value="{$packeteryAgeVerification}" {if $packeteryAgeVerification}checked="checked"{/if}>
                    {l s='This product is for adults only and needs to be age verified upon delivery.' d='Modules.Packetery.Displayadminproductextra'}
                </label>
            </div>
        </div>
    </div>
    {if $isPrestaShop16}
        <div class="panel-footer">
            <a href="{$adminProductUrl}" class="btn btn-default"><i
                        class="process-icon-cancel"></i> {l s='Cancel' d='Modules.Packetery.Displayadminproductextra'}</a>
            <button type="submit" name="submitAddproduct" class="btn btn-default pull-right"><i
                        class="process-icon-save"></i> {l s='Save' d='Modules.Packetery.Displayadminproductextra'}</button>
            <button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right"><i
                        class="process-icon-save"></i> {l s='Save and Stay' d='Modules.Packetery.Displayadminproductextra'}</button>
        </div>
    {/if}
</div>

