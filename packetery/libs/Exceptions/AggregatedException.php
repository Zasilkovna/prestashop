<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

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
