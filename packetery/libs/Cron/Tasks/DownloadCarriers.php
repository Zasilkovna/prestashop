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

namespace Packetery\Cron\Tasks;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\ApiCarrier\Downloader;
use Packetery\Exceptions\DatabaseException;

class DownloadCarriers extends Base
{
    /** @var \Packetery */
    public $module;

    /** @var Downloader */
    private $downloader;

    /**
     * @param \Packetery $module
     * @param Downloader $downloader
     */
    public function __construct(\Packetery $module, Downloader $downloader)
    {
        $this->module = $module;
        $this->downloader = $downloader;
    }

    /**
     * @return string[]
     *
     * @throws DatabaseException
     */
    public function execute()
    {
        $result = $this->downloader->run();
        if ($result['class'] === 'danger') {
            return [$result['text']];
        }

        return [];
    }
}
