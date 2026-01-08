<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\PacketTracking;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PacketStatusRecord
{
    /** @var \DateTimeImmutable */
    private $dateTime;

    /** @var string */
    private $statusCode;

    /** @var string */
    private $statusText;

    /**
     * @param \DateTimeImmutable $dateTime
     * @param string $statusCode
     * @param string $statusText
     */
    public function __construct(\DateTimeImmutable $dateTime, $statusCode, $statusText)
    {
        $this->dateTime = $dateTime;
        $this->statusCode = $statusCode;
        $this->statusText = $statusText;
    }

    /** @return string */
    public function getHash()
    {
        return md5($this->dateTime->format('Y-m-d H:i:s') . '|' . $this->statusCode . '|' . $this->statusText);
    }
}
