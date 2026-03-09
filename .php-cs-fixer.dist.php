<?php

use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

require_once __DIR__ . '/PacketeryCsFixerConfig.php';
$config = new PacketeryCsFixerConfig();

$finder = Finder::create()
    ->in(__DIR__ . '/packetery')
    ->exclude('vendor')
    ->name('*.php');

$config->setFinder($finder);
$config->setUsingCache(false);
$config->setParallelConfig(ParallelConfigFactory::detect());

return $config;

