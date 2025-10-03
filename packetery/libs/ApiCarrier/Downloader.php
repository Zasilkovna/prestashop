<?php

/**
 * 2017 Zlab Solutions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
 *  @copyright Since 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\ApiCarrier;

if (!defined('_PS_VERSION_')) {
    exit;
}

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Message\Response;
use Packetery\Exceptions\DatabaseException;
use Packetery\Exceptions\DownloadException;
use Packetery\Tools\ConfigHelper;

class Downloader
{
    const API_URL = 'https://www.zasilkovna.cz/api/v4/%s/branch.json?address-delivery';

    /** @var \Packetery */
    private $module;

    /** @var ApiCarrierRepository */
    private $apiCarrierRepository;

    /** @var ConfigHelper */
    private $configHelper;

    /**
     * Downloader constructor.
     *
     * @param \Packetery $module
     * @param ApiCarrierRepository $apiCarrierRepository
     * @param ConfigHelper $configHelper
     */
    public function __construct(\Packetery $module, ApiCarrierRepository $apiCarrierRepository, ConfigHelper $configHelper)
    {
        $this->module = $module;
        $this->apiCarrierRepository = $apiCarrierRepository;
        $this->configHelper = $configHelper;
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
     * @throws DownloadException downloadException
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
