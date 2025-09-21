<?php

namespace Packetery\Module\Exception;

if (!defined('_PS_VERSION_')) {
    exit;
}

class IncorrectApiPasswordException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
