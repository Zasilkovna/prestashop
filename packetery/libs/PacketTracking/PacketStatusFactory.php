<?php

namespace Packetery\PacketTracking;

use Packetery;

class PacketStatusFactory {

    /** @var Packetery */
    private $module;

    /**
     * @param Packetery $module
     */
    public function __construct(Packetery $module) {
        $this->module = $module;
    }

    /**
     * Gets packet statuses and their translated explanations.
     *
     * @return PacketStatus[]
     */
    public function getPacketStatuses() {
        return [
            PacketStatus::RECEIVED_DATA => new PacketStatus(
                PacketStatus::RECEIVED_DATA,
                'received data',
                $this->module->l('Awaiting consignment', 'packetstatusfactory')
            ),
            PacketStatus::ARRIVED => new PacketStatus(
                PacketStatus::ARRIVED,
                'arrived',
                $this->module->l('Accepted at depot', 'packetstatusfactory')
            ),
            PacketStatus::PREPARED_FOR_DEPARTURE => new PacketStatus(
                PacketStatus::PREPARED_FOR_DEPARTURE,
                'prepared for departure',
                $this->module->l('On the way', 'packetstatusfactory')
            ),
            PacketStatus::DEPARTED => new PacketStatus(
                PacketStatus::DEPARTED,
                'departed',
                $this->module->l('Departed from depot', 'packetstatusfactory')
            ),
            PacketStatus::READY_FOR_PICKUP => new PacketStatus(
                PacketStatus::READY_FOR_PICKUP,
                'ready for pickup',
                $this->module->l('Ready for pick-up', 'packetstatusfactory')
            ),
            PacketStatus::HANDED_TO_CARRIER => new PacketStatus(
                PacketStatus::HANDED_TO_CARRIER,
                'handed to carrier',
                $this->module->l('Handed over to carrier company', 'packetstatusfactory')
            ),
            PacketStatus::DELIVERED => new PacketStatus(
                PacketStatus::DELIVERED,
                'delivered',
                $this->module->l('Delivered', 'packetstatusfactory')
            ),
            PacketStatus::POSTED_BACK => new PacketStatus(
                PacketStatus::POSTED_BACK,
                'posted back',
                $this->module->l('Returning (on the way back)', 'packetstatusfactory')
            ),
            PacketStatus::RETURNED => new PacketStatus(
                PacketStatus::RETURNED,
                'returned',
                $this->module->l('Returned to sender', 'packetstatusfactory')
            ),
            PacketStatus::CANCELLED => new PacketStatus(
                PacketStatus::CANCELLED,
                'cancelled',
                $this->module->l('Cancelled', 'packetstatusfactory')
            ),
            PacketStatus::COLLECTED => new PacketStatus(
                PacketStatus::COLLECTED,
                'collected',
                $this->module->l('Parcel has been collected', 'packetstatusfactory')
            ),
            PacketStatus::CUSTOMS => new PacketStatus(
                PacketStatus::CUSTOMS,
                'customs',
                $this->module->l('Customs declaration process', 'packetstatusfactory')
            ),
            PacketStatus::REVERSE_PACKET_ARRIVED => new PacketStatus(
                PacketStatus::REVERSE_PACKET_ARRIVED,
                'reverse packet arrived',
                $this->module->l('Reverse parcel has been accepted at our pick up point', 'packetstatusfactory')
            ),
            PacketStatus::DELIVERY_ATTEMPT => new PacketStatus(
                PacketStatus::DELIVERY_ATTEMPT,
                'delivery attempt',
                $this->module->l('Unsuccessful delivery attempt of parcel', 'packetstatusfactory')
            ),
            PacketStatus::REJECTED_BY_RECIPIENT => new PacketStatus(
                PacketStatus::REJECTED_BY_RECIPIENT,
                'rejected by recipient',
                $this->module->l('Rejected by recipient response', 'packetstatusfactory')
            ),
            PacketStatus::UNKNOWN => new PacketStatus(
                PacketStatus::UNKNOWN,
                'unknown',
                $this->module->l('Unknown parcel status', 'packetstatusfactory')
            ),
        ];
    }

}
