<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 *  @author    Zásilkovna, s.r.o.
 *  @copyright 2012-2016 Zásilkovna, s.r.o.
 *  @license   LICENSE.txt
 */

define('_PS_ADMIN_DIR_', getcwd().'/../../'.preg_replace('/[^a-z0-9._ -]/si', '', $_GET['admindir']));
require_once('../../config/config.inc.php');
require_once(_PS_ADMIN_DIR_.'/init.php');
require_once('AdminOrderPacketery.php');

$aop = new AdminOrderPacketery();
$aop->exportCsv();
