<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Order;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Exceptions\ApiClientException;
use Packetery\Exceptions\DatabaseException;
use Packetery\Log\LogRepository;
use Packetery\Module\SoapApi;

class ConsignPasswordProvider
{
    /** @var SoapApi */
    private $soapApi;

    /** @var LogRepository */
    private $logRepository;

    public function __construct(SoapApi $soapApi, LogRepository $logRepository)
    {
        $this->soapApi = $soapApi;
        $this->logRepository = $logRepository;
    }

    /**
     * Returns the consign password for the given packet from Packeta API.
     * On API fault the failure is logged into the Packeta API log and an ApiClientException is thrown.
     *
     * @throws ApiClientException when the SOAP request fails
     * @throws DatabaseException when the Packeta log write fails
     */
    public function fetchFromApi(int $orderId, string $packetId): ?string
    {
        $response = $this->soapApi->getPacketInfo($packetId);

        if ($response->hasFault()) {
            $this->logRepository->insertRow(
                LogRepository::ACTION_PACKET_INFO,
                [
                    'request' => ['packetId' => $packetId],
                    'response' => [
                        'fault' => $response->getFault(),
                        'faultString' => $response->getFaultString(),
                    ],
                ],
                LogRepository::STATUS_ERROR,
                $orderId
            );

            throw new ApiClientException("packetInfo() failed for order ID {$orderId}: {$response->getFaultString()}");
        }

        return $response->getConsignPassword();
    }
}
