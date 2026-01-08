<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\ApiCarrier;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Exceptions\DatabaseException;
use Packetery\Exceptions\DownloadException;
use Packetery\Tools\ConfigHelper;
use Packetery\Tools\HttpClientWrapper;

class Downloader
{
    public const API_URL = 'https://www.zasilkovna.cz/api/v4/%s/branch.json?address-delivery';

    /** @var \Packetery */
    private $module;

    /** @var ApiCarrierRepository */
    private $apiCarrierRepository;

    /** @var ConfigHelper */
    private $configHelper;

    /** @var HttpClientWrapper */
    private $httpClient;

    public function __construct(
        \Packetery $module,
        ApiCarrierRepository $apiCarrierRepository,
        ConfigHelper $configHelper,
        HttpClientWrapper $httpClient
    ) {
        $this->module = $module;
        $this->apiCarrierRepository = $apiCarrierRepository;
        $this->configHelper = $configHelper;
        $this->httpClient = $httpClient;
    }

    /**
     * Runs update and returns result.
     *
     * @return array
     *
     * @throws DatabaseException
     */
    public function run()
    {
        try {
            $carriers = $this->fetchAsArray();
        } catch (\Exception $e) {
            return [
                'text' => sprintf(
                    $this->module->l('Carrier download failed: %s Please try again later.', 'downloader'),
                    $e->getMessage()
                ),
                'class' => 'danger',
            ];
        }
        if (is_array($carriers) === false || $carriers === []) {
            return [
                'text' => sprintf(
                    $this->module->l('Carrier download failed: %s Please try again later.', 'downloader'),
                    $this->module->l('Failed to get the list.', 'downloader')
                ),
                'class' => 'danger',
            ];
        }
        $validation_result = $this->validateCarrierData($carriers);
        if ($validation_result === false) {
            return [
                'text' => sprintf(
                    $this->module->l('Carrier download failed: %s Please try again later.', 'downloader'),
                    $this->module->l('Invalid API response.', 'downloader')
                ),
                'class' => 'danger',
            ];
        }
        $this->apiCarrierRepository->save($carriers, $this->module);
        ConfigHelper::update('PACKETERY_LAST_CARRIERS_UPDATE', time());

        return [
            'text' => $this->module->l('Carriers were updated.', 'downloader'),
            'class' => 'success',
        ];
    }

    /**
     * Downloads carriers and returns in array.
     *
     * @return array|null
     *
     * @throws DownloadException downloadException
     */
    private function fetchAsArray()
    {
        $json = $this->downloadJson();

        return $this->getFromJson($json);
    }

    /**
     * Downloads carriers in JSON.
     *
     * @return string
     *
     * @throws DownloadException
     */
    private function downloadJson()
    {
        $url = sprintf(self::API_URL, $this->configHelper->getApiKey());

        try {
            return $this->httpClient->get($url);
        } catch (\Exception $exception) {
            throw new DownloadException($exception->getMessage());
        }
    }

    /**
     * Converts JSON to array.
     *
     * @param string $json JSON
     *
     * @return array|null
     */
    private function getFromJson($json)
    {
        $carriers_data = json_decode($json, true);

        return isset($carriers_data['carriers']) ? $carriers_data['carriers'] : null;
    }

    /**
     * Validates data from API.
     *
     * @param array $carriers data retrieved from API
     *
     * @return bool
     */
    public function validateCarrierData(array $carriers)
    {
        foreach ($carriers as $carrier) {
            if (
                !isset(
                    $carrier['id'],
                    $carrier['name'],
                    $carrier['country'],
                    $carrier['currency'],
                    $carrier['pickupPoints'],
                    $carrier['apiAllowed'],
                    $carrier['separateHouseNumber'],
                    $carrier['customsDeclarations'],
                    $carrier['requiresEmail'],
                    $carrier['requiresPhone'],
                    $carrier['requiresSize'],
                    $carrier['disallowsCod'],
                    $carrier['maxWeight']
                )
            ) {
                return false;
            }
        }

        return true;
    }
}
