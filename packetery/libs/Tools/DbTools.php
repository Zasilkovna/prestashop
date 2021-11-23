<?php

namespace Packetery\Tools;

use Db;
use PrestaShopException;
use PrestaShopLogger;

class DbTools
{
    /** @var Db */
    private $db;

    /**
     * @param Db $db
     */
    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @param array $queries
     * @param string $logMessage
     * @param bool $returnFalseOnException
     * @return bool
     */
    public function executeQueries($queries, $logMessage, $returnFalseOnException = false)
    {
        foreach ($queries as $query) {
            try {
                $result = $this->db->execute($query);
                if ($result === false) {
                    return false;
                }
            } catch (PrestaShopException $exception) {
                PrestaShopLogger::addLog($logMessage . ' ' .
                    $exception->getMessage(), 3, null, null, null, true);
                if ($returnFalseOnException) {
                    return false;
                }
            }
        }

        return true;
    }
}
