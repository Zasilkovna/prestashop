<?php

use PrestaShop\CodingStandards\CsFixer\Config;

class PacketeryCsFixerConfig extends Config
{
    public function getRules(): array
    {
        $rules = parent::getRules();

        $rules['trailing_comma_in_multiline'] = [
            'elements' => ['arrays', 'array_destructuring'],
            'after_heredoc' => false,
        ];

        return $rules;
    }
}
