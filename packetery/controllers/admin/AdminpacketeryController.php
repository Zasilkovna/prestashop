<?php
/**
* 2016-2017 ZSolutions
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
*  @author    Eugene Zubkov <magrabota@gmail.com>
*  @copyright 2016 ZSolutions
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Property of ZSolutions https://www.facebook.com/itZSsolutions/
*/

class AdminpacketeryController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();

        $this->bootstrap = true;
        $this->html = '';
        $this->display = 'view';
        $this->meta_title = $this->l('Zasilkona');
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
