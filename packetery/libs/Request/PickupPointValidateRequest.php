<?php

declare(strict_types=1);

namespace Packetery\Request;

use Packetery\PickupPointValidate\ValidatedOptions;
use Packetery\PickupPointValidate\ValidatedPoint;

class PickupPointValidateRequest
{
    /** @var ValidatedOptions|null */
    private $options;

    /** @var ValidatedPoint */
    private $point;

    public function __construct(
        ?ValidatedOptions $options,
        ?ValidatedPoint $point
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
            'options' => $this->options ? $this->options->getSubmittableData() : null,
            'point' => $this->point ? $this->point->getSubmittableData() : null,
        ];
    }
}
