<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\ApiCarrier;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Immutable representation of a Packeta API carrier row as returned by ApiCarrierRepository::getEntityById().
 */
class ApiCarrierEntity
{
    /** @var string */
    private $id;
    /** @var string */
    private $name;
    /** @var string */
    private $currency;
    /** @var bool */
    private $isPickupPoints;
    /** @var string */
    private $country;
    /** @var bool */
    private $disallowsCod;
    /** @var bool */
    private $requiresSize;

    public function __construct(
        string $id,
        string $name,
        string $currency,
        bool $isPickupPoints,
        string $country,
        bool $disallowsCod,
        bool $requiresSize
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->currency = $currency;
        $this->isPickupPoints = $isPickupPoints;
        $this->country = $country;
        $this->disallowsCod = $disallowsCod;
        $this->requiresSize = $requiresSize;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return self
     */
    public static function fromDbRow(array $row): self
    {
        return new self(
            (string) $row['id'],
            (string) $row['name'],
            (string) $row['currency'],
            (bool) $row['is_pickup_points'],
            (string) $row['country'],
            (bool) $row['disallows_cod'],
            (bool) $row['requires_size']
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function isPickupPoints(): bool
    {
        return $this->isPickupPoints;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function disallowsCod(): bool
    {
        return $this->disallowsCod;
    }

    public function requiresSize(): bool
    {
        return $this->requiresSize;
    }
}
