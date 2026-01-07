<?php

namespace Packetery\Exceptions;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AggregatedException extends \Exception
{
    /** @var \Exception[] */
    private $exceptions = [];

    /**
     * AggregatedException constructor.
     *
     * @param \Exception[] $exceptions
     */
    public function __construct(array $exceptions)
    {
        parent::__construct('Multiple exceptions occurred.');
        $this->exceptions = $exceptions;
    }

    /**
     * @return \Exception[]
     */
    public function getExceptions()
    {
        return $this->exceptions;
    }
}
