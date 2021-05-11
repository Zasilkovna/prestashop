<?php

class SenderGetReturnRoutingException extends Exception
{
    public $senderNotExists = false;

    /**
     * SenderGetReturnRoutingException constructor.
     * @param string $message
     * @param bool $senderNotExists
     */
    public function __construct($message, $senderNotExists)
    {
        parent::__construct($message);
        $this->senderNotExists = $senderNotExists;
    }
}
