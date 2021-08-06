<input type="hidden" name="baseuri" id="baseuri" value="{$baseUri}">
<input type="hidden" id="shop-language" name="shop-language" value="{$lang}">
<input type="hidden" id="customer_country" name="customer_country" value="{$country}">
<input type="hidden" id="zpoint_carriers" name="zpoint_carriers" value='{$zPointCarriersIdsJSON}'>
<input type="hidden" id="app_identity" name="app_identity" value="{$appIdentity}">
<input type="hidden" id="packeta-api-key" name="packeta-api-key" value="{$apiKey}">
<script type="text/javascript">
    PacketaModule = window.PacketaModule || { };

    {* json_encode writes PHP array to JS object, nofilter prevents " to be turned to &quot; in PS 1.7 *}
    PacketaModule.config = {$packeteryConfig|json_encode nofilter};

    // PS 1.6 OPC re-creates the list of shipping methods, throwing out extra content in the process.
    // PS 1.6 5-steps checkout doesn't do that

    // todo: distinguish 5-steps to toggle visibility here, for OPC toggle in display-before-carrier via onShippingLoaded...

    // In PS 1.7 $ is not defined at this point
    if (typeof $ !== 'undefined') {
		// To make PS 1.6 OPC work
        onShippingLoadedCallback();
    }
</script>
