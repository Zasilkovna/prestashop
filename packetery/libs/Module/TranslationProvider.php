<?php

namespace Packetery\Module;

use Packetery;
use Tools;

class TranslationProvider
{
    const MAX_NUMBER_OF_ORDERS_TO_PROCESS = 'Insert maximum number of orders that will be processed';

    /** @var Packetery */
    private $module;

    public function __construct(Packetery $module)
    {
        $this->module = $module;
    }

    /**
     * @param string $translationKey
     * @return string
     */
    public function getTranslation($translationKey)
    {
        return ($this->getTranslations()[$translationKey] ?: '');
    }

    public function getTranslations()
    {
        if (Tools::version_compare(_PS_VERSION_, '9.0', '<')) {
            return [
                'Api password must be 32 characters long.' =>
                    $this->module->l('Api password must be 32 characters long.', 'translationsprovider'),
                self::MAX_NUMBER_OF_ORDERS_TO_PROCESS =>
                    $this->module->l(
                        'Insert maximum number of orders that will be processed',
                        'translationsprovider'
                    ),
            ];
        }

        return [
            'Api password must be 32 characters long.' =>
                $this->module->getTranslator()->trans(
                    'Api password must be 32 characters long.',
                    [],
                    'Modules.Packetery.TranslationProvider'
                ),
            self::MAX_NUMBER_OF_ORDERS_TO_PROCESS =>
                $this->module->getTranslator()->trans(
                    'Insert maximum number of orders that will be processed',
                    [],
                    'Modules.Packetery.TranslationProvider'
                ),
        ];
    }
}
