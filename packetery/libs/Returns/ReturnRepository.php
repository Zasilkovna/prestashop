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

use Packetery\Exceptions\DatabaseException;
use Packetery\Tools\DbTools;

class ReturnRepository
{
    /** @var string */
    public static $tableName = 'packetery_return';

    /** @var DbTools */
    private $dbTools;

    public function __construct(DbTools $dbTools)
    {
        $this->dbTools = $dbTools;
    }

    /**
     * @throws DatabaseException
     */
    public function insert(
        int $idOrder,
        string $claimId,
        ?string $claimPassword,
        string $status,
        string $source
    ): bool {
        return $this->dbTools->insert(
            self::$tableName,
            [
                'id_order' => $idOrder,
                'claim_id' => $this->dbTools->db->escape($claimId),
                'claim_password' => $claimPassword === null
                    ? null
                    : $this->dbTools->db->escape($claimPassword),
                'status' => $this->dbTools->db->escape($status),
                'source' => $this->dbTools->db->escape($source),
                'date_add' => (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
            ],
            true // store a missing claim password as SQL NULL, not ''
        );
    }

    /**
     * Inserts a return that waits for e-shop approval: it has not been sent to Packeta yet, so it
     * carries no claim id/password. The contact the customer entered in the form is stored so
     * approval can build the claim from it. Approval later fills the claim id/password
     * via setClaimApproved().
     *
     * @throws DatabaseException
     */
    public function insertPending(int $idOrder, string $source, ?string $email = null, ?string $phone = null): bool
    {
        return $this->dbTools->insert(
            self::$tableName,
            [
                'id_order' => $idOrder,
                'claim_id' => null,
                'claim_password' => null,
                'status' => $this->dbTools->db->escape(ReturnEntity::STATUS_PENDING),
                'source' => $this->dbTools->db->escape($source),
                'date_add' => (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
                'email' => ($email === null || $email === '') ? null : $this->dbTools->db->escape($email),
                'phone' => ($phone === null || $phone === '') ? null : $this->dbTools->db->escape($phone),
            ],
            true // store the empty claim id/password/contact as SQL NULL, not ''
        );
    }

    /**
     * Fills the Packeta claim id/password on an approved pending return and flips it to "created".
     *
     * @throws DatabaseException
     */
    public function setClaimApproved(int $idReturn, string $claimId, ?string $claimPassword): bool
    {
        return $this->dbTools->update(
            self::$tableName,
            [
                'claim_id' => $this->dbTools->db->escape($claimId),
                'claim_password' => $claimPassword === null
                    ? null
                    : $this->dbTools->db->escape($claimPassword),
                'status' => $this->dbTools->db->escape(ReturnEntity::STATUS_CREATED),
            ],
            '`id_return` = ' . $idReturn,
            0,
            true // keep a missing claim password as SQL NULL, not ''
        );
    }

    /**
     * Number of returns already recorded for an order, in any state. Used by the approval policy:
     * the first return is auto-processed, any further one waits for approval — regardless of what
     * happened to the earlier ones (a return the e-shop cancelled/rejected still counts, so a repeat
     * attempt does not slip through automatically).
     *
     * @throws DatabaseException
     */
    public function countByOrderId(int $idOrder): int
    {
        $count = $this->dbTools->getValue('
            SELECT COUNT(*)
            FROM `' . _DB_PREFIX_ . self::$tableName . '`
            WHERE `id_order` = ' . $idOrder);

        return (int) $count;
    }

    /**
     * @return ReturnEntity[]
     *
     * @throws DatabaseException
     */
    public function getByOrderId(int $idOrder): array
    {
        $rows = $this->dbTools->getRows('
            SELECT `id_return`, `id_order`, `claim_id`, `claim_password`, `status`, `source`, `date_add`, `email`, `phone`
            FROM `' . _DB_PREFIX_ . self::$tableName . '`
            WHERE `id_order` = ' . $idOrder . '
            ORDER BY `id_return` ASC');

        $returns = [];
        foreach ($rows as $row) {
            $returns[] = ReturnEntity::fromDbRow($row);
        }

        return $returns;
    }

    /**
     * Latest still-active (created) return of an order, or null when there is none.
     *
     * @throws DatabaseException
     */
    public function getActiveByOrderId(int $idOrder): ?ReturnEntity
    {
        $row = $this->dbTools->getRow('
            SELECT `id_return`, `id_order`, `claim_id`, `claim_password`, `status`, `source`, `date_add`, `email`, `phone`
            FROM `' . _DB_PREFIX_ . self::$tableName . '`
            WHERE `id_order` = ' . $idOrder . '
              AND `status` = "' . $this->dbTools->db->escape(ReturnEntity::STATUS_CREATED) . '"
            ORDER BY `id_return` DESC');

        if (!is_array($row) || $row === []) {
            return null;
        }

        return ReturnEntity::fromDbRow($row);
    }

    /**
     * Latest return of an order that is waiting for e-shop approval, or null when there is none.
     *
     * @throws DatabaseException
     */
    public function getPendingByOrderId(int $idOrder): ?ReturnEntity
    {
        $row = $this->dbTools->getRow('
            SELECT `id_return`, `id_order`, `claim_id`, `claim_password`, `status`, `source`, `date_add`, `email`, `phone`
            FROM `' . _DB_PREFIX_ . self::$tableName . '`
            WHERE `id_order` = ' . $idOrder . '
              AND `status` = "' . $this->dbTools->db->escape(ReturnEntity::STATUS_PENDING) . '"
            ORDER BY `id_return` DESC');

        if (!is_array($row) || $row === []) {
            return null;
        }

        return ReturnEntity::fromDbRow($row);
    }

    /**
     * @throws DatabaseException
     */
    public function getById(int $idReturn): ?ReturnEntity
    {
        $row = $this->dbTools->getRow('
            SELECT `id_return`, `id_order`, `claim_id`, `claim_password`, `status`, `source`, `date_add`, `email`, `phone`
            FROM `' . _DB_PREFIX_ . self::$tableName . '`
            WHERE `id_return` = ' . $idReturn);

        if (!is_array($row) || $row === []) {
            return null;
        }

        return ReturnEntity::fromDbRow($row);
    }

    /**
     * @throws DatabaseException
     */
    public function updateStatus(int $idReturn, string $status): bool
    {
        return $this->dbTools->update(
            self::$tableName,
            ['status' => $this->dbTools->db->escape($status)],
            '`id_return` = ' . $idReturn
        );
    }

    public function getDropTableSql(): string
    {
        return 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . self::$tableName . '`;';
    }

    public function getCreateTableSql(): string
    {
        return 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::$tableName . '` (
            `id_return` int NOT NULL AUTO_INCREMENT,
            `id_order` int NOT NULL,
            `claim_id` varchar(15) NULL,
            `claim_password` varchar(10) NULL,
            `status` varchar(20) NOT NULL,
            `source` varchar(20) NOT NULL,
            `date_add` datetime NOT NULL,
            `email` varchar(255) NULL,
            `phone` varchar(255) NULL,
            PRIMARY KEY (`id_return`),
            KEY `idx_id_order` (`id_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
    }
}
