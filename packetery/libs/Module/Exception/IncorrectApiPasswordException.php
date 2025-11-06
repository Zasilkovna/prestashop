<?php

namespace Packetery\Module\Exception;

class IncorrectApiPasswordException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
