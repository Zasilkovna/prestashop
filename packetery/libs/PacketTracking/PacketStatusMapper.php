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
                'translated' => $this->module->getTranslator()->trans('Awaiting consignment', [], 'Modules.Packetery.Packetstatusmapper'),
                'isFinal' => false,
            ],
            self::ARRIVED => [
                'status' => 'arrived',
                'translated' => $this->module->getTranslator()->trans('Accepted at depot', [], 'Modules.Packetery.Packetstatusmapper'),
                'isFinal' => false,
            ],
            self::PREPARED_FOR_DEPARTURE => [
                'status' => 'prepared for departure',
                'translated' => $this->module->getTranslator()->trans('On the way', [], 'Modules.Packetery.Packetstatusmapper'),
                'isFinal' => false,
            ],
            self::DEPARTED => [
                'status' => 'departed',
                'translated' => $this->module->getTranslator()->trans('Departed from depot', [], 'Modules.Packetery.Packetstatusmapper'),
                'isFinal' => false,
            ],
            self::READY_FOR_PICKUP => [
                'status' => 'ready for pickup',
                'translated' => $this->module->getTranslator()->trans('Ready for pick-up', [], 'Modules.Packetery.Packetstatusmapper'),
                'isFinal' => false,
            ],
            self::HANDED_TO_CARRIER => [
                'status' => 'handed to carrier',
                'translated' => $this->module->getTranslator()->trans('Handed over to carrier company', [], 'Modules.Packetery.Packetstatusmapper'),
                'isFinal' => false,
            ],
            self::DELIVERED => [
                'status' => 'delivered',
                'translated' => $this->module->getTranslator()->trans('Delivered', [], 'Modules.Packetery.Packetstatusmapper'),
                'isFinal' => true,
            ],
            self::POSTED_BACK => [
                'status' => 'posted back',
                'translated' => $this->module->getTranslator()->trans('Returning (on the way back)', [], 'Modules.Packetery.Packetstatusmapper'),
                'isFinal' => false,
            ],
            self::RETURNED => [
                'status' => 'returned',
                'translated' => $this->module->getTranslator()->trans('Returned to sender', [], 'Modules.Packetery.Packetstatusmapper'),
                'isFinal' => true,
            ],
            self::CANCELLED => [
                'status' => 'cancelled',
                'translated' => $this->module->getTranslator()->trans('Cancelled', [], 'Modules.Packetery.Packetstatusmapper'),
                'isFinal' => true,
            ],
            self::COLLECTED => [
                'status' => 'collected',
                'translated' => $this->module->getTranslator()->trans('Parcel has been collected', [], 'Modules.Packetery.Packetstatusmapper'),
                'isFinal' => false,
            ],
            self::CUSTOMS => [
                'status' => 'customs',
                'translated' => $this->module->getTranslator()->trans('Customs declaration process', [], 'Modules.Packetery.Packetstatusmapper'),
                'isFinal' => false,
            ],
            self::REVERSE_PACKET_ARRIVED => [
                'status' => 'reverse packet arrived',
                'translated' => $this->module->getTranslator()->trans('Reverse parcel has been accepted at our pick up point', [], 'Modules.Packetery.Packetstatusmapper'),
                'isFinal' => false,
            ],
            self::DELIVERY_ATTEMPT => [
                'status' => 'delivery attempt',
                'translated' => $this->module->getTranslator()->trans('Unsuccessful delivery attempt of parcel', [], 'Modules.Packetery.Packetstatusmapper'),
                'isFinal' => false,
            ],
            self::REJECTED_BY_RECIPIENT => [
                'status' => 'rejected by recipient',
                'translated' => $this->module->getTranslator()->trans('Rejected by recipient response', [], 'Modules.Packetery.Packetstatusmapper'),
                'isFinal' => false,
            ],
            self::UNKNOWN => [
                'status' => 'unknown',
                'translated' => $this->module->getTranslator()->trans('Unknown parcel status', [], 'Modules.Packetery.Packetstatusmapper'),
                'isFinal' => true,
            ],
        ];
    }

}
