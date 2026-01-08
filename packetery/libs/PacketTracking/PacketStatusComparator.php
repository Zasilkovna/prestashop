<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\PacketTracking;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PacketStatusComparator
{
    /**
     * Compare records from API and database using hashes
     *
     * @param PacketStatusRecord[] $apiPacketStatuses
     * @param PacketStatusRecord[] $databasePacketStatuses
     *
     * @return bool
     */
    public function isDifferenceBetweenApiAndDatabase(array $apiPacketStatuses, array $databasePacketStatuses)
    {
        $databasePacketStatusHashes = [];

        foreach ($databasePacketStatuses as $databasePacketStatus) {
            $databasePacketStatusHashes[$databasePacketStatus->getHash()] = true;
        }

        foreach ($apiPacketStatuses as $apiPacketStatus) {
            $hash = $apiPacketStatus->getHash();

            if (!isset($databasePacketStatusHashes[$hash])) {
                return true;
            }
        }

        return false;
    }
}
