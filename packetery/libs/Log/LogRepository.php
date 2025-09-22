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
namespace Packetery\Log;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Exceptions\DatabaseException;
use Packetery\Tools\DbTools;

class LogRepository
{
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';

    const ACTION_PACKET_SENDING = 'packet-sending';
    const ACTION_LABEL_PRINT = 'label-print';
    const ACTION_SENDER_VALIDATION = 'sender-validation';
    const ACTION_PACKET_TRACKING = 'packet-tracking';

    /** @var DbTools */
    private $dbTools;

    /** @var \Packetery */
    private $module;

    public static $tableName = 'packetery_log';

    /**
     * @param DbTools $dbTools
     * @param \Packetery $module
     */
    public function __construct(DbTools $dbTools, \Packetery $module)
    {
        $this->dbTools = $dbTools;
        $this->module = $module;
    }

    /**
     * @param string $action
     *
     * @return string
     */
    public function getTranslatedAction($action)
    {
        $translations = $this->getActionTranslations();
        if (!isset($translations[$action])) {
            return $action;
        }

        return $translations[$action];
    }

    /**
     * @return array<string, string>
     * @return void
     */
    public function getActionTranslations()
    {
        return [
            self::ACTION_LABEL_PRINT => $this->module->getTranslator()->trans('Label print', [], 'Modules.Packetery.Logrepository'),
            self::ACTION_SENDER_VALIDATION => $this->module->getTranslator()->trans('Sender validation', [], 'Modules.Packetery.Logrepository'),
            self::ACTION_PACKET_SENDING => $this->module->getTranslator()->trans('Packet sending', [], 'Modules.Packetery.Logrepository'),
            self::ACTION_PACKET_TRACKING => $this->module->getTranslator()->trans('Packet tracking', [], 'Modules.Packetery.Logrepository'),
        ];
    }

    /**
     * @param string $action
     * @param array<string, mixed> $params
     * @param string $status
     * @param string|int|null $orderId
     *
     * @return bool
     *
     * @throws DatabaseException
     * @throws \DateMalformedStringException
     */
    public function insertRow($action, array $params, $status = 'success', $orderId = null)
    {
        return $this->insert(
            [
                'order_id' => $orderId === 0 || $orderId === '0' ? null : $orderId,
                'params' => json_encode($params, JSON_UNESCAPED_UNICODE),
                'status' => $status,
                'action' => $action,
                'date' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * @param array $data
     *
     * @return bool
     *
     * @throws DatabaseException
     */
    public function insert(array $data)
    {
        return $this->dbTools->insert(
            self::$tableName,
            $data
        );
    }

    /**
     * @return string
     */
    private function getPrefixedTableName()
    {
        return _DB_PREFIX_ . self::$tableName;
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
        return 'CREATE TABLE ' . $this->getPrefixedTableName() . ' (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(10) NULL,
            `params` text NOT NULL,
            `status` varchar(20) NOT NULL DEFAULT \'\',
            `action` varchar(45) NOT NULL DEFAULT \'\',
            `date` datetime NOT NULL,
            PRIMARY KEY  (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
    }

    /**
     * @param int $logExpirationDays
     *
     * @throws DatabaseException
     */
    public function purge($logExpirationDays)
    {
        $this->dbTools->delete(self::$tableName, '`date` < DATE_SUB(NOW(), INTERVAL ' . (int) $logExpirationDays . ' DAY)');
    }

    /**
     * @param int|string $orderId
     *
     * @return bool
     */
    public function hasAnyByOrderId($orderId)
    {
        return '1' === $this->dbTools->getValue('SELECT "1" FROM `' . $this->getPrefixedTableName() . '` WHERE order_id = ' . (int) $orderId . ';');
    }
}
