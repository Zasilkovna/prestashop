<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

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
    public const CHECK_INTERVAL_IN_SECONDS = 24 * 3600; // 1 day
    public const GITHUB_RELEASES_ENDPOINT_URL = 'https://api.github.com/repos/Zasilkovna/prestashop/releases';

    /** @var \Packetery */
    private $module;

    /** @var ApiClientFacade */
    private $apiClientFacade;

    /** @var JsonStructureValidator */
    private $jsonStructureValidator;

    public function __construct(
        \Packetery $module,
        ApiClientFacade $apiClientFacade,
        JsonStructureValidator $jsonStructureValidator
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
            if (
                (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_ === false)
                && (defined('_PACKETERY_DEBUG_LOG_') && _PACKETERY_DEBUG_LOG_ === true)
            ) {
                \PrestaShopLogger::addLog("Packetery: {$exception->getMessage()}", 3, null, null, null, true);
            } elseif (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_ === true) {
                throw $exception;
            }
            ConfigHelper::update(ConfigHelper::KEY_LAST_VERSION_CHECK_TIMESTAMP, time());

            return;
        }

        $version = $response->getVersion();
        $downloadUrl = $response->getDownloadUrl();
        $releaseNotes = $response->getReleaseNotes();

        if ($this->shouldUpdateStoredVersionData($version, $downloadUrl)) {
            ConfigHelper::update(ConfigHelper::KEY_LAST_VERSION, $version);
            ConfigHelper::update(ConfigHelper::KEY_LAST_VERSION_URL, $downloadUrl);
            ConfigHelper::update(ConfigHelper::KEY_LAST_RELEASE_NOTES, $releaseNotes);
        }

        ConfigHelper::update(ConfigHelper::KEY_LAST_VERSION_CHECK_TIMESTAMP, time());
    }

    /**
     * @return LatestReleaseResponse
     *
     * @throws ApiClientException
     * @throws VersionCheckerException
     */
    private function getLatestReleaseResponse(): LatestReleaseResponse
    {
        $allowedReleaseTypes = defined('_PACKETERY_ALLOWED_RELEASE_TYPES_') && is_array(_PACKETERY_ALLOWED_RELEASE_TYPES_)
            ? _PACKETERY_ALLOWED_RELEASE_TYPES_
            : ['stable'];

        $json = $this->apiClientFacade->getWithGithubAuthorizationToken(self::GITHUB_RELEASES_ENDPOINT_URL);
        if ($json === '' || $json === false) {
            throw new VersionCheckerException('Failed to retrieve response from GitHub releases endpoint.');
        }

        $releaseList = json_decode($json, true);

        if (!is_array($releaseList) || json_last_error() !== JSON_ERROR_NONE) {
            \PrestaShopLogger::addLog('Packetery: JSON decode error: ' . json_last_error_msg(), 3, null, null, null, true);
            throw VersionCheckerException::createForInvalidLatestReleaseResponse();
        }

        foreach ($releaseList as $release) {
            $isStructureValid = $this->jsonStructureValidator->isStructureValid(
                $release,
                [
                    'tag_name' => 'string',
                    'draft' => 'bool',
                    'prerelease' => 'bool',
                    'assets' => [
                        0 => [
                            'browser_download_url' => 'string',
                        ],
                    ],
                    'body' => 'string',
                ]
            );

            if (!$isStructureValid) {
                throw VersionCheckerException::createForInvalidLatestReleaseResponse();
            }

            $isDraft = (bool) $release['draft'];
            $isPrerelease = (bool) $release['prerelease'];

            if ($isDraft) {
                $releaseType = 'draft';
            } elseif ($isPrerelease) {
                $releaseType = 'prerelease';
            } else {
                $releaseType = 'stable';
            }

            if (in_array($releaseType, $allowedReleaseTypes, true)) {
                return new LatestReleaseResponse(
                    ltrim($release['tag_name'], 'v'),
                    $release['assets'][0]['browser_download_url'],
                    $release['body']
                );
            }
        }

        throw VersionCheckerException::createForInvalidLatestReleaseResponse();
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

        $showReadMoreLink = false;
        $releaseNotes = ConfigHelper::get(ConfigHelper::KEY_LAST_RELEASE_NOTES);
        $releaseNotes = nl2br($releaseNotes);
        $limit = 400;
        if (mb_strlen($releaseNotes, 'UTF-8') > $limit) {
            $showReadMoreLink = true;
            $releaseNotes = mb_substr($releaseNotes, 0, $limit, 'UTF-8');
        }

        $smarty->assign('releaseNotes', $releaseNotes);
        $smarty->assign('showReadMoreLink', $showReadMoreLink);

        return $smarty->fetch(__DIR__ . '/../../views/templates/admin/newVersionMessage.tpl');
    }

    public function getVersionUpdateMessage(): string
    {
        return sprintf(
            $this->module->l('A new version of the Packeta module is available: %s (current version: %s).', 'versionchecker'),
            ConfigHelper::get(ConfigHelper::KEY_LAST_VERSION),
            $this->module->version
        ) . ' ' .
            $this->module->l('More information can be found on the configuration page.', 'versionchecker');
    }

    private function shouldUpdateStoredVersionData(string $version, string $downloadUrl): bool
    {
        return $version !== ''
               && $downloadUrl !== ''
               && $this->isNewVersionAvailable($version);
    }
}
