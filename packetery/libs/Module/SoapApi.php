<?php

namespace Packetery\Module;

use Packetery\Exceptions\SenderGetReturnRoutingException;
use Packetery\Tools\ConfigHelper;
use SoapClient;
use SoapFault;

class SoapApi
{
    const API_WSDL_URL = 'https://www.zasilkovna.cz/api/soap-php-bugfix.wsdl';

    /**
     * @param string $senderIndication
     * @return array with 2 return routing strings for a sender specified by $senderIndication.
     * @throws SenderGetReturnRoutingException
     */
    public function senderGetReturnRouting($senderIndication)
    {
        $client = new SoapClient(self::API_WSDL_URL);
        $apiPassword = $this->getApiPass();
        try {
            $response = $client->senderGetReturnRouting($apiPassword, $senderIndication);
            return $response->routingSegment;
        } catch (SoapFault $e) {
            throw new SenderGetReturnRoutingException($e->getMessage(), isset($e->detail->SenderNotExists));
        }
    }

    /**
     * @return false|string
     */
    public function getApiPass()
    {
        return ConfigHelper::get('PACKETERY_APIPASS');
    }

    /**
     * @return false|string
     */
    public function getApiKey()
    {
        $apiPass = $this->getApiPass();
        if ($apiPass === false) {
            return false;
        }

        return substr($apiPass, 0, 16);
    }

}
