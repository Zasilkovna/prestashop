<?php
  class Carrier extends CarrierCore
  { // Open brace must be on separate line for the PrestaShop override composition to work.

      /* This function adds Packetery branch name to carrier name
         in some places, especially e-mail templates. */
      function __construct($id = null, $id_lang = null)
      {
          parent::__construct($id, $id_lang);
          
          $db = Db::getInstance();
          $context = Context::getContext(); 

          $controller = Tools::getValue('controller');
          if($controller == "orderdetail") {
              list($order) = $db->executeS('select id_carrier, id_cart from `'._DB_PREFIX_.'orders` where id_order=' . ((int) Tools::getValue('id_order')));
              $id_carrier = $order['id_carrier'];
              $id_cart = $order['id_cart'];
          }
          elseif(!in_array($controller, array("orderopc", "order")) && $context && $context->cart) {
              $id_carrier = $context->cart->id_carrier;
              $id_cart = $context->cart->id;
          }
          else {
              $id_carrier = null;
              $id_cart = null;
          }

          $is_packetery_carrier = ($db->getValue('select 1 from `'._DB_PREFIX_.'packetery_carrier` where id_carrier=' . ((int) $id_carrier)) == 1);
          if($is_packetery_carrier) {
              $selected_branch = $db->getValue('select name_branch from `'._DB_PREFIX_.'packetery_order` where id_cart=' . ((int) $id_cart));
              if($selected_branch) {
                  $this->name .= " ($selected_branch)";
              }
          }
      }
  }
?>