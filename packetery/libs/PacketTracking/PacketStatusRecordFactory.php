<?php

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
