<?php

namespace Packetery\ApiCarrier;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Message\Response;
use Packetery;
use Packetery\Exceptions\DatabaseException;
use Packetery\Exceptions\DownloadException;
use Packetery\Module\SoapApi;
use Packetery\Tools\ConfigHelper;

class Downloader
{
    const API_URL = 'https://www.zasilkovna.cz/api/v4/%s/branch.json?address-delivery';

    /** @var Packetery */
    private $module;

    /** @var ApiCarrierRepository */
    private $apiCarrierRepository;

    /** @var SoapApi */
    private $configHelper;

    /**
     * Downloader constructor.
     * @param Packetery $module
     * @param ApiCarrierRepository $apiCarrierRepository
     * @param SoapApi $configHelper
     */
    public function __construct(Packetery $module, ApiCarrierRepository $apiCarrierRepository, ConfigHelper $configHelper)
    {
        $this->module = $module;
        $this->apiCarrierRepository = $apiCarrierRepository;
        $this->configHelper = $configHelper;
    }

    /**
     * Runs update and returns result.
     * @return array
     * @throws DatabaseException
     */
    public function run()
    {
        try {
            $carriers = $this->fetchAsArray();
        } catch (\Exception $e) {
            return [
                'text' => sprintf(
                    $this->module->getTranslator()->trans('Carrier download failed: %s Please try again later.', [], 'Modules.Packetery.Downloader'),
                    $e->getMessage()
                ),
                'class' => 'danger',
            ];
        }
        if (!$carriers) {
            return [
                'text' => sprintf(
                    $this->module->getTranslator()->trans('Carrier download failed: %s Please try again later.', [], 'Modules.Packetery.Downloader'),
                    $this->module->getTranslator()->trans('Failed to get the list.', [], 'Modules.Packetery.Downloader')
                ),
                'class' => 'danger',
            ];
        }
        $validation_result = $this->validateCarrierData($carriers);
        if (!$validation_result) {
            return [
                'text' => sprintf(
                    $this->module->getTranslator()->trans('Carrier download failed: %s Please try again later.', [], 'Modules.Packetery.Downloader'),
                    $this->module->getTranslator()->trans('Invalid API response.', [], 'Modules.Packetery.Downloader')
                ),
                'class' => 'danger',
            ];
        }
        $this->apiCarrierRepository->save($carriers, $this->module);
        ConfigHelper::update('PACKETERY_LAST_CARRIERS_UPDATE', time());

        return [
            'text' => $this->module->getTranslator()->trans('Carriers were updated.', [], 'Modules.Packetery.Downloader'),
            'class' => 'success',
        ];
    }

    /**
     * Downloads carriers and returns in array.
     *
     * @return array|null
     * @throws DownloadException DownloadException.
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
     * @throws DownloadException DownloadException.
     */
    private function downloadJson()
    {
        $url = sprintf(self::API_URL, $this->configHelper->getApiKey());

        // Guzzle version 5 is included from PrestaShop 1.7.0
        if (class_exists('GuzzleHttp\Client')) {
            $client = new Client();
            try {
                /** @var Response $result */
                $result = $client->get($url);
            } catch (TransferException $exception) {
                throw new DownloadException($exception->getMessage());
            }

            $body = $result->getBody();
            if (isset($body)) {
                return $body->getContents();
            }
            return '';
        }

        return \Tools::file_get_contents($url, false, null, 30, true);
    }

    /**
     * Converts JSON to array.
     *
     * @param string $json JSON.
     *
     * @return array|null
     */
    private function getFromJson($json)
    {
        $carriers_data = json_decode($json, true);

        return (isset($carriers_data['carriers']) ? $carriers_data['carriers'] : null);
    }

    /**
     * Validates data from API.
     * @param array $carriers Data retrieved from API.
     * @return bool
     */
    public function validateCarrierData(array $carriers)
    {
        foreach ($carriers as $carrier) {
            if (!isset(
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
            )) {
                return false;
            }
        }

        return true;
    }
}
