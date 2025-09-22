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

namespace Packetery\PacketTracking;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PacketStatusRecordFactory
{
    /**
     * Creates an instance from API data
     *
     * @param array $apiData
     *
     * @return PacketStatusRecord
     */
    public static function createFromSoapApi(array $apiData)
    {
        return new PacketStatusRecord(
            new \DateTimeImmutable(isset($apiData['dateTime']) ? (string) $apiData['dateTime'] : 'now'),
            isset($apiData['statusCode']) ? (string) $apiData['statusCode'] : '',
            isset($apiData['statusText']) ? (string) $apiData['statusText'] : ''
        );
    }

    /**
     * Creates an instance from a database record
     *
     * @param array $databaseRow
     *
     * @return PacketStatusRecord
     */
    public static function createFromDatabase(array $databaseRow)
    {
        return new PacketStatusRecord(
            new \DateTimeImmutable(isset($databaseRow['event_datetime']) ? (string) $databaseRow['event_datetime'] : 'now'),
            isset($databaseRow['status_code']) ? (string) $databaseRow['status_code'] : '',
            isset($databaseRow['status_text']) ? (string) $databaseRow['status_text'] : ''
        );
    }
}
