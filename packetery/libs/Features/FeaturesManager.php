<?php

namespace Packetery\Features;

use Exception;
use Packetery\Exceptions\ApiClientException;
use Packetery\Module\ApiClientFacade;
use Packetery\Module\VersionChecker;
use Packetery\Response\FeaturesResponse;
use Packetery\Response\LatestReleaseResponse;
use Packetery\Tools\ConfigHelper;
use Packetery\Tools\JsonStructureValidator;
use PrestaShopLogger;

class FeaturesManager
{
    const FEATURES_API_URL = 'https://pes-features-prod-pes.prod.packeta-com.codenow.com/v1/ps';
    const CHECK_INTERVAL = 24 * 3600; // 1 day

    /** @var ApiClientFacade */
    private $client;

    /** @var ConfigHelper */
    private $configHelper;

    /** @var VersionChecker */
    private $versionChecker;

    /** @var JsonStructureValidator */
    private $jsonStructureValidator;

    /**
     * @param ApiClientFacade $client
     * @param ConfigHelper $configHelper
     * @param VersionChecker $versionChecker
     */
    public function __construct(
        ApiClientFacade $client,
        ConfigHelper $configHelper,
        VersionChecker $versionChecker,
        JsonStructureValidator $jsonStructureValidator
    )
    {
        $this->client = $client;
        $this->configHelper = $configHelper;
        $this->versionChecker = $versionChecker;
        $this->jsonStructureValidator = $jsonStructureValidator;
    }

    /**
     * @return bool
     */
    public function shouldCheckApi()
    {
        $lastCheck = ConfigHelper::get(ConfigHelper::KEY_LAST_FEATURE_CHECK);

        return (($lastCheck === false) || (time() - $lastCheck > self::CHECK_INTERVAL));
    }

    /**
     * @return void
     */
    public function checkForUpdate()
    {
        if (!$this->shouldCheckApi()) {
            return;
        }

        try {
            $response = $this->getLatestReleaseResponse();
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Packetery: ' . $e->getMessage(), 3, null, null, null, true);

            return;
        }

        $version = $response->getVersion();
        $downloadUrl = $response->getDownloadUrl();
        if ($version && $downloadUrl && $this->versionChecker->isNewVersionAvailable($version)) {
            ConfigHelper::update(ConfigHelper::KEY_LAST_VERSION, $version);
            ConfigHelper::update(ConfigHelper::KEY_LAST_VERSION_URL, $downloadUrl);
        }

        ConfigHelper::update(ConfigHelper::KEY_LAST_FEATURE_CHECK, time());
    }

    /**
     * @return string
     */
    private function getApiUrl()
    {
        $url = self::FEATURES_API_URL;
        $apiKey = $this->configHelper->getApiKey();

        if ($apiKey) {
            return sprintf($url . '?api_key=%s', $apiKey);
        }

        return $url;
    }

    /**
     * @return FeaturesResponse
     * @throws ApiClientException
     */
    public function getResponse()
    {
        $json = $this->client->get($this->getApiUrl());
        if (!$json) {
            throw new ApiClientException('Empty response from Features API.');
        }

        return FeaturesResponse::createFromJson($json);
    }

    /**
     * @return LatestReleaseResponse
     * @throws ApiClientException
     */
    public function getLatestReleaseResponse()
    {
        $json = $this->client->get('https://api.github.com/repos/Zasilkovna/prestashop/releases/latest');
        if (!$json) {
            throw new ApiClientException('Empty response from GitHub latest releases endpoint.');
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            PrestaShopLogger::addLog('Packetery: ' . json_last_error_msg(), 3, null, null, null, true);
            throw new ApiClientException('Invalid response from GitHub latest releases endpoint.');
        }

        $isStructureValid = $this->jsonStructureValidator->isStructureValid(
            $data,
            [
                'tag_name' => 'string',
                'assets' => [
                    0 => [
                        'browser_download_url' => 'string',
                    ],
                ],
            ]
        );

        if (!$isStructureValid) {
            throw new ApiClientException('Invalid response structure from GitHub latest releases endpoint.');
        }

        return new LatestReleaseResponse(
            ltrim($data['tag_name'], 'v'),
            $data['assets'][0]['browser_download_url']
        );
    }
}
