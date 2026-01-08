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

        $rules['blank_line_after_opening_tag'] = false;

        $rules['header_comment'] = [
            'header' => <<<'HEADER'
@author    Packeta s.r.o. <e-commerce.support@packeta.com>
@copyright 2017 Packeta s.r.o.
@license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
HEADER
,
            'comment_type' => 'PHPDoc',
            'location' => 'after_open',
            'separate' => 'none',
        ];

        return $rules;
    }
}
