<?php

declare(strict_types=1);

namespace Packetery\PickupPointValidate;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ValidatedPoint
{
    /** @var string|null */
    private $id;

    /** @var string|null */
    private $carrierId;

    /** @var bool|null */
    private $carrierPickupPointId;

    public function __construct(
        ?string $id,
        ?string $carrierId,
        ?string $carrierPickupPointId
    ) {
        $this->id = $id;
        $this->carrierId = $carrierId;
        $this->carrierPickupPointId = $carrierPickupPointId;
    }

    /**
     * @return array<string, string|bool|float|null>
     */
    public function getSubmittableData(): array
    {
        return array_filter(get_object_vars($this));
    }
}
