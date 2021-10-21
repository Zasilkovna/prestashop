<div id="packetery-carrier-{$carrier_id|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="shop-language" name="shop-language" value="{$language['iso_code']|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="app_identity" name="app_identity" value="{$app_identity}">
    <input type="hidden" id="widget_carriers" name="widget_carriers" value="{$widget_carriers}">
    <input type="hidden" id="customerCountry" name="customerCountry" value="{$customerCountry|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="customerStreet" name="customerStreet" value="{$customerStreet|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="customerCity" name="customerCity" value="{$customerCity|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="customerZip" name="customerZip" value="{$customerZip|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="addressValidationSetting" name="addressValidationSetting" value="{$addressValidationSetting|escape:'htmlall':'UTF-8'}">
    <div id="packetery-widget" class="clearfix">
        <div class="widget-left">
            <div class="col-md-12">
                <div class="zas-box">
                    <button class="btn btn-sm btn-success pull-left open-packeta-widget-hd"
                            id="open-packeta-widget-hd">{l s='Validate delivery address' mod='packetery'}
                    </button>
                    <br>
                    <ul>
                        <li>{l s='Selected delivery address' mod='packetery'}:
                            <span id="picked-delivery-place" class="picked-delivery-place">
                                {assign var=addressInfo value=[]}
                                {if $customerStreet}{$addressInfo[]=$customerStreet}{/if}
                                {if $customerCity}{$addressInfo[]=$customerCity}{/if}
                                {if $customerZip}{$addressInfo[]=$customerZip}{/if}
                                {', '|implode:$addressInfo}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
