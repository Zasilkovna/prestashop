<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\PacketTracking;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PacketStatus
{
    public const RECEIVED_DATA = 1;
    public const ARRIVED = 2;
    public const PREPARED_FOR_DEPARTURE = 3;
    public const DEPARTED = 4;
    public const READY_FOR_PICKUP = 5;
    public const HANDED_TO_CARRIER = 6;
    public const DELIVERED = 7;
    public const POSTED_BACK = 9;
    public const RETURNED = 10;
    public const CANCELLED = 11;
    public const COLLECTED = 12;
    public const CUSTOMS = 14;
    public const REVERSE_PACKET_ARRIVED = 15;
    public const DELIVERY_ATTEMPT = 16;
    public const REJECTED_BY_RECIPIENT = 17;
    public const UNKNOWN = 999;

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
