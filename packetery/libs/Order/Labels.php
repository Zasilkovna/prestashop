<?php

namespace Packetery\Order;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Exceptions\LabelPrintException;
use Packetery\Log\LogRepository;
use Packetery\Module\SoapApi;
use Packetery\Response\PacketsCourierLabelsPdfResponse;
use Packetery\Response\PacketsLabelsPdfResponse;
use Packetery\Tools\ConfigHelper;

class Labels
{
    public const TYPE_PACKETA = 'packeta';
    public const TYPE_CARRIER = 'carrier';

    /** @var LogRepository */
    private $logRepository;

    /** @var \Packetery */
    private $module;

    public function __construct(
        LogRepository $logRepository,
        \Packetery $module
    ) {
        $this->logRepository = $logRepository;
        $this->module = $module;
    }

    /**
     * @param array $packets used for packeta labels
     * @param string $type
     * @param array|null $packetsEnhanced used for carrier labels
     * @param int $offset
     * @param bool $fallbackToPacketaLabel
     *
     * @return string
     *
     * @throws LabelPrintException
     */
    public function packetsLabelsPdf(array $packets, $type, $packetsEnhanced = null, $offset = 0, $fallbackToPacketaLabel = false)
    {
        /** @var SoapApi $soapApi */
        $soapApi = $this->module->diContainer->get(SoapApi::class);
        $carrierNumbers = [];
        if (is_array($packetsEnhanced)) {
            $carrierNumbers = array_column($packetsEnhanced, 'courierNumber', 'packetId');
        }

        if ($type === self::TYPE_CARRIER) {
            $format = ConfigHelper::get('PACKETERY_CARRIER_LABEL_FORMAT');
            $response = $soapApi->getPacketsCourierLabelsPdf($packetsEnhanced, $format, $offset);
            if ($fallbackToPacketaLabel === true && $response->hasFault()) {
                $response = $soapApi->getPacketsLabelsPdf(array_values($packets), $format, $offset);
            }
        } else {
            $format = ConfigHelper::get('PACKETERY_LABEL_FORMAT');
            $response = $soapApi->getPacketsLabelsPdf(array_values($packets), $format, $offset);
        }

        if ($response->hasFault()) {
            foreach ($packets as $orderId => $packetNumber) {
                $logProperties = $this->buildLogProperties($packetNumber, $format, $type, $carrierNumbers, $response);
                $logProperties['exception'] = $response->getFaultString();
                $this->logRepository->insertRow(
                    LogRepository::ACTION_LABEL_PRINT,
                    $logProperties,
                    LogRepository::STATUS_ERROR,
                    $orderId
                );
            }

            if ($fallbackToPacketaLabel === true && count($packets) === 1) {
                $message = sprintf(
                    $this->module->l('Label printing for packet %s failed, you can find more information in the Packeta log.', 'labels'),
                    array_shift($packets)
                );
            } elseif ($type === self::TYPE_CARRIER) {
                $message = sprintf(
                    $this->module->l('Carrier label printing failed, you can find more information in the Packeta log. Error: %s', 'labels'),
                    $response->getFaultString()
                );
            } else {
                $message = sprintf(
                    $this->module->l('Label printing failed, you can find more information in the Packeta log. Error: %s', 'labels'),
                    $response->getFaultString()
                );
            }

            throw new LabelPrintException($message);
        }

        foreach ($packets as $orderId => $packetNumber) {
            $logProperties = $this->buildLogProperties($packetNumber, $format, $type, $carrierNumbers, $response);
            $this->logRepository->insertRow(
                LogRepository::ACTION_LABEL_PRINT,
                $logProperties,
                LogRepository::STATUS_SUCCESS,
                $orderId
            );
        }

        return $response->getPdfContents();
    }

    /**
     * @param string $packetNumber
     * @param string $format
     * @param string $type
     * @param array $carrierNumbers
     * @param PacketsLabelsPdfResponse|PacketsCourierLabelsPdfResponse $response
     *
     * @return array
     */
    public function buildLogProperties($packetNumber, $format, $type, array $carrierNumbers, $response)
    {
        $logProperties = [
            'packetNumber' => $packetNumber,
            'format' => $format,
            'type' => $type,
        ];
        if ($response->hasInvalidPacketId($packetNumber) === true) {
            $logProperties['isPacketIdInvalid'] = true;
        }
        if (isset($carrierNumbers[$packetNumber])) {
            $logProperties['packetCourierNumber'] = $carrierNumbers[$packetNumber];
            if (
                $response instanceof PacketsCourierLabelsPdfResponse
                && $response->hasInvalidCourierNumber($carrierNumbers[$packetNumber]) === true
            ) {
                $logProperties['isCourierNumberInvalid'] = true;
            }
        }

        return $logProperties;
    }
}
