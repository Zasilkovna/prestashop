<?php

namespace Packetery\Module;

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
    public function getWithGithubAuthorizationToken($url)
    {
        if (class_exists('GuzzleHttp\Client')) {
            $token = defined('_GITHUB_ACCESS_TOKEN_') ? _GITHUB_ACCESS_TOKEN_ : null;

            if ($token !== null) {
                $headers['Authorization'] = "token {$token}";
                $client = new Client([
                    'headers' => $headers,
                ]);
            } else {
                $client = new Client();
            }


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
