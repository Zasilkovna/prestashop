<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Module;

use Packetery\Exceptions\EmptyArrayToJsonConvertException;
use Packetery\Exceptions\FailedToConvertJsonException;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Helper
{
    public const TRACKING_URL = 'https://tracking.packeta.com/Z%s';

    /**
     * @param string $packetId
     *
     * @return string
     */
    public static function getTrackingUrl($packetId)
    {
        return sprintf(self::TRACKING_URL, rawurlencode($packetId));
    }

    /**
     * @return string
     */
    public static function getBaseUri()
    {
        return __PS_BASE_URI__ === '/' ? '' : \Tools::substr(__PS_BASE_URI__, 0, \Tools::strlen(__PS_BASE_URI__) - 1);
    }

    /**
     * @param string $data
     *
     * @return mixed|null
     */
    public static function unserialize(
        $data
    ) {
        return unserialize(
            $data,
            [
                'allowed_classes' => false,
            ]
        );
    }

    /**
     * Transforms non-empty array to JSON string
     *
     * @param array<int|string, mixed> $data
     *
     * @return string
     *
     * @throws EmptyArrayToJsonConvertException
     * @throws FailedToConvertJsonException
     */
    public static function transformArrayToJson(array $data): string
    {
        if ($data === []) {
            throw new EmptyArrayToJsonConvertException('Function transformArrayToJson got empty array.');
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new FailedToConvertJsonException(json_last_error_msg());
        }

        return $json;
    }

    /**
     * Recursively escapes all string values in common array
     * Non-string values (int, float, bool, null) are preserved.
     *
     * @param array<int|string, mixed> $array
     *
     * @return array<int|string, mixed>
     */
    public static function escapeArray(array $array): array
    {
        $resultArray = $array;
        array_walk_recursive($resultArray, function (&$value) {
            if (is_string($value)) {
                $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5);
            }
        });

        return $resultArray;
    }
}
