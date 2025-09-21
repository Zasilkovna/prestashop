<?php

declare(strict_types=1);

namespace Packetery\Response;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CancelPacketResponse extends BaseResponse
{
    /**
     * Checks if cancel is possible.
     *
     * @return bool
     */
    public function hasCancelNotAllowedFault(): bool
    {
        return ($this->fault === 'CancelNotAllowedFault');
    }
}
