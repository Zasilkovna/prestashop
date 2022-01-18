<?php

namespace Packetery\ApiCarrier;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Message\Response;
use Packetery;
use Packetery\Exceptions\DatabaseException;
use Packetery\Exceptions\DownloadException;
use PacketeryApi;

/**
 * Class Downloader
 *
 * @package Packetery
 */
class Downloader
{
    const API_URL = 'https://www.zasilkovna.cz/api/v4/%s/branch.json?address-delivery';

    /** @var Packetery */
    private $module;

    /** @var ApiCarrierRepository */
    private $apiCarrierRepository;

    /**
     * Downloader constructor.
     * @param Packetery $module
     * @throws \ReflectionException
     */
    public function __construct(Packetery $module)
    {
        $this->module = $module;
        $apiCarrierRepository = $this->module->diContainer->get(ApiCarrierRepository::class);
        $this->apiCarrierRepository = $apiCarrierRepository;
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
                    $this->module->l('Carrier download failed: %s Please try again later.', 'downloader'),
                    $e->getMessage()
                ),
                'class' => 'danger',
            ];
        }
        if (!$carriers) {
            return [
                'text' => sprintf(
                    $this->module->l('Carrier download failed: %s Please try again later.', 'downloader'),
                    $this->module->l('Failed to get the list.', 'downloader')
                ),
                'class' => 'danger',
            ];
        }
        $validation_result = $this->validateCarrierData($carriers);
        if (!$validation_result) {
            return [
                'text' => sprintf(
                    $this->module->l('Carrier download failed: %s Please try again later.', 'downloader'),
                    $this->module->l('Invalid API response.', 'downloader')
                ),
                'class' => 'danger',
            ];
        }
        $this->apiCarrierRepository->save($carriers, $this->module);
        \Configuration::updateValue('PACKETERY_LAST_CARRIERS_UPDATE', time());

        return [
            'text' => $this->module->l('Carriers were updated.', 'downloader'),
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
        $url = sprintf(self::API_URL, PacketeryApi::getApiKey());

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
