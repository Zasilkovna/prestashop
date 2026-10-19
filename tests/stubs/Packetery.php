<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

class Packetery
{
    public const ZPOINT = 'zpoint';
    public const PP_ALL = 'pp_all';
    public const MODULE_SLUG = 'packetery';

    /** @var string */
    public $version = '3.6.0';

    /** @var array<string, string> translated texts by source text */
    public $translations = [];

    /**
     * Escapes like Module::l()
     */
    public function l(string $string, ...$arguments): string
    {
        return htmlspecialchars($this->translations[$string] ?? $string, ENT_COMPAT, 'UTF-8');
    }

    public function getContext(): object
    {
        return new stdClass();
    }

    public function getPathUri(): string
    {
        return '/modules/packetery/';
    }

    /**
     * @param array<string, mixed> $params
     */
    public function getAdminLink(
        string $controller,
        array $params = [],
        string $anchor = ''
    ): string {
        $query = $params === [] ? '' : '?' . http_build_query($params);

        return "/admin/{$controller}{$query}{$anchor}";
    }
}
