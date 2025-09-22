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

use Packetery\Exceptions\DatabaseException;
use Packetery\Log\LogRepository;
use Packetery\Tools\Tools;

class PurgeLogs extends Base
{
    const DEFAULT_LOG_EXPIRATION_DAYS = 30;

    /** @var \Packetery */
    public $module;

    /** @var LogRepository */
    private $logRepository;

    /**
     * @param \Packetery $module
     * @param LogRepository $logRepository
     */
    public function __construct(\Packetery $module, LogRepository $logRepository)
    {
        $this->module = $module;
        $this->logRepository = $logRepository;
    }

    /**
     * @return string[]
     */
    public function execute()
    {
        $logExpirationDays = (int) Tools::getValue('log_expiration_days', self::DEFAULT_LOG_EXPIRATION_DAYS);
        try {
            $this->logRepository->purge($logExpirationDays);
        } catch (DatabaseException $e) {
            return [$e->getMessage()];
        }

        return [];
    }
}
