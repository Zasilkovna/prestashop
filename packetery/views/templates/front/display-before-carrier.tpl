<input type="hidden" name="baseuri" id="baseuri" value="{$baseUri}">
<input type="hidden" id="shop-language" name="shop-language" value="{$lang}">
<input type="hidden" id="customer_country" name="customer_country" value="{$country}">
<input type="hidden" id="zpoint_carriers" name="zpoint_carriers" value='{$zPointCarriersIdsJSON}'>
<input type="hidden" id="app_identity" name="app_identity" value="{$appIdentity}">
<input type="hidden" id="packeta-api-key" name="packeta-api-key" value="{$apiKey}">
<input type="hidden" id="widgetAutoOpen" name="widgetAutoOpen" value="{$widgetAutoOpen}">
<script type="text/javascript">
    var packeteryAjaxFrontToken = "{$token}";
    var prestashopVersion = "{$psVersion}";
    var packeteryMustSelectText = "{$mustSelectPointText}";

    {if Tools::version_compare($psVersion, '1.7.0', '<')}
    // In PS 1.7 $ is not defined at some moment.
    $(function() {
        packeteryCheckZasBoxAndLoad(); // To make PS 1.6 OPC work
    });
    {/if}
</script>
