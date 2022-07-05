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
class RokpayResponseModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
            
        $check =  Tools::getValue('action');
         parent::initContent();
        if($check == 'cancel')
        {
             //$this->setTemplate('rokpay_intro.tpl');
           
            //echo 'da';
            //die;
            $this->setTemplate('module:rokpay/views/templates/hook/rokpay_cancel.tpl');
            //return $this->display(__FILE__, '../rokpay_intro.tpl');
        }
        else
        {
            $this->setTemplate('module:rokpay/views/templates/hook/rokpay_fail.tpl');
        }
        //echo 'response';
        //die;
    }
}
