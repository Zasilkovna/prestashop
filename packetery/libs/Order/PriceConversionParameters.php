<?php

namespace Packetery\Order;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Currency;

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
     * @var Currency
     */
    private $orderCurrency;

    public function __construct(
        ?string $packeteryCurrency,
        float $totalPrice,
        Currency $orderCurrency
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

    public function getOrderCurrency(): Currency
    {
        return $this->orderCurrency;
    }
}
