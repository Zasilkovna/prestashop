<?php
  require_once('../../config/config.inc.php');
  require_once('../../init.php');
  require_once('packetery.php');
  
  if(!$cart || !$cart->id) return;
  
  $db = Db::getInstance();
  if($db->getValue('select 1 from `'._DB_PREFIX_.'packetery_order` where id_cart=' . ((int) $cart->id))) {
      $db->execute('update `'._DB_PREFIX_.'packetery_order` set id_branch=' . ((int) $_POST['id_branch']) . ', name_branch="' . pSQL($_POST['name_branch']) . '", currency_branch="' . pSQL($_POST['currency_branch']) . '" where id_cart=' . ((int) $cart->id));
  }
  else {
      $db->execute('insert into `'._DB_PREFIX_.'packetery_order` set id_branch=' . ((int) $_POST['id_branch']) . ', name_branch="' . pSQL($_POST['name_branch']) . '", currency_branch="' . pSQL($_POST['currency_branch']) . '", id_cart=' . ((int) $cart->id));
  }
  
  header("Content-Type: application/json");
  echo json_encode(array('success' => true));
?>