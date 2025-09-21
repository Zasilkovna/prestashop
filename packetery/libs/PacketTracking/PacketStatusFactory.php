<?php

namespace Packetery\PacketTracking;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PacketStatusFactory
{
    /** @var \Packetery */
    private $module;

    /**
     * @param \Packetery $module
     */
    public function __construct(\Packetery $module)
    {
        $this->module = $module;
    }

    /**
     * Gets packet statuses and their translated explanations.
     *
     * @return PacketStatus[]
     */
    public function getPacketStatuses()
    {
        return [
            PacketStatus::RECEIVED_DATA => new PacketStatus(
                PacketStatus::RECEIVED_DATA,
                'received data',
                $this->module->getTranslator()->trans('Awaiting consignment', [], 'Modules.Packetery.Packetstatusfactory'),
                false
            ),
            PacketStatus::ARRIVED => new PacketStatus(
                PacketStatus::ARRIVED,
                'arrived',
                $this->module->getTranslator()->trans('Accepted at depot', [], 'Modules.Packetery.Packetstatusfactory'),
                false
            ),
            PacketStatus::PREPARED_FOR_DEPARTURE => new PacketStatus(
                PacketStatus::PREPARED_FOR_DEPARTURE,
                'prepared for departure',
                $this->module->getTranslator()->trans('On the way', [], 'Modules.Packetery.Packetstatusfactory'),
                false
            ),
            PacketStatus::DEPARTED => new PacketStatus(
                PacketStatus::DEPARTED,
                'departed',
                $this->module->getTranslator()->trans('Departed from depot', [], 'Modules.Packetery.Packetstatusfactory'),
                false
            ),
            PacketStatus::READY_FOR_PICKUP => new PacketStatus(
                PacketStatus::READY_FOR_PICKUP,
                'ready for pickup',
                $this->module->getTranslator()->trans('Ready for pick-up', [], 'Modules.Packetery.Packetstatusfactory'),
                false
            ),
            PacketStatus::HANDED_TO_CARRIER => new PacketStatus(
                PacketStatus::HANDED_TO_CARRIER,
                'handed to carrier',
                $this->module->getTranslator()->trans('Handed over to carrier company', [], 'Modules.Packetery.Packetstatusfactory'),
                false
            ),
            PacketStatus::DELIVERED => new PacketStatus(
                PacketStatus::DELIVERED,
                'delivered',
                $this->module->getTranslator()->trans('Delivered', [], 'Modules.Packetery.Packetstatusfactory'),
                true
            ),
            PacketStatus::POSTED_BACK => new PacketStatus(
                PacketStatus::POSTED_BACK,
                'posted back',
                $this->module->getTranslator()->trans('Returning (on the way back)', [], 'Modules.Packetery.Packetstatusfactory'),
                false
            ),
            PacketStatus::RETURNED => new PacketStatus(
                PacketStatus::RETURNED,
                'returned',
                $this->module->getTranslator()->trans('Returned to sender', [], 'Modules.Packetery.Packetstatusfactory'),
                true
            ),
            PacketStatus::CANCELLED => new PacketStatus(
                PacketStatus::CANCELLED,
                'cancelled',
                $this->module->getTranslator()->trans('Cancelled', [], 'Modules.Packetery.Packetstatusfactory'),
                true
            ),
            PacketStatus::COLLECTED => new PacketStatus(
                PacketStatus::COLLECTED,
                'collected',
                $this->module->getTranslator()->trans('Parcel has been collected', [], 'Modules.Packetery.Packetstatusfactory'),
                false
            ),
            PacketStatus::CUSTOMS => new PacketStatus(
                PacketStatus::CUSTOMS,
                'customs',
                $this->module->getTranslator()->trans('Customs declaration process', [], 'Modules.Packetery.Packetstatusfactory'),
                false
            ),
            PacketStatus::REVERSE_PACKET_ARRIVED => new PacketStatus(
                PacketStatus::REVERSE_PACKET_ARRIVED,
                'reverse packet arrived',
                $this->module->getTranslator()->trans('Reverse parcel has been accepted at our pick up point', [], 'Modules.Packetery.Packetstatusfactory'),
                false
            ),
            PacketStatus::DELIVERY_ATTEMPT => new PacketStatus(
                PacketStatus::DELIVERY_ATTEMPT,
                'delivery attempt',
                $this->module->getTranslator()->trans('Unsuccessful delivery attempt of parcel', [], 'Modules.Packetery.Packetstatusfactory'),
                false
            ),
            PacketStatus::REJECTED_BY_RECIPIENT => new PacketStatus(
                PacketStatus::REJECTED_BY_RECIPIENT,
                'rejected by recipient',
                $this->module->getTranslator()->trans('Rejected by recipient response', [], 'Modules.Packetery.Packetstatusfactory'),
                false
            ),
            PacketStatus::UNKNOWN => new PacketStatus(
                PacketStatus::UNKNOWN,
                'unknown',
                $this->module->getTranslator()->trans('Unknown parcel status', [], 'Modules.Packetery.Packetstatusfactory'),
                true
            ),
        ];
    }
}
