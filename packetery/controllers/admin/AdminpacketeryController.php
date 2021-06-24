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
 *  @copyright 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
class AdminpacketeryController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();

        $this->bootstrap = true;
        $this->html = '';
        $this->display = 'view';
        $this->meta_title = $this->l('Packeta', 'adminpacketerycontroller');
        $context = Context::getContext();

        if (isset($context->employee) && ($context->employee->id > 0)) {
            $id_employee = $context->employee->id;
            $token = self::getAdminToken($id_employee);
            Tools::redirectAdmin("index.php?controller=AdminModules&token=$token&configure=packetery");
        } else {
            die();
        }
    }

    public function renderView()
    {
        $context = Context::getContext();

        if (isset($context->employee) && ($context->employee->id > 0)) {
            $id_employee = $context->employee->id;
            $token = self::getAdminToken($id_employee);
            Tools::redirectAdmin("index.php?controller=AdminModules&token=$token&configure=packetery");
        } else {
            die();
        }
    }

    public static function getAdminToken($id_employee)
    {
        $tab = 'AdminModules';
        return Tools::getAdminToken($tab.(int)Tab::getIdFromClassName($tab).(int)$id_employee);
    }
}
