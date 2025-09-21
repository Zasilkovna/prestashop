<?php

declare(strict_types=1);

namespace Packetery\Request;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\PickupPointValidate\ValidatedOptions;
use Packetery\PickupPointValidate\ValidatedPoint;

class PickupPointValidateRequest
{
    /** @var ValidatedOptions */
    private $options;

    /** @var ValidatedPoint */
    private $point;

    public function __construct(
        ValidatedOptions $options,
        ValidatedPoint $point
    ) {
        $this->options = $options;
        $this->point = $point;
    }

    /**
     * @return array<string, string|bool|float|null>
     */
    public function getSubmittableData(): array
    {
        return [
            'options' => $this->options->getSubmittableData(),
            'point' => $this->point->getSubmittableData(),
        ];
    }
}
