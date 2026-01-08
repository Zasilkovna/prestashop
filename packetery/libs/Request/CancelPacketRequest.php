<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
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
