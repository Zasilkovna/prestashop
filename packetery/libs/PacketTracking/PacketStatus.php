<?php
/**
 * 2017 Zlab Solutions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
 *  @copyright Since 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
namespace Packetery\PacketTracking;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PacketStatus
{
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
        $isFinal,
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
