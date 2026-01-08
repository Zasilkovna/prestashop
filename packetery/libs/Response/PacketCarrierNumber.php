<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Response;

if (!defined('_PS_VERSION_')) {
    exit;
}

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
