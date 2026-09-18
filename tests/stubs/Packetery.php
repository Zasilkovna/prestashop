<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

class Packetery
{
    public const MODULE_SLUG = 'packetery';

    /** @var string */
    public $version = '3.4.0';

    public function l(string $string, ...$arguments): string
    {
        return $string;
    }
}
