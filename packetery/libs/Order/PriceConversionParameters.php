<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Order;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PriceConversionParameters
{
    /**
     * @var string
     */
    private $packeteryCurrency;
    /**
     * @var float
     */
    private $totalPrice;
    /**
     * @var \Currency
     */
    private $orderCurrency;

    public function __construct(
        ?string $packeteryCurrency,
        float $totalPrice,
        \Currency $orderCurrency
    ) {
        $this->packeteryCurrency = $packeteryCurrency;
        $this->totalPrice = $totalPrice;
        $this->orderCurrency = $orderCurrency;
    }

    public function getPacketeryCurrency(): ?string
    {
        return $this->packeteryCurrency;
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    public function getOrderCurrency(): \Currency
    {
        return $this->orderCurrency;
    }
}
