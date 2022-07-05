<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
/**
 * @since 1.0.0
 *
 * @property Rokpay $module
 */
include_once(_PS_MODULE_DIR_.'rokpay/RokpayFun.php');

class RokpayValidationModuleFrontController extends ModuleFrontController {   
    

    
    public function success($shopOrderId) {
        if (!($this->module instanceof Rokpay)) {
            Tools::redirect('index.php?controller=order&step=1');
            return;
        }
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
            return;
        }
        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'rokpay') {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            die($this->trans('This payment method is not available.', [], 'Modules.Checkpayment.Shop'));
        }
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
            return;
        }
        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $mailVars = [];
        $this->module->validateOrder((int)$cart->id, Configuration::get('ROKPAY_ORDER_STATUS'), $total, $this->module->displayName, null, $mailVars, (int)$currency->id, false, $customer->secure_key);
        $customer_order_id = $this->module->currentOrder;
        $rokpay = new RokpayFun();
        $rokpay->updatetData($customer_order_id, $shopOrderId);
        Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (int)$cart->id . '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
    }
    public function fail($shopOrderId) {
         Db::getInstance()->update('rokpay_payments', array("status"=>"fail"), 'id = ' . (int)$shopOrderId);
        Tools::redirect('index.php?fc=module&module=rokpay&controller=response&action=fail');
    }
    public function cancel($shopOrderId) {
       
        Db::getInstance()->update('rokpay_payments', array("status"=>"cancel"), 'id = ' . (int)$shopOrderId);
        Tools::redirect('index.php?fc=module&module=rokpay&controller=response&action=cancel');
    }
    public function postProcess() {
        if (Tools::getValue('response') == "success") {
            $this->success($_GET['shopOrderId']);
        } elseif (Tools::getValue('response') == "fail") {
            $this->fail($_GET['shopOrderId']);
        } elseif (Tools::getValue('response') == "cancel") {
            $this->cancel($_GET['shopOrderId']);
        }
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'rokpay') {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            exit($this->module->getTranslator()->trans('This payment method is not available.', [], 'Modules.Rokpay.Shop'));
        }



        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $mailVars = [];
        /*echo $cart->id."=".$this->module->currentOrder."=".$customer->secure_key."=".$total;*/
        $rokpay = new RokpayFun();
        $data = $rokpay->insertData($cart->id, $customer);
        $rokpay->verifyShop($cart, $data);
    }
}
