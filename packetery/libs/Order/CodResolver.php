<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Order;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CodResolver
{
    public const CURRENCY_DEFAULT_PRECISION = 2;

    private const DEFAULT_COD = 0.0;
    private const CURRENCY_CZK = 'CZK';
    private const CURRENCY_HUF = 'HUF';
    private const HUF_MULTIPLES = 5;

    /**
     * @param array{is_cod: mixed, price_cod: mixed} $packeteryOrder
     */
    public function resolveCodValue(array $packeteryOrder, float $total): float
    {
        if ((bool) $packeteryOrder['is_cod'] === false) {
            return self::DEFAULT_COD;
        }
        if ($packeteryOrder['price_cod'] !== null) {
            return (float) $packeteryOrder['price_cod'];
        }

        return $total;
    }

    public function roundCodByCurrency(float $cod, string $currency): float
    {
        if ($currency === self::CURRENCY_CZK) {
            return ceil($cod);
        }
        if ($currency === self::CURRENCY_HUF) {
            return $this->roundUpMultiples($cod, self::HUF_MULTIPLES);
        }

        return round($cod, self::CURRENCY_DEFAULT_PRECISION);
    }

    /**
     * Rounds value up to the nearest multiple of $multiple.
     */
    private function roundUpMultiples(float $value, int $multiple): float
    {
        if ($multiple <= 0) {
            return $value;
        }

        return ceil($value / $multiple) * $multiple;
    }
}
