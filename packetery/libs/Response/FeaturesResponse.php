<?php

namespace Packetery\Response;

use InvalidArgumentException;
use Packetery\Tools\JsonStructureValidator;

/**
 * Represents a feature flag response from the API.
 */
class FeaturesResponse
{
    const JSON_STRUCTURE = [
        'plugin' => [
            'version' => 'string',
            'downloadUrl' => 'string',
        ],
        'features' => 'array',
    ];

    /**
     * @var array The plugin information.
     */
    private $plugin;

    /**
     * @var array The features and their state.
     */
    private $features;

    /**
     * @var JsonStructureValidator
     */
    private static $jsonStructureValidator;

    public function __construct($plugin, $features)
    {
        $this->plugin = $plugin;
        $this->features = $features;
        self::$jsonStructureValidator = new JsonStructureValidator();
    }

    /**
     * @param string $key
     * @return string
     */
    private function getPluginInfo($key)
    {
        return $this->plugin[$key];
    }

    /**
     * Returns the version of the plugin.
     *
     * @return string
     */
    public function getPluginVersion()
    {
        return $this->getPluginInfo('version');
    }

    /**
     * Returns the download URL of the plugin.
     *
     * @return string|null The download URL of the plugin, or null if it is not set.
     */
    public function getPluginDownloadUrl()
    {
        return $this->getPluginInfo('downloadUrl');
    }

    /**
     * Creates a new instance from the given JSON.
     *
     * @param string $json
     * @return FeaturesResponse
     */
    public static function createFromJson($json)
    {
        $data = json_decode($json, true);
        if (!self::$jsonStructureValidator) {
            self::$jsonStructureValidator = new JsonStructureValidator();
        }

        if (!self::$jsonStructureValidator->isStructureValid($data, self::JSON_STRUCTURE)) {
            throw new InvalidArgumentException('Invalid JSON for Features response.');
        }

        return new self($data['plugin'], $data['features']);
    }

}
