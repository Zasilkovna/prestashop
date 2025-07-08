<?php

namespace Packetery\Response;

trait InvalidPacketIdsTrait
{
    /**
     * @var string[]
     */
    private $invalidPacketIds = [];

    /**
     * @param string[] $invalidPacketIds
     * @return void
     */
    public function setInvalidPacketIds(array $invalidPacketIds)
    {
        $this->invalidPacketIds = $invalidPacketIds;
    }

    /**
     * @param string $packetId
     * @return bool|null
     */
    public function hasInvalidPacketId($packetId)
    {
        if (in_array($packetId, $this->invalidPacketIds, true)) {
            return true;
        }

        return null;
    }
}
