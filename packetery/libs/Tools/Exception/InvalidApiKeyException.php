<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tools\Exception;

if (!defined('_PS_VERSION_')) {
    exit;
}

class InvalidApiKeyException extends \RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function createFromMissingKey(): self
    {
        return new self('API key is missing');
    }
}
