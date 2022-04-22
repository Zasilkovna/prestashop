<div class="panel col-lg-12">
    <div id="content" class="bootstrap">
        <h3 class="panel-heading">
            {l s='Packetery product settings' mod='packetery'}
        </h3>
        <div class="checkbox">
            <label>
                <input
                        type="checkbox"
                        id="packetery_age_verification"
                        name="packetery_age_verification"
                        value="{$packeteryAgeVerification}" {if $packeteryAgeVerification}checked="checked"{/if}>
                {l s='This product is for adults only and needs to be age verified upon delivery.' mod='packetery'}
            </label>
        </div>
        <input type="hidden" id="packetery_product_extra_hook" name="packetery_product_extra_hook" value="1">
    </div>
</div>
