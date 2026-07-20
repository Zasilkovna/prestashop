<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Returns;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Carrier\CarrierTools;
use Packetery\Tools\ConfigHelper;

/**
 * Builds a typed ReturnSettings from stored configuration. The raw parsing/defaulting lives in
 * fromRawValues() (pure, unit-testable); fromConfig() only reads ConfigHelper and delegates.
 */
class ReturnSettingsFactory
{
    public const DEFAULT_WINDOW_DAYS = 14;

    public const DEFAULT_EXCLUDE_VIRTUAL = true;

    public function fromConfig(): ReturnSettings
    {
        $keys = [
            ConfigHelper::KEY_RETURNS_ENABLED,
            ConfigHelper::KEY_RETURNS_ALLOW_UNREGISTERED,
            ConfigHelper::KEY_RETURNS_WINDOW_DAYS,
            ConfigHelper::KEY_RETURNS_EXCLUDED_CATEGORIES,
            ConfigHelper::KEY_RETURNS_EXCLUDE_VIRTUAL,
            ConfigHelper::KEY_RETURNS_MAX_ITEM_VALUE,
            ConfigHelper::KEY_RETURNS_MAX_ITEM_WEIGHT,
            ConfigHelper::KEY_RETURNS_ALLOWED_GROUPS,
            ConfigHelper::KEY_RETURNS_ALLOWED_CARRIERS,
            ConfigHelper::KEY_RETURNS_ALLOWED_COUNTRIES,
            ConfigHelper::KEY_RETURNS_APPROVE_FIRST,
        ];

        $raw = [];
        foreach ($keys as $key) {
            $raw[$key] = ConfigHelper::get($key);
        }

        return $this->fromRawValues($raw);
    }

    /**
     * @param array<string, mixed> $raw raw config values keyed by ConfigHelper::KEY_RETURNS_*
     *                                  (false/''/missing = not set -> default)
     */
    public function fromRawValues(array $raw): ReturnSettings
    {
        return new ReturnSettings(
            $this->toBool($this->rawValue($raw, ConfigHelper::KEY_RETURNS_ENABLED)),
            $this->toBool($this->rawValue($raw, ConfigHelper::KEY_RETURNS_ALLOW_UNREGISTERED)),
            $this->toIntOrDefault($this->rawValue($raw, ConfigHelper::KEY_RETURNS_WINDOW_DAYS), self::DEFAULT_WINDOW_DAYS),
            $this->toIntList($this->rawValue($raw, ConfigHelper::KEY_RETURNS_EXCLUDED_CATEGORIES)),
            $this->toBoolOrDefault($this->rawValue($raw, ConfigHelper::KEY_RETURNS_EXCLUDE_VIRTUAL), self::DEFAULT_EXCLUDE_VIRTUAL),
            $this->toFloatOrNull($this->rawValue($raw, ConfigHelper::KEY_RETURNS_MAX_ITEM_VALUE)),
            $this->toFloatOrNull($this->rawValue($raw, ConfigHelper::KEY_RETURNS_MAX_ITEM_WEIGHT)),
            $this->toIntList($this->rawValue($raw, ConfigHelper::KEY_RETURNS_ALLOWED_GROUPS)),
            $this->toIntList($this->rawValue($raw, ConfigHelper::KEY_RETURNS_ALLOWED_CARRIERS)),
            $this->toCountryIsoList($this->rawValue($raw, ConfigHelper::KEY_RETURNS_ALLOWED_COUNTRIES)),
            $this->toBool($this->rawValue($raw, ConfigHelper::KEY_RETURNS_APPROVE_FIRST))
        );
    }

    /**
     * Normalizes a raw config value (ConfigHelper::get returns string|false) to ?string, so the
     * downstream parsing helpers can be strictly typed instead of accepting mixed.
     *
     * @param array<string, mixed> $raw
     */
    private function rawValue(array $raw, string $key): ?string
    {
        $value = $raw[$key] ?? null;

        return ($value === false || $value === null) ? null : (string) $value;
    }

    private function toBool(?string $value): bool
    {
        return (bool) (int) $value;
    }

    private function toBoolOrDefault(?string $value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return (bool) (int) $value;
    }

    private function toIntOrDefault(?string $value, int $default): int
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return (int) $value;
    }

    private function toFloatOrNull(?string $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    /**
     * @param string|null $value JSON map id => value (AbstractFormService checkbox storage)
     *
     * @return int[]
     */
    private function toIntList(?string $value): array
    {
        $result = [];
        foreach ($this->selectedIds($value) as $id) {
            $result[] = (int) $id;
        }

        return $result;
    }

    /**
     * @param string|null $value JSON map ISO code => value
     *
     * @return string[]
     */
    private function toCountryIsoList(?string $value): array
    {
        $isoCodes = array_map('strtoupper', $this->selectedIds($value));
        /*
         * An empty/unset country whitelist falls back to the Packeta-serviceable countries so it never
         * blocks everything. Only these countries are selectable and returns run only for them, so
         * "no country restriction" is not a meaningful option — this set is the whole universe.
         */
        if ($isoCodes === []) {
            return CarrierTools::COUNTRIES_WITH_INTERNAL_PICKUP_POINTS;
        }

        return $isoCodes;
    }

    /**
     * Ticked ids from the AbstractFormService checkbox storage — a JSON map id => value where an
     * unticked box is stored as false / empty.
     *
     * @return string[]
     */
    private function selectedIds(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return [];
        }

        $ids = [];
        foreach ($decoded as $id => $checkboxValue) {
            if ($this->isTicked($checkboxValue)) {
                $ids[] = (string) $id;
            }
        }

        return $ids;
    }

    /**
     * @param mixed $checkboxValue
     */
    private function isTicked($checkboxValue): bool
    {
        return !in_array($checkboxValue, [false, null, '', 0, '0'], true);
    }
}
