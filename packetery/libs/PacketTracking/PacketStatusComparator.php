<?php

namespace Packetery\PacketTracking;

class PacketStatusComparator
{
    /**
     * Compare records from API and database using hashes
     *
     * @param PacketStatusRecord[] $apiPacketStatuses
     * @param PacketStatusRecord[] $databasePacketStatuses
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
