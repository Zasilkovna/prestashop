<?php

namespace Packetery\Response;

class PacketInfo extends BaseResponse
{

    /**
     * Packet carrier number.
     * @var string
     */
    private $number;
    /**
     * Packet tracking link
     * @var string
     */
    private $trackingLink;

    /**
     * Sets packet carrier number.
     * @param string $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * Sets packet carrier tracking link.
     * @param string $trackingLink
     */
    public function setTrackingLink($trackingLink)
    {
        $this->trackingLink = $trackingLink;
    }

    /**
     * Gets packet carrier number
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Gets packet carrier tracking link
     * @return string
     */
    public function getTrackingLink()
    {
        return $this->trackingLink;
    }
}
