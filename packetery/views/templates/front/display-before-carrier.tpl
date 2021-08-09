{*
 This file is inserted before the list of shipping methods:
 * PS 1.6: 5-steps checkout
 * PS 1.6: OPC - twice! order-opc.js inserts this html first along with all carrier html and then again, separately only this html
 * PS 1.7
*}
<script type="text/javascript">
    PacketaModule = window.PacketaModule || { };

    {* json_encode writes PHP array to JS object, nofilter prevents " to be turned to &quot; in PS 1.7 *}
    PacketaModule.config = {$packeteryConfig|json_encode nofilter};

    // PS 1.6 OPC re-creates the list of shipping methods, throwing out extra content in the process.
    // PS 1.6 5-steps checkout doesn't do that

    // todo: distinguish 5-steps to toggle visibility here, for OPC toggle in display-before-carrier via onShippingLoaded...

    if (typeof PacketaModule.runner !== 'undefined') {
        PacketaModule.runner.onBeforeCarrierLoad();
    }
</script>
