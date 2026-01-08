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

class BaseResponse
{
    /**
     * Fault identifier.
     *
     * @var ?string
     */
    protected $fault;

    /**
     * Fault string.
     *
     * @var ?string
     */
    private $faultString;

    /**
     * Checks if is faulty.
     *
     * @return bool
     */
    public function hasFault()
    {
        return (bool) $this->fault;
    }

    /**
     * Checks if password is faulty.
     *
     * @return bool
     */
    public function hasWrongPassword()
    {
        return 'IncorrectApiPasswordFault' === $this->fault;
    }

    /**
     * @return bool
     */
    public function hasPacketIdsFault()
    {
        return $this->fault === 'PacketIdsFault';
    }

    /**
     * @return bool
     */
    public function hasPacketIdFault()
    {
        return $this->fault === 'PacketIdFault';
    }

    /**
     * @return bool
     */
    public function hasInvalidCourierNumberFault()
    {
        return $this->fault === 'InvalidCourierNumber';
    }

    /**
     * Sets fault identifier.
     *
     * @param string $fault fault identifier
     */
    public function setFault($fault)
    {
        $this->fault = $fault;
    }

    /**
     * Sets fault string.
     *
     * @param string $faultString fault string
     */
    public function setFaultString($faultString)
    {
        $this->faultString = $faultString;
    }

    /**
     * Gets fault string.
     *
     * @return string|null
     */
    public function getFaultString()
    {
        return $this->faultString;
    }

    public function getFault(): ?string
    {
        return $this->fault;
    }
}
