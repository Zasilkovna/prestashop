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

/**
 * Immutable representation of a `packetery_order` row as returned by OrderRepository::getEntityById().
 */
class OrderEntity
{
    /** @var int|null */
    private $idBranch;
    /** @var string|null */
    private $nameBranch;
    /** @var int */
    private $idCarrier;
    /** @var bool */
    private $isCod;
    /** @var bool */
    private $isAd;
    /** @var string|null */
    private $currencyBranch;
    /** @var bool */
    private $isCarrier;
    /** @var string|null */
    private $carrierPickupPoint;
    /** @var string|null */
    private $trackingNumber;
    /** @var float|null */
    private $weight;
    /** @var int|null */
    private $length;
    /** @var int|null */
    private $height;
    /** @var int|null */
    private $width;
    /** @var string|null */
    private $zip;
    /** @var string|null */
    private $city;
    /** @var string|null */
    private $street;
    /** @var string|null */
    private $houseNumber;

    public function __construct(
        ?int $idBranch,
        ?string $nameBranch,
        int $idCarrier,
        bool $isCod,
        bool $isAd,
        ?string $currencyBranch,
        bool $isCarrier,
        ?string $carrierPickupPoint,
        ?string $trackingNumber,
        ?float $weight,
        ?int $length,
        ?int $height,
        ?int $width,
        ?string $zip,
        ?string $city,
        ?string $street,
        ?string $houseNumber
    ) {
        $this->idBranch = $idBranch;
        $this->nameBranch = $nameBranch;
        $this->idCarrier = $idCarrier;
        $this->isCod = $isCod;
        $this->isAd = $isAd;
        $this->currencyBranch = $currencyBranch;
        $this->isCarrier = $isCarrier;
        $this->carrierPickupPoint = $carrierPickupPoint;
        $this->trackingNumber = $trackingNumber;
        $this->weight = $weight;
        $this->length = $length;
        $this->height = $height;
        $this->width = $width;
        $this->zip = $zip;
        $this->city = $city;
        $this->street = $street;
        $this->houseNumber = $houseNumber;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return self
     */
    public static function fromDbRow(array $row): self
    {
        return new self(
            isset($row['id_branch']) ? (int) $row['id_branch'] : null,
            isset($row['name_branch']) ? (string) $row['name_branch'] : null,
            (int) $row['id_carrier'],
            (bool) $row['is_cod'],
            (bool) $row['is_ad'],
            isset($row['currency_branch']) ? (string) $row['currency_branch'] : null,
            (bool) $row['is_carrier'],
            isset($row['carrier_pickup_point']) ? (string) $row['carrier_pickup_point'] : null,
            isset($row['tracking_number']) ? (string) $row['tracking_number'] : null,
            isset($row['weight']) ? (float) $row['weight'] : null,
            isset($row['length']) ? (int) $row['length'] : null,
            isset($row['height']) ? (int) $row['height'] : null,
            isset($row['width']) ? (int) $row['width'] : null,
            isset($row['zip']) ? (string) $row['zip'] : null,
            isset($row['city']) ? (string) $row['city'] : null,
            isset($row['street']) ? (string) $row['street'] : null,
            isset($row['house_number']) ? (string) $row['house_number'] : null
        );
    }

    public function getIdBranch(): ?int
    {
        return $this->idBranch;
    }

    public function getNameBranch(): ?string
    {
        return $this->nameBranch;
    }

    public function getIdCarrier(): int
    {
        return $this->idCarrier;
    }

    public function isCod(): bool
    {
        return $this->isCod;
    }

    public function isAd(): bool
    {
        return $this->isAd;
    }

    public function getCurrencyBranch(): ?string
    {
        return $this->currencyBranch;
    }

    public function isCarrier(): bool
    {
        return $this->isCarrier;
    }

    public function getCarrierPickupPoint(): ?string
    {
        return $this->carrierPickupPoint;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }
}
