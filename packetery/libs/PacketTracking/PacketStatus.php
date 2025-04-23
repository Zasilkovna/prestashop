<?php

namespace Packetery\PacketTracking;

class PacketStatus {
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

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $translatedCode;

    /**
     * @var bool
     */
    private $isFinal;

    public function __construct(
        $id,
        $code,
        $translatedCode, 
        $isFinal
    ) {

        $this->id = $id;
        $this->code = $code;
        $this->translatedCode = $translatedCode;
        $this->isFinal = $isFinal;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getTranslatedCode(): string
    {
        return $this->translatedCode;
    }

    public function isFinal(): bool
    {
        return $this->isFinal;
    }

}
