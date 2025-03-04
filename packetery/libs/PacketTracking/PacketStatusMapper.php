<?php

namespace Packetery\PacketTracking;

use Packetery;

class PacketStatusMapper {
    const RECEIVED_DATA = 1;
    const ARRIVED = 2;
    const PREPARED_FOR_DEPARTURE = 3;
    const DEPARTED = 4;
    const READY_FOR_PICKUP = 5;
    const HANDED_TO_CARRIER = 6;
    const DELIVERED = 7;
    const POSTED_BACK = 9;
    const RETURNED = 10;
    const CANCELLED = 11;
    const COLLECTED = 12;
    const CUSTOMS = 14;
    const REVERSE_PACKET_ARRIVED = 15;
    const DELIVERY_ATTEMPT = 16;
    const REJECTED_BY_RECIPIENT = 17;
    const UNKNOWN = 999;

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
     * @return array<int, array<string, string>>
     */
    public function getPacketStatuses() {
        return [
            self::RECEIVED_DATA => [
                'status' => 'received data',
                'translated' => $this->module->l('Awaiting consignment', 'packetstatusmapper'),
                'isFinal' => false,
            ],
            self::ARRIVED => [
                'status' => 'arrived',
                'translated' => $this->module->l('Accepted at depot', 'packetstatusmapper'),
                'isFinal' => false,
            ],
            self::PREPARED_FOR_DEPARTURE => [
                'status' => 'prepared for departure',
                'translated' => $this->module->l('On the way', 'packetstatusmapper'),
                'isFinal' => false,
            ],
            self::DEPARTED => [
                'status' => 'departed',
                'translated' => $this->module->l('Departed from depot', 'packetstatusmapper'),
                'isFinal' => false,
            ],
            self::READY_FOR_PICKUP => [
                'status' => 'ready for pickup',
                'translated' => $this->module->l('Ready for pick-up', 'packetstatusmapper'),
                'isFinal' => false,
            ],
            self::HANDED_TO_CARRIER => [
                'status' => 'handed to carrier',
                'translated' => $this->module->l('Handed over to carrier company', 'packetstatusmapper'),
                'isFinal' => false,
            ],
            self::DELIVERED => [
                'status' => 'delivered',
                'translated' => $this->module->l('Delivered', 'packetstatusmapper'),
                'isFinal' => true,
            ],
            self::POSTED_BACK => [
                'status' => 'posted back',
                'translated' => $this->module->l('Returning (on the way back)', 'packetstatusmapper'),
                'isFinal' => false,
            ],
            self::RETURNED => [
                'status' => 'returned',
                'translated' => $this->module->l('Returned to sender', 'packetstatusmapper'),
                'isFinal' => true,
            ],
            self::CANCELLED => [
                'status' => 'cancelled',
                'translated' => $this->module->l('Cancelled', 'packetstatusmapper'),
                'isFinal' => true,
            ],
            self::COLLECTED => [
                'status' => 'collected',
                'translated' => $this->module->l('Parcel has been collected', 'packetstatusmapper'),
                'isFinal' => false,
            ],
            self::CUSTOMS => [
                'status' => 'customs',
                'translated' => $this->module->l('Customs declaration process', 'packetstatusmapper'),
                'isFinal' => false,
            ],
            self::REVERSE_PACKET_ARRIVED => [
                'status' => 'reverse packet arrived',
                'translated' => $this->module->l('Reverse parcel has been accepted at our pick up point', 'packetstatusmapper'),
                'isFinal' => false,
            ],
            self::DELIVERY_ATTEMPT => [
                'status' => 'delivery attempt',
                'translated' => $this->module->l('Unsuccessful delivery attempt of parcel', 'packetstatusmapper'),
                'isFinal' => false,
            ],
            self::REJECTED_BY_RECIPIENT => [
                'status' => 'rejected by recipient',
                'translated' => $this->module->l('Rejected by recipient response', 'packetstatusmapper'),
                'isFinal' => false,
            ],
            self::UNKNOWN => [
                'status' => 'unknown',
                'translated' => $this->module->l('Unknown parcel status', 'packetstatusmapper'),
                'isFinal' => true,
            ],
        ];
    }

}
