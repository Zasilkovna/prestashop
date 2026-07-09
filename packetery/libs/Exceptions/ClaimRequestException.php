<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Exceptions;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ClaimRequestException extends \Exception
{
    /** @var string */
    private $faultCode;

    public function __construct(string $faultCode, string $faultString)
    {
        parent::__construct($faultString);
        $this->faultCode = $faultCode;
    }

    public function getFaultCode(): string
    {
        return $this->faultCode;
    }
}
