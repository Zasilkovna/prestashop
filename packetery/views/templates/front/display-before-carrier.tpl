{**
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}
{*
 This file is inserted before the list of shipping methods:
 * PS 1.6: 5-steps checkout
 * PS 1.6: OPC - twice! order-opc.js inserts this html first along with all carrier html and then again, separately only this html
 * PS 1.7
*}
<span class="hidden" id="packetaModuleConfig" data-packetaModuleConfig="{$packetaModuleConfig}"></span>
<script type="text/javascript">
    PacketaModule = window.PacketaModule || { };

    PacketaModule.config = JSON.parse(document.getElementById('packetaModuleConfig').getAttribute('data-packetaModuleConfig'));

    if (typeof PacketaModule.runner !== 'undefined') {
        PacketaModule.runner.onBeforeCarrierLoad();
    }
</script>
