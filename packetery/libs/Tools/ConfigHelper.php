<?php

namespace Packetery\Tools;

use Configuration;
use Context;
use Language;
use Shop;

class ConfigHelper
{
    const KEY_LAST_FEATURE_CHECK = 'PACKETERY_LAST_FEATURE_CHECK';
    const KEY_LAST_VERSION_URL = 'PACKETERY_LAST_VERSION_URL';
    const KEY_LAST_VERSION = 'PACKETERY_LAST_VERSION';

    const BEHAVIOR_ALL = 'all'; // default
    const BEHAVIOR_SEPARATE = 'separate';

    private static $configBehavior = [
        // It is possible to have multiple senders for one set of credentials.
        'PACKETERY_ESHOP_ID' => self::BEHAVIOR_SEPARATE,
    ];

    /**
     * We do not try to fix rare errors caused by using pre 3.0 versions with multistore on.
     * @param string $key
     * @return false|string
     */
    public static function get($key, $groupId = false, $shopId = false)
    {
        if (self::getConfigBehavior($key) === self::BEHAVIOR_ALL) {
            return Configuration::get($key, null, null, null);
        }

        if ($groupId === false) {
            $groupId = Shop::getContextShopGroupID(true);
        }
        if ($shopId === false) {
            $shopId = Shop::getContextShopID(true);
        }
        $value = Configuration::get($key, null, $groupId, $shopId);
        // if no value set, try to get value set in older module version, but not for another shop
        if ($value === false && $groupId && $shopId) {
            $value = Configuration::get($key, null, $groupId);
        }
        if ($value === false && $groupId) {
            $value = Configuration::get($key);
        }

        return $value;
    }

    /**
     * @param array $keys
     * @return array
     */
    public static function getMultiple($keys)
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = self::get($key);
        }

        return $results;
    }

    /**
     * @param string $key
     * @param mixed $values
     * @return bool
     */
    public static function update($key, $values)
    {
        if (self::getConfigBehavior($key) === self::BEHAVIOR_ALL) {
            // Shop group id and shop id is 0, which is saved as null. Passing null makes PS load active ones,
            // that is not desired. Empty string would work the same way.
            return Configuration::updateValue($key, $values, false, 0, 0);
        }

        return Configuration::updateValue($key, $values);
    }

    /**
     * @return false|string
     */
    public function getApiPass()
    {
        return self::get('PACKETERY_APIPASS');
    }

    /**
     * @return false|string
     */
    public function getApiKey()
    {
        $apiPass = $this->getApiPass();
        if ($apiPass === false) {
            return false;
        }

        return self::getApiKeyFromApiPass($apiPass);
    }

    /**
     * @param string $apiPass
     * @return false|string
     */
    public static function getApiKeyFromApiPass($apiPass)
    {
        return substr($apiPass, 0, 16);
    }

    /**
     * @return string|false
     */
    public function getBackendLanguage()
    {
        $employee = Context::getContext()->employee;
        return Language::getIsoById($employee ? $employee->id_lang : Configuration::get('PS_LANG_DEFAULT'));
    }

    /**
     * @param string $key
     * @return string
     */
    private static function getConfigBehavior($key)
    {
        if (isset(self::$configBehavior[$key])) {
            return self::$configBehavior[$key];
        }

        return self::BEHAVIOR_ALL;
    }

}
