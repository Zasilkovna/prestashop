<?php

use PhpCsFixer\Finder;

require_once __DIR__ . '/PacketeryCsFixerConfig.php';
$config = new PacketeryCsFixerConfig();

$finder = Finder::create()
    ->in(__DIR__ . '/packetery')
    ->exclude('vendor')
    ->exclude('translations')
    ->name('*.php');

$config->setFinder($finder);
$config->setUsingCache(false);

return $config;

