<?php

namespace Packetery\Order;

use Packetery\Module\SoapApi;
use Packetery\Tools\ConfigHelper;
use SoapClient;
use SoapFault;

class Labels
{
    const TYPE_PACKETA = 'packeta';
    const TYPE_CARRIER = 'carrier';

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @param ConfigHelper $configHelper
     */
    public function __construct(ConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    /**
     * @param array $packets Used for packeta labels.
     * @param string $type
     * @param int $offset
     * @param array|null $packetsEnhanced Used for carrier labels.
     * @return string|void
     */
    public function packetsLabelsPdf(array $packets, $type, $packetsEnhanced = null , $offset = 0)
    {
        $client = new SoapClient(SoapApi::WSDL_URL);
        try {
            if ($type === self::TYPE_CARRIER) {
                $format = ConfigHelper::get('PACKETERY_CARRIER_LABEL_FORMAT');
                $pdf = $client->packetsCourierLabelsPdf($this->configHelper->getApiPass(), $packetsEnhanced, $offset, $format);
            } else {
                $format = ConfigHelper::get('PACKETERY_LABEL_FORMAT');
                $pdf = $client->packetsLabelsPdf($this->configHelper->getApiPass(), $packets, $format, $offset);
            }
            if ($pdf) {
                return $pdf;
            }

            echo "\n error \n";
            exit;
        } catch (SoapFault $e) {
            if (isset($e->faultstring)) {
                $error_msg = $e->faultstring;
                echo "\n$error_msg\n";
            }
            exit;
        }
    }
}
