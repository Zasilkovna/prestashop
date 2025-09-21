<?php

declare(strict_types=1);

namespace Packetery\Response;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PickupPointValidateResponse
{
    /** @var bool */
    private $isValid;

    /** @var array<int, array{code: string, description: string}> */
    private $errors;

    /**
     * @param bool $isValid
     * @param array<int, array{code: string, description: string}> $errors
     */
    public function __construct(bool $isValid, array $errors)
    {
        $this->isValid = $isValid;
        $this->errors = $errors;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * @return array<int, array{code: string, description: string}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
