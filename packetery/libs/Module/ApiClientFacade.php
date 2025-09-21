<?php

namespace Packetery\Module;

if (!defined('_PS_VERSION_')) {
    exit;
}

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Message\Response;
use Packetery\Exceptions\ApiClientException;

class ApiClientFacade
{
    /**
     * @param string $url
     * @return string|bool
     * @throws \Packetery\Exceptions\ApiClientException
     */
    public function get($url)
    {
        if (class_exists('GuzzleHttp\Client')) {
            $client = new Client();
            try {
                /** @var Response $result */
                $result = $client->get($url);
            } catch (TransferException $exception) {
                throw new ApiClientException($exception->getMessage());
            }
            $body = $result->getBody();

            if (isset($body)) {
                return $body->getContents();
            }

            return '';
        }

        return \Tools::file_get_contents($url, false, null, 30, true);
    }
}
