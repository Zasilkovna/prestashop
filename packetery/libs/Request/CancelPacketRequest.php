<?php

declare(strict_types=1);

namespace Packetery\Request;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CancelPacketRequest
{
    /** @var string */
    private $packetId;

    public function __construct(string $packetId)
    {
        $this->packetId = $packetId;
    }

    public function getPacketId(): string
    {
        return $this->packetId;
    }
}
