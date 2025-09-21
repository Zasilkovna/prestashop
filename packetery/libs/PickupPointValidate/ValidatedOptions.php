<?php

declare(strict_types=1);

namespace Packetery\PickupPointValidate;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ValidatedOptions
{
    /** @var string|null */
    private $country;

    /** @var string|null */
    private $carriers;

    /** @var bool|null */
    private $claimAssistant;

    /** @var bool|null */
    private $packetConsignment;

    /** @var float|null */
    private $weight;

    /** @var bool|null */
    private $livePickupPoint;

    /** @var string|null */
    private $expeditionDay;

    /** @var bool|null */
    private $cashOnDelivery;

    /** @var string|null */
    private $length;

    /** @var string|null */
    private $width;

    /** @var string|null */
    private $depth;

    /** @var array|null */
    private $vendors;

    public function __construct(
        ?string $country,
        ?string $carriers,
        ?bool $claimAssistant,
        ?bool $packetConsignment,
        ?float $weight,
        ?bool $livePickupPoint,
        ?string $expeditionDay,
        ?bool $cashOnDelivery,
        ?string $length,
        ?string $width,
        ?string $depth,
        ?array $vendors
    ) {
        $this->country = $country;
        $this->carriers = $carriers;
        $this->claimAssistant = $claimAssistant;
        $this->packetConsignment = $packetConsignment;
        $this->weight = $weight;
        $this->livePickupPoint = $livePickupPoint;
        $this->expeditionDay = $expeditionDay;
        $this->cashOnDelivery = $cashOnDelivery;
        $this->length = $length;
        $this->width = $width;
        $this->depth = $depth;
        $this->vendors = $vendors;
    }

    /**
     * @return array<string, string|bool|float|null>
     */
    public function getSubmittableData(): array
    {
        return array_filter(get_object_vars($this));
    }
}
