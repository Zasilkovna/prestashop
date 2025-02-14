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
            ],
            self::ARRIVED => [
                'status' => 'arrived',
                'translated' => $this->module->l('Accepted at depot', 'packetstatusmapper'),
            ],
            self::PREPARED_FOR_DEPARTURE => [
                'status' => 'prepared for departure',
                'translated' => $this->module->l('On the way', 'packetstatusmapper'),
            ],
            self::DEPARTED => [
                'status' => 'departed',
                'translated' => $this->module->l('Departed from depot', 'packetstatusmapper'),
            ],
            self::READY_FOR_PICKUP => [
                'status' => 'ready for pickup',
                'translated' => $this->module->l('Ready for pick-up', 'packetstatusmapper'),
            ],
            self::HANDED_TO_CARRIER => [
                'status' => 'handed to carrier',
                'translated' => $this->module->l('Handed over to carrier company', 'packetstatusmapper'),
            ],
            self::DELIVERED => [
                'status' => 'delivered',
                'translated' => $this->module->l('Delivered', 'packetstatusmapper'),
            ],
            self::POSTED_BACK => [
                'status' => 'posted back',
                'translated' => $this->module->l('Returning (on the way back)', 'packetstatusmapper'),
            ],
            self::RETURNED => [
                'status' => 'returned',
                'translated' => $this->module->l('Returned to sender', 'packetstatusmapper'),
            ],
            self::CANCELLED => [
                'status' => 'cancelled',
                'translated' => $this->module->l('Cancelled', 'packetstatusmapper'),
            ],
            self::COLLECTED => [
                'status' => 'collected',
                'translated' => $this->module->l('Parcel has been collected', 'packetstatusmapper'),
            ],
            self::CUSTOMS => [
                'status' => 'customs',
                'translated' => $this->module->l('Customs declaration process', 'packetstatusmapper'),
            ],
            self::REVERSE_PACKET_ARRIVED => [
                'status' => 'reverse packet arrived',
                'translated' => $this->module->l('Reverse parcel has been accepted at our pick up point', 'packetstatusmapper'),
            ],
            self::DELIVERY_ATTEMPT => [
                'status' => 'delivery attempt',
                'translated' => $this->module->l('Unsuccessful delivery attempt of parcel', 'packetstatusmapper'),
            ],
            self::REJECTED_BY_RECIPIENT => [
                'status' => 'rejected by recipient',
                'translated' => $this->module->l('Rejected by recipient response', 'packetstatusmapper'),
            ],
            self::UNKNOWN => [
                'status' => 'unknown',
                'translated' => $this->module->l('Unknown parcel status', 'packetstatusmapper'),
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getPacketStatusChoices()
    {
        $result = [];

        foreach ($this->getPacketStatuses() as $key => $packetStatus) {
            $result[] = [
                'id' => $key,
                'name' => $packetStatus['translated'],
            ];
        }

        return $result;
    }

}
