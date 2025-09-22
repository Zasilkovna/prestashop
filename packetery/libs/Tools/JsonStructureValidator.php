<?php
/**
 * 2017 Zlab Solutions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
 *  @copyright Since 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
namespace Packetery\Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class JsonStructureValidator
 *
 * This class is used to validate JSON against given structure.
 *
 * json example: {"plugin": {"version": "1.0.0", "downloadUrl": "https://example.com/download"}}
 * structure example: ['plugin' => ['version' => 'string', 'downloadUrl' => 'string']]
 */
class JsonStructureValidator
{
    /**
     * @param string $json
     * @param array $structure
     *
     * @return bool
     */
    public function isValid($json, array $structure)
    {
        $json = json_decode($json, true);
        if ($json === null) {
            return false;
        }

        return $this->isStructureValid($json, $structure);
    }

    /**
     * @param array $decodedJson
     * @param array $structure
     *
     * @return bool
     */
    public function isStructureValid(array $decodedJson, array $structure)
    {
        foreach ($structure as $key => $value) {
            if (!isset($decodedJson[$key])) {
                return false;
            }
            if (is_array($value)) {
                if (!$this->isStructureValid($decodedJson[$key], $value)) {
                    return false;
                }
            } elseif (!$this->isValidType($decodedJson[$key], $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed $value
     * @param string $type
     *
     * @return bool
     */
    private function isValidType($value, $type)
    {
        switch ($type) {
            case 'string':
                return is_string($value);
            case 'int':
                return is_int($value);
            case 'float':
                return is_float($value);
            case 'bool':
                return is_bool($value);
            case 'array':
                return is_array($value);
            case 'object':
                return is_object($value);
            default:
                return false;
        }
    }
}
