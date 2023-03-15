<div id="packetery-carrier-{$carrier_id|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="baseuri" value="{$baseuri|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="shop-language" value="{$language['iso_code']|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="app_identity" value="{$app_identity}">
    <input type="hidden" id="widget_carriers" name="widget_carriers" value="{$widget_carriers}">
    <input type="hidden" id="customerCountry" name="customerCountry" value="{$customerCountry|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="customerStreet" name="customerStreet" value="{$customerStreet|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="customerHouseNumber" name="customerHouseNumber" value="{$customerHouseNumber|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="customerCity" name="customerCity" value="{$customerCity|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="customerZip" name="customerZip" value="{$customerZip|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="addressValidationSetting" name="addressValidationSetting" value="{$addressValidationSetting|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="addressValidated" name="addressValidated" value="{$addressValidated|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="addressValidatedMessage" name="addressValidatedMessage" value="{$addressValidatedMessage|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="addressNotValidatedMessage" name="addressNotValidatedMessage" value="{$addressNotValidatedMessage|escape:'htmlall':'UTF-8'}">
    <input type="hidden" id="countryDiffersMessage" name="countryDiffersMessage" value="{$countryDiffersMessage|escape:'htmlall':'UTF-8'}">
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
                            <span class="picked-delivery-place">
                                {assign var=addressInfo value=[]}
                                {if $customerStreet}{$addressInfo[]=$customerStreet}{/if}
                                {if $customerHouseNumber}{$addressInfo[]=$customerHouseNumber}{/if}
                                {if $customerCity}{$addressInfo[]=$customerCity}{/if}
                                {if $customerZip}{$addressInfo[]=$customerZip}{/if}
                                {', '|implode:$addressInfo}
                            </span>
                            <br>
                            <span class="address-validation-result{if $addressValidated} address-validated{/if}">
                                {if $addressValidated}
                                    {$addressValidatedMessage|escape:'htmlall':'UTF-8'}
                                {else}
                                    {$addressNotValidatedMessage|escape:'htmlall':'UTF-8'}
                                {/if}
                            </span>
                        </li>
                    </ul>
                    <input type="hidden" class="packeta-api-key" name="packeta-api-key"
                           value="{$packeta_api_key}">
                </div>
            </div>
        </div>
    </div>
    <div class="packetery-address-not-validated-message" data-content="{l s='Address was not validated.' mod='packetery'}"></div>
</div>
