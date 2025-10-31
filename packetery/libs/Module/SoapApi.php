<?php

namespace Packetery\Module;

use Packetery;
use Packetery\Exceptions\SenderGetReturnRoutingException;
use Packetery\Log\LogRepository;
use Packetery\Order\OrderRepository;
use Packetery\Response\PacketCarrierNumber;
use Packetery\Response\PacketInfo;
use Packetery\Tools\ConfigHelper;
use Packetery\Tools\MessageManager;
use ReflectionException;
use SoapClient;
use SoapFault;

class SoapApi
{
    const WSDL_URL = 'http://www.zasilkovna.cz/api/soap-php-bugfix.wsdl';

    /**
     * @var Packetery
     */
    private $module;
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @param Packetery $module
     * @param ConfigHelper $configHelper
     */
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
        $client = new SoapClient(self::WSDL_URL);
        try {
            $response = $client->senderGetReturnRouting($this->configHelper->getApiPass(), $senderIndication);
            return $response->routingSegment;
        } catch (SoapFault $e) {
            throw new SenderGetReturnRoutingException($e->getMessage(), isset($e->detail->SenderNotExists));
        }
    }

    /**
     * @param string $packetId
     * @return PacketInfo
     */
    public function getPacketInfo($packetId)
    {
        $packetInfo = new PacketInfo();
        try {
            $client = new SoapClient(self::WSDL_URL);
            // get PacketInfoResult
            $response = $client->packetInfo($this->configHelper->getApiPass(), $packetId);
            if (
                !empty($response->courierInfo) &&
                isset($response->courierInfo->courierInfoItem, $response->courierInfo->courierInfoItem->courierTrackingUrls)
            ) {
                $packetInfo->setNumber($response->courierInfo->courierInfoItem->courierNumbers->courierNumber);
                $packetInfo->setTrackingLink($this->getTrackingUrlInProperLanguage(
                    $response->courierInfo->courierInfoItem->courierTrackingUrls->courierTrackingUrl
                ));
            }
        } catch (SoapFault $exception) {
            $packetInfo->setFault($this->getFaultIdentifier($exception));
            $packetInfo->setFaultString($exception->faultstring);
        }
        return $packetInfo;
    }

    /**
     * @param object|array $courierTrackingUrl
     * @return string|null
     */
    public function getTrackingUrlInProperLanguage($courierTrackingUrl)
    {
        if (is_object($courierTrackingUrl)) {
            return $courierTrackingUrl->url;
        }
        if (is_array($courierTrackingUrl)) {
            $urlPreferred = null;
            $urlEn = null;
            $preferredLang = $this->configHelper->getBackendLanguage();
            foreach ($courierTrackingUrl as $courierTrackingUrlObject) {
                if ($courierTrackingUrlObject->lang === $preferredLang) {
                    $urlPreferred = $courierTrackingUrlObject->url;
                }
                if ($courierTrackingUrlObject->lang === 'en') {
                    $urlEn = $courierTrackingUrlObject->url;
                }
            }
            if ($urlPreferred) {
                return $urlPreferred;
            }
            if ($urlEn) {
                return $urlEn;
            }
            return $courierTrackingUrl[0]->url;
        }
        return null;
    }

    /**
     * Requests carrier number for a packet.
     *
     * @param string $packetId
     * @return PacketCarrierNumber
     */
    public function packetCarrierNumber($packetId)
    {
        $response = new PacketCarrierNumber();
        try {
            $soapClient = new SoapClient(self::WSDL_URL);
            $number = $soapClient->packetCourierNumber($this->configHelper->getApiPass(), $packetId);
            $response->setNumber($number);
        } catch (SoapFault $exception) {
            $response->setFault($this->getFaultIdentifier($exception));
            $response->setFaultString($exception->faultstring);
        }

        return $response;
    }

    /**
     * @param array $packets
     * @return array
     * @throws Packetery\Exceptions\DatabaseException
     * @throws ReflectionException
     */
    public function getPacketIdsWithCarrierNumbers($packets)
    {
        $result = [];
        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->module->diContainer->get(OrderRepository::class);
        /** @var LogRepository $logRepository */
        $logRepository = $this->module->diContainer->get(LogRepository::class);
        /** @var MessageManager $messageManager */
        $messageManager = $this->module->diContainer->get(MessageManager::class);
        foreach ($packets as $orderId => $packetId) {
            $orderCarrierNumber = $orderRepository->getCarrierNumber($orderId);
            if (!$orderCarrierNumber) {
                $response = $this->packetCarrierNumber($packetId);
                if ($response->hasFault()) {
                    if ($response->hasWrongPassword()) {
                        $messageManager->setMessage('warning', $this->module->getTranslator()->trans('Used API password is not valid.', [], 'Modules.Packetery.Soapapi'));
                        return $result;
                    }
                    $logRepository->insertRow(
                        LogRepository::ACTION_CARRIER_TRACKING_NUMBER,
                        [
                            'packetId' => $packetId,
                            'error' => $response->getFaultString(),
                        ],
                        LogRepository::STATUS_ERROR,
                        $orderId
                    );

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
     *
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

    /**
     * @param string $packetId
     * @return array|string
     */
    public function getPacketTracking($packetId)
    {
        $client = new SoapClient(self::WSDL_URL);
        try {
            $response = $client->packetTracking($this->configHelper->getApiPass(), $packetId);
        } catch (SoapFault $exception) {
            return $exception->faultstring;
        }
        return $response;
    }

    /**
     * @param array $packets
     * @param string $format
     * @param string $offset
     * @return Packetery\Response\PacketsLabelsPdfResponse
     */
    public function getPacketsLabelsPdf(array $packets, $format, $offset)
    {
        $response = new Packetery\Response\PacketsLabelsPdfResponse();
        try {
            $soapClient = new SoapClient(self::WSDL_URL);
            $pdfContents = $soapClient->packetsLabelsPdf($this->configHelper->getApiPass(), $packets, $format, $offset);
            $response->setPdfContents($pdfContents);
        } catch (SoapFault $exception) {
            $response->setFault($this->getFaultIdentifier($exception));
            $response->setFaultString($exception->faultstring);

            if ($response->hasPacketIdsFault()) {
                $response->setInvalidPacketIds((array) $exception->detail->PacketIdsFault->ids->packetId);
            }
        }

        return $response;
    }

    /**
     * @param array $packetsEnhanced
     * @param string $format
     * @param string $offset
     * @return Packetery\Response\PacketsCourierLabelsPdfResponse
     */
    public function getPacketsCourierLabelsPdf(array $packetsEnhanced, $format, $offset)
    {
        $response = new Packetery\Response\PacketsCourierLabelsPdfResponse();
        try {
            $soapClient = new SoapClient(self::WSDL_URL);
            $pdfContents = $soapClient->packetsCourierLabelsPdf($this->configHelper->getApiPass(), $packetsEnhanced, $offset, $format);
            $response->setPdfContents($pdfContents);
        } catch (SoapFault $exception) {
            $response->setFault($this->getFaultIdentifier($exception));
            $response->setFaultString($exception->faultstring);
        }

        if ($response->hasInvalidCourierNumberFault() && count($packetsEnhanced) === 1) {
            $response->setInvalidCourierNumbers(array_column($packetsEnhanced, 'courierNumber'));
        }
        if ($response->hasPacketIdFault() && count($packetsEnhanced) === 1) {
            $response->setInvalidPacketIds(array_column($packetsEnhanced, 'packetId'));
        }

        return $response;
    }
}
