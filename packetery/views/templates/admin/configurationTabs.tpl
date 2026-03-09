{**
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

<div class="tab-pane">
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item {if $isStatusSubmitted === false}active{/if}">
            <a href="#packetery_general" class="nav-link" role="tab" data-toggle="tab">
                {l s='General' mod='packetery'}
            </a>
        </li>
        <li class="nav-item {if $isStatusSubmitted === true}active{/if}">
            <a href="#packetery_packet_status_tracking" class="nav-link" role="tab" data-toggle="tab">
                {l s='Packet status tracking' mod='packetery'}
            </a>
        </li>
    </ul>
</div>
<div class="tab-content">
    <div class="tab-pane fade in {if $isStatusSubmitted === false}active{/if}" id="packetery_general">
        {$generalTabContent nofilter}
    </div>
    <div class="tab-pane fade in {if $isStatusSubmitted === true}active{/if}" id="packetery_packet_status_tracking">
        {$packetStatusTrackingTabContent nofilter}
    </div>
</div>
