<?php

namespace Packetery\Module\Exception;

class SenderNotExistsException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
