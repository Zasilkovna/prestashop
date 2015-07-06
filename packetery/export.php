<?php
  define('_PS_ADMIN_DIR_', getcwd().'/../../'.preg_replace('/[^a-z0-9._ -]/si', '', $_GET['admindir']));
  require_once('../../config/config.inc.php');
  require_once(_PS_ADMIN_DIR_.'/init.php');
  require_once('AdminOrderPacketery.php');
  
  $aop = new AdminOrderPacketery();
  $aop->export_csv();
?>