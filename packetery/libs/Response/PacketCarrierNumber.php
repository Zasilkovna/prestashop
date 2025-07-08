<?php

namespace Packetery\Response;

class PacketCarrierNumber extends BaseResponse
{
    /**
     * Packet carrier number.
     *
     * @var string
     */
    private $number;

    /**
     * Sets packet carrier number.
     *
     * @param string $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * Gets packet carrier number
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }
}
