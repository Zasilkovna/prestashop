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

use Packetery\Tools\DbTools;

class PacketTrackingRepository
{
    public static $tableName = 'packetery_packet_status';

    /**
     * @var DbTools
     */
    private $dbTools;

    /**
     * @param DbTools $dbTools
     */
    public function __construct(DbTools $dbTools)
    {
        $this->dbTools = $dbTools;
    }

    /**
     * @return string
     */
    private function getPrefixedTableName()
    {
        return _DB_PREFIX_ . self::$tableName;
    }

    /**
     * @param int $orderId
     * @param string $packetId
     * @param string $eventDatetime
     * @param int $statusCode
     * @param string $statusText
     *
     * @return bool
     */
    public function insert($orderId, $packetId, $eventDatetime, $statusCode, $statusText)
    {
        return $this->dbTools->insert(
            self::$tableName,
            [
                'id_order' => $orderId,
                'packet_id' => $packetId,
                'event_datetime' => $eventDatetime,
                'status_code' => $statusCode,
                'status_text' => $statusText,
            ]
        );
    }

    /**
     * @param int $orderId
     * @param string $packetId
     *
     * @return int|null
     */
    public function getLastStatusCodeByOrderAndPacketId($orderId, $packetId)
    {
        $statusCode = $this->dbTools->getValue('SELECT `status_code` FROM `' . $this->getPrefixedTableName() . '`
            WHERE `id_order` = ' . (int) $orderId . ' AND `packet_id` = "' . $this->dbTools->db->escape($packetId) . '"
            ORDER BY `event_datetime` DESC');

        if ($statusCode === false || !is_numeric($statusCode)) {
            return null;
        }

        return (int) $statusCode;
    }

    /**
     * @return string
     */
    public function getDropTableSql()
    {
        return 'DROP TABLE IF EXISTS `' . $this->getPrefixedTableName() . '`;';
    }

    /**
     * @return string
     */
    public function getCreateTableSql()
    {
        return 'CREATE TABLE `' . $this->getPrefixedTableName() . '` (
          `id` int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `id_order` int unsigned NOT NULL,
          `packet_id` varchar(15) NOT NULL,
          `event_datetime` datetime NOT NULL,
          `status_code` tinyint unsigned NOT NULL,
          `status_text` text NOT NULL,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          KEY `id_order` (`id_order`),
          KEY `packet_id` (`packet_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
    }

    /**
     * @param int $orderId
     *
     * @return bool
     */
    public function delete($orderId)
    {
        return $this->dbTools->delete(self::$tableName, '`id_order` = ' . (int) $orderId);
    }

    /**
     * @param int $orderId
     *
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     */
    public function getPacketStatusesByOrderId($orderId)
    {
        $sql = 'SELECT `id`, `id_order`, `packet_id`, `event_datetime`, `status_code`, `status_text` 
                FROM `' . $this->getPrefixedTableName() . '`
                WHERE `id_order` = ' . (int) $orderId;

        return $this->dbTools->getRows($sql);
    }
}
