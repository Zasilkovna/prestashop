<?php

namespace Packetery\Module;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Exceptions\ApiClientException;
use Packetery\Tools\HttpClientWrapper;

class ApiClientFacade
{
    /** @var HttpClientWrapper */
    private $httpClient;

    public function __construct(HttpClientWrapper $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param string $url
     *
     * @return string|bool
     *
     * @throws ApiClientException
     */
    public function getWithGithubAuthorizationToken($url)
    {
        $options = [];
        $token = defined('_GITHUB_ACCESS_TOKEN_') ? _GITHUB_ACCESS_TOKEN_ : null;
        if ($token !== null) {
            $options = [
                'headers' => [
                    'Authorization' => "token {$token}",
                ],
            ];
        }

        try {
            return $this->httpClient->get($url, $options);
        } catch (\Exception $exception) {
            throw new ApiClientException($exception->getMessage());
        }
    }
}
