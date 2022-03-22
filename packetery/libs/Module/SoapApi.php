<?php

namespace Packetery\Module;

use Packetery;
use Packetery\Exceptions\PacketInfoException;
use Packetery\Exceptions\SenderGetReturnRoutingException;
use Packetery\Order\OrderRepository;
use Packetery\Response\PacketCarrierNumber;
use Packetery\Tools\ConfigHelper;
use Packetery\Tools\Logger;
use Packetery\Tools\MessageManager;
use SoapClient;
use SoapFault;

class SoapApi
{
    const API_WSDL_URL = 'https://www.zasilkovna.cz/api/soap-php-bugfix.wsdl';

    /**
     * @var Packetery
     */
    private $module;
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    public function __construct(Packetery $module, ConfigHelper $configHelper)
    {
        $this->module = $module;
        $this->configHelper = $configHelper;
    }

    /**
     * @param string $senderIndication
     * @return array with 2 return routing strings for a sender specified by $senderIndication.
     * @throws SenderGetReturnRoutingException
     */
    public function senderGetReturnRouting($senderIndication)
    {
        $client = new SoapClient(self::API_WSDL_URL);
        try {
            $response = $client->senderGetReturnRouting($this->configHelper->getApiPass(), $senderIndication);
            return $response->routingSegment;
        } catch (SoapFault $e) {
            throw new SenderGetReturnRoutingException($e->getMessage(), isset($e->detail->SenderNotExists));
        }
    }

    /**
     * @param string $packetId
     * @return array
     * @throws PacketInfoException
     */
    public function getTrackingUrl($packetId)
    {
        $client = new SoapClient(self::API_WSDL_URL);
        try {
            // get PacketInfoResult
            $response = $client->packetInfo($this->configHelper->getApiPass(), $packetId);
            if (
                !empty($response->courierInfo) &&
                isset($response->courierInfo->courierInfoItem, $response->courierInfo->courierInfoItem->courierTrackingUrls)
            ) {
                return [
                    $response->courierInfo->courierInfoItem->courierNumbers->courierNumber,
                    $response->courierInfo->courierInfoItem->courierTrackingUrls->courierTrackingUrl->url,
                ];
            }
            return [null, null];
        } catch (SoapFault $e) {
            throw new PacketInfoException($e->getMessage(), isset($e->detail->SenderNotExists));
        }
    }

    /**
     * Requests carrier number for a packet.
     * @param string $packetId
     * @return PacketCarrierNumber
     */
    public function packetCarrierNumber($packetId)
    {
        $response = new PacketCarrierNumber();
        try {
            $soapClient = new SoapClient(self::API_WSDL_URL);
            $number = $soapClient->packetCourierNumber($this->configHelper->getApiPass(), $packetId);
            $response->setNumber($number);
        } catch (SoapFault $exception) {
            $response->setFault($this->getFaultIdentifier($exception));
            $response->setFaultString($exception->faultstring);
        }

        return $response;
    }

    /**
     * @throws \ReflectionException
     * @throws Packetery\Exceptions\DatabaseException
     */
    public function getPacketIdsWithCarrierNumbers($packets)
    {
        $result = [];
        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->module->diContainer->get(OrderRepository::class);
        /** @var Logger $logger */
        $logger = $this->module->diContainer->get(Logger::class);
        /** @var MessageManager $messageManager */
        $messageManager = $this->module->diContainer->get(MessageManager::class);
        foreach ($packets as $orderId => $packetId) {
            $orderCarrierNumber = $orderRepository->getCarrierNumber($orderId);
            if (!$orderCarrierNumber) {
                $response = $this->packetCarrierNumber($packetId);
                if ($response->hasFault()) {
                    if ($response->hasWrongPassword()) {
                        $messageManager->setMessage('warning', $this->module->l('Packeta API password is not set.', 'soapapi'));
                        return $result;
                    }
                    $logger->logToFile(sprintf('Error while retrieving carrier number for order %s: %s', $packetId, $response->getFaultString()));
                    continue;
                }
                $orderRepository->setCarrierNumber($orderId, $response->getNumber());
                $orderCarrierNumber = $response->getNumber();
            }

            $result[] = [
                'packetId' => $packetId,
                'courierNumber' => $orderCarrierNumber,
            ];
        }

        return $result;
    }

    /**
     * Gets fault identifier from SoapFault exception.
     * @param SoapFault $exception
     * @return int|string
     */
    private function getFaultIdentifier(SoapFault $exception)
    {
        if (isset($exception->detail)) {
            return array_keys(get_object_vars($exception->detail))[0];
        }

        return $exception->faultstring;
    }

}
