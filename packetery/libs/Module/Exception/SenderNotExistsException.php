<?php

namespace Packetery\Module\Exception;

if (!defined('_PS_VERSION_')) {
    exit;
}

class SenderNotExistsException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
