<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Returns;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Immutable per-item input for return eligibility (no PrestaShop objects).
 * `value` and `weight` are per unit; `quantity` is the ordered amount, so the
 * order totals are the sum of value * quantity and weight * quantity across items.
 */
class ReturnOrderItem
{
    /** @var float */
    private $value;

    /** @var float */
    private $weight;

    /** @var int */
    private $quantity;

    /** @var int[] */
    private $categoryIds;

    /** @var bool */
    private $virtual;

    /**
     * @param int[] $categoryIds
     */
    public function __construct(float $value, float $weight, int $quantity, array $categoryIds, bool $virtual)
    {
        $this->value = $value;
        $this->weight = $weight;
        $this->quantity = $quantity;
        $this->categoryIds = $categoryIds;
        $this->virtual = $virtual;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /** @return int[] */
    public function getCategoryIds(): array
    {
        return $this->categoryIds;
    }

    public function isVirtual(): bool
    {
        return $this->virtual;
    }
}
