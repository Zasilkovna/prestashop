<div id="module-packetery" class="panel product-tab">
    {if $isPrestaShop16}
        <input type="hidden" name="submitted_tabs[]" value="ModulePacketery"/>
    {/if}
    <input type="hidden" id="packetery_product_extra_hook" name="packetery_product_extra_hook" value="1">
    <h3>
        {l s='Packetery product settings' mod='packetery' d='Modules.Packetery.Display_admin_product_extra'}
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
                    {l s='This product is for adults only and needs to be age verified upon delivery.' mod='packetery' d='Modules.Packetery.Display_admin_product_extra'}
                </label>
            </div>
        </div>
    </div>
    {if $isPrestaShop16}
        <div class="panel-footer">
            <a href="{$adminProductUrl}" class="btn btn-default"><i
                        class="process-icon-cancel"></i> {l s='Cancel' mod='packetery' d='Modules.Packetery.Display_admin_product_extra'}</a>
            <button type="submit" name="submitAddproduct" class="btn btn-default pull-right"><i
                        class="process-icon-save"></i> {l s='Save' mod='packetery' d='Modules.Packetery.Display_admin_product_extra'}</button>
            <button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right"><i
                        class="process-icon-save"></i> {l s='Save and Stay' mod='packetery' d='Modules.Packetery.Display_admin_product_extra'}</button>
        </div>
    {/if}
</div>

