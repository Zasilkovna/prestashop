<?php

namespace Packetery\Module;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Exceptions\ApiClientException;
use Packetery\Exceptions\VersionCheckerException;
use Packetery\Response\LatestReleaseResponse;
use Packetery\Tools\ConfigHelper;
use Packetery\Tools\JsonStructureValidator;

class VersionChecker
{
    const CHECK_INTERVAL_IN_SECONDS = 24 * 3600; // 1 day
    const LATEST_RELEASES_ENDPOINT_URL = 'https://api.github.com/repos/Zasilkovna/prestashop/releases/latest';

    /** @var \Packetery */
    private $module;

    /** @var ApiClientFacade */
    private $apiClientFacade;

    /** @var JsonStructureValidator */
    private $jsonStructureValidator;

    public function __construct(
        \Packetery $module,
        ApiClientFacade $apiClientFacade,
        JsonStructureValidator $jsonStructureValidator,
    ) {
        $this->module = $module;
        $this->apiClientFacade = $apiClientFacade;
        $this->jsonStructureValidator = $jsonStructureValidator;
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
        } catch (\Exception $exception) {
            \PrestaShopLogger::addLog('Packetery: ' . $exception->getMessage(), 3, null, null, null, true);
            ConfigHelper::update(ConfigHelper::KEY_LAST_VERSION_CHECK_TIMESTAMP, time());

            return;
        }

        $version = $response->getVersion();
        $downloadUrl = $response->getDownloadUrl();
        if ($version !== '' && $downloadUrl !== '' && $this->isNewVersionAvailable($version)) {
            ConfigHelper::update(ConfigHelper::KEY_LAST_VERSION, $version);
            ConfigHelper::update(ConfigHelper::KEY_LAST_VERSION_URL, $downloadUrl);
        }

        ConfigHelper::update(ConfigHelper::KEY_LAST_VERSION_CHECK_TIMESTAMP, time());
    }

    /**
     * @return LatestReleaseResponse
     *
     * @throws ApiClientException
     * @throws VersionCheckerException
     */
    private function getLatestReleaseResponse()
    {
        $json = $this->apiClientFacade->get(self::LATEST_RELEASES_ENDPOINT_URL);
        if ($json === '' || $json === false) {
            throw new VersionCheckerException('Failed to retrieve response from GitHub latest releases endpoint.');
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            \PrestaShopLogger::addLog('Packetery: JSON decode error: ' . json_last_error_msg(), 3, null, null, null, true);
            throw VersionCheckerException::createForInvalidLatestReleaseResponse();
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
            throw VersionCheckerException::createForInvalidLatestReleaseResponse();
        }

        return new LatestReleaseResponse(
            ltrim($data['tag_name'], 'v'),
            $data['assets'][0]['browser_download_url']
        );
    }

    /**
     * @return bool
     */
    private function shouldCheckApi()
    {
        $lastCheck = ConfigHelper::get(ConfigHelper::KEY_LAST_VERSION_CHECK_TIMESTAMP);
        if ($lastCheck === false) {
            return true;
        }

        return (time() - (int) $lastCheck) > self::CHECK_INTERVAL_IN_SECONDS;
    }

    /**
     * @param string|null $newVersion
     *
     * @return bool
     */
    public function isNewVersionAvailable($newVersion = null)
    {
        if (!$newVersion) {
            $latestVersion = ConfigHelper::get(ConfigHelper::KEY_LAST_VERSION);

            return $latestVersion ? \Tools::version_compare($this->module->version, $latestVersion) : false;
        }

        return \Tools::version_compare($this->module->version, $newVersion);
    }

    /**
     * @return false|string
     *
     * @throws \SmartyException
     */
    public function getVersionUpdateMessageHtml()
    {
        $smarty = $this->module->getContext()->smarty;
        $smarty->assign('downloadUrl', ConfigHelper::get(ConfigHelper::KEY_LAST_VERSION_URL));
        $smarty->assign('newVersion', ConfigHelper::get(ConfigHelper::KEY_LAST_VERSION));
        $smarty->assign('currentVersion', $this->module->version);

        return $smarty->fetch(__DIR__ . '/../../views/templates/admin/newVersionMessage.tpl');
    }
}
