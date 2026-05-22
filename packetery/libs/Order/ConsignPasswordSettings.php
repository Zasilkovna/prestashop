<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Order;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Tools\ConfigHelper;

class ConsignPasswordSettings
{
    public const MODE_IMMEDIATE = 'immediately';
    public const MODE_CRON = 'cron';

    /** @var string|null */
    private $mode;

    private function __construct(?string $mode)
    {
        $this->mode = $mode;
    }

    public static function fromConfig(): self
    {
        if ((bool) ConfigHelper::get(ConfigHelper::KEY_SHOW_CONSIGN_PASSWORD) === false) {
            return new self(null);
        }

        if (ConfigHelper::get(ConfigHelper::KEY_CONSIGN_PASSWORD_RETRIEVAL) === self::MODE_CRON) {
            return new self(self::MODE_CRON);
        }

        return new self(self::MODE_IMMEDIATE);
    }

    public function isEnabled(): bool
    {
        return $this->mode !== null;
    }

    public function isImmediate(): bool
    {
        return $this->mode === self::MODE_IMMEDIATE;
    }

    public function isCron(): bool
    {
        return $this->mode === self::MODE_CRON;
    }
}
