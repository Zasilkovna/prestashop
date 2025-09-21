<?php

declare(strict_types=1);

namespace Packetery\PickupPointValidate;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Exception;
use Packetery\PickupPointValidate\Exception\HttpRequestException;
use Packetery\Request\PickupPointValidateRequest;
use Packetery\Response\PickupPointValidateResponse;
use Packetery\Tools\HttpClientWrapper;

class PickupPointValidate
{
    private const URL_VALIDATE_ENDPOINT = 'https://widget.packeta.com/v6/pps/api/widget/v1/validate';

    /** @var string */
    private $apiKey;

    /** @var HttpClientWrapper */
    private $httpClient;

    private function __construct(string $apiKey, HttpClientWrapper $httpClient)
    {
        $this->apiKey = $apiKey;
        $this->httpClient = $httpClient;
    }

    /**
     * @param false|string $apiKey
     * @param HttpClientWrapper $httpClient
     * @return PickupPointValidate
     */
    public static function createWithValidApiKey($apiKey, HttpClientWrapper $httpClient): PickupPointValidate
    {
        return new self($apiKey, $httpClient);
    }

    public function validate(PickupPointValidateRequest $request): PickupPointValidateResponse
    {
        $postData = $request->getSubmittableData();
        $postData['apiKey'] = $this->apiKey;
        $options = [
            'body' => json_encode($postData),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];
        try {
            $contents = $this->httpClient->post(self::URL_VALIDATE_ENDPOINT, $options);
        } catch (Exception $e) {
            throw new HttpRequestException('HTTP Request Exception: ' . $e->getMessage(), 0, $e);
        }
        $resultArray = json_decode($contents, true);

        if (!is_array($resultArray)) {
            throw new HttpRequestException('Invalid JSON response received from API.');
        }

        if (!array_key_exists('isValid', $resultArray) || !is_bool($resultArray['isValid'])) {
            throw new HttpRequestException(sprintf(
                'Invalid API response: expected boolean "isValid" field, got %s.',
                var_export($resultArray['isValid'] ?? null, true)
            ));
        }

        if (!array_key_exists('errors', $resultArray) || !is_array($resultArray['errors'])) {
            throw new HttpRequestException(sprintf(
                'Invalid API response: expected array "errors" field, got %s.',
                var_export($resultArray['errors'] ?? null, true)
            ));
        }

        return new PickupPointValidateResponse($resultArray['isValid'], $resultArray['errors']);
    }
}
