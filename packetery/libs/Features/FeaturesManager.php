<?php

namespace Packetery\Features;

use Exception;
use Packetery\Exceptions\ApiClientException;
use Packetery\Module\ApiClientFacade;
use Packetery\Module\VersionChecker;
use Packetery\Response\FeaturesResponse;
use Packetery\Tools\ConfigHelper;
use PrestaShopLogger;

class FeaturesManager
{
    const FEATURES_API_URL = 'https://pes-features-prod-pes.prod.packeta-com.codenow.com/v1/ps';
    const CHECK_INTERVAL = 3600; // 1 hour

    /** @var ApiClientFacade */
    private $client;

    /** @var ConfigHelper */
    private $configHelper;

    /** @var VersionChecker */
    private $versionChecker;

    /**
     * @param ApiClientFacade $client
     * @param ConfigHelper $configHelper
     * @param VersionChecker $versionChecker
     */
    public function __construct(ApiClientFacade $client, ConfigHelper $configHelper, VersionChecker $versionChecker)
    {
        $this->client = $client;
        $this->configHelper = $configHelper;
        $this->versionChecker = $versionChecker;
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
     * The method is temporarily disabled due to Packeta endpoint instability.
     *
     * @return void
     */
    public function checkForUpdate()
    {
        if (!$this->shouldCheckApi()) {
            return;
        }

        try {
            $response = $this->getResponse();
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Packetery: ' . $e->getMessage(), 3, null, null, null, true);

            return;
        }

        $latestVersion = $response->getPluginVersion();
        $downloadUrl = $response->getPluginDownloadUrl();
        if ($latestVersion && $downloadUrl && $this->versionChecker->isNewVersionAvailable($latestVersion)) {
            ConfigHelper::update(ConfigHelper::KEY_LAST_VERSION, $latestVersion);
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

}
