<input type="hidden" name="baseuri" id="baseuri" value="{$base_uri}">
<input type="hidden" id="shop-language" name="shop-language" value="{$lang}">
<input type="hidden" id="customer_country" name="customer_country" value="{$country}">
<input type="hidden" id="zpoint_carriers" name="zpoint_carriers" value='{$zPointCarriersIdsJSON}'>
<input type="hidden" id="app_identity" name="app_identity" value="{$appIdentity}">
<input type="hidden" id="packeta-api-key" name="packeta-api-key" value="{$api_key}">
<script type="text/javascript">
    var packetery_ajax_front_token = "{$token}";
    var prestashop_version = "{$psVersion}";
    var packetery_must_select_text = "{$must_select_point_text}";
</script>
