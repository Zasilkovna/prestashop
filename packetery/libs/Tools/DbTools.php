<?php

namespace Packetery\Tools;

// PrestaShopDatabaseException is extended from PrestaShopException
use Db;
use Packetery\Exceptions\DatabaseException;
use PrestaShopException;
use PrestaShopLogger;

class DbTools
{
    /** @var Db */
    private $db;

    /** @var Logger */
    private $logger;

    /**
     * @param Db $db
     * @param Logger $logger
     */
    public function __construct(Db $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * @param array $queries
     * @param string $logMessage
     * @param bool $returnFalseOnException true in Installer, false in Uninstaller
     * @return bool
     */
    public function executeQueries($queries, $logMessage, $returnFalseOnException = false)
    {
        foreach ($queries as $query) {
            try {
                $this->execute($query);
            } catch (DatabaseException $exception) {
                // there are more details in Packeta log
                PrestaShopLogger::addLog($logMessage . ' ' .
                    $exception->getMessage(), 3, null, null, null, true);
                if ($returnFalseOnException) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param string|null $methodName
     * @param mixed $result
     * @param string $query
     * @throws DatabaseException
     */
    private function logAndThrow($methodName, $result, $query)
    {
        if ($result instanceof PrestaShopException) {
            $this->logger->logToFile($result->getMessage() . ', query: ' . $query);
            throw new DatabaseException($result->getMessage() . ', see details in Packeta log');
        } elseif ($result === false) {
            // in case DEV mode is turned off, methods return false instead of raising an exception
            $message = $methodName . ' method returned false';
            $this->logger->logToFile($message . ', query: ' . $query);
            throw new DatabaseException($message . ', see details in Packeta log');
        }
    }

    /**
     * @param string $sql SQL query
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     * @throws DatabaseException
     */
    public function getRows($sql)
    {
        try {
            $result = $this->db->executeS($sql);
        } catch (PrestaShopException $exception) {
            $this->logAndThrow(null, $exception, $sql);
        }
        $this->logAndThrow('executeS', $result, $sql);
        return $result;
    }

    /**
     * @param string $sql SQL query
     * @return array|bool|object|null
     * @throws DatabaseException
     */
    public function getRow($sql)
    {
        try {
            $result = $this->db->getRow($sql);
        } catch (PrestaShopException $exception) {
            $this->logAndThrow(null, $exception, $sql);
        }
        // in this case, false is ok
        return $result;
    }

    /**
     * Simplified fork of Db::getValue
     * @param string $sql SQL query
     * @return false|string|null
     * @throws DatabaseException
     */
    public function getValue($sql)
    {
        $result = $this->getRow($sql);
        if (!$result) {
            return false;
        }
        return array_shift($result);
    }

    /**
     * @param string $sql
     * @param bool $useCache
     * @return bool
     * @throws DatabaseException
     */
    public function execute($sql, $useCache = true)
    {
        try {
            $result = $this->db->execute($sql, $useCache);
        } catch (PrestaShopException $exception) {
            $this->logAndThrow(null, $exception, $sql);
        }
        $this->logAndThrow('execute', $result, $sql);
        return $result;
    }

    /**
     * @param string $table
     * @param string $where
     * @param int $limit
     * @param bool $useCache
     * @param bool $addPrefix
     * @return bool
     * @throws DatabaseException
     */
    public function delete($table, $where = '', $limit = 0, $useCache = true, $addPrefix = true)
    {
        $queryForLog = 'table ' . $table . '; where ' . $where;
        try {
            $result = $this->db->delete($table, $where, $limit, $useCache, $addPrefix);
        } catch (PrestaShopException $exception) {
            $this->logAndThrow(null, $exception, $queryForLog);
        }
        $this->logAndThrow('delete', $result, $queryForLog);
        return $result;
    }

    /**
     * @param string $table
     * @param array $data
     * @param false $nullValues
     * @param bool $useCache
     * @param int $type
     * @param bool $addPrefix
     * @return bool
     * @throws DatabaseException
     */
    public function insert($table, $data, $nullValues = false, $useCache = true, $type = Db::INSERT, $addPrefix = true)
    {
        $queryForLog = 'table ' . $table . '; data ' . serialize($data);
        try {
            $result = $this->db->insert($table, $data, $nullValues, $useCache, $type, $addPrefix);
        } catch (PrestaShopException $exception) {
            $this->logAndThrow(null, $exception, $queryForLog);
        }
        $this->logAndThrow('insert', $result, $queryForLog);
        return $result;
    }

    /**
     * @param string $table
     * @param array $data
     * @param string $where
     * @param int $limit
     * @param false $nullValues
     * @param bool $useCache
     * @param bool $addPrefix
     * @return bool
     * @throws DatabaseException
     */
    public function update($table, $data, $where = '', $limit = 0, $nullValues = false, $useCache = true, $addPrefix = true)
    {
        $queryForLog = 'table ' . $table . '; data ' . serialize($data) . '; where ' . $where;
        try {
            $result = $this->db->update($table, $data, $where, $limit, $nullValues, $useCache, $addPrefix);
        } catch (PrestaShopException $exception) {
            $this->logAndThrow(null, $exception, $queryForLog);
        }
        $this->logAndThrow('update', $result, $queryForLog);
        return $result;
    }
}
