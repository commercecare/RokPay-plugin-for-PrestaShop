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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
require_once "RokpayFun.php";
if (!defined('_PS_VERSION_'))
{
    exit;
}

class Rokpay extends PaymentModule
{
    const FLAG_DISPLAY_PAYMENT_INVITE = 'ROKPAY_PAYMENT_INVITE';

    protected $_html = '';
    protected $_postErrors = [];

    public $apikey;
    public $shopnumber;

    public $extra_mail_vars;
    /**
     * @var int
     */
    public $is_eu_compatible;

    public function __construct()
    {
        $this->name = 'rokpay';
        $this->tab = 'payments_gateways';
        $this->version = '2.1.1';
        $this->ps_versions_compliancy = ['min' => '1.7.6.0', 'max' => _PS_VERSION_];
        $this->author = 'PrestaShop';
        $this->controllers = ['payment', 'validation'];
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        /*$config = Configuration::getMultiple(['BANK_WIRE_DETAILS', 'BANK_WIRE_OWNER', 'BANK_WIRE_ADDRESS', 'BANK_WIRE_RESERVATION_DAYS']);*/
        $config = Configuration::getMultiple(['ROKPAY_SHOP_NUMBER', 'ROKPAY_API_KEY','ROKPAY_API_ENVIRONMENT']);
        if (!empty($config['ROKPAY_SHOP_NUMBER']))
        {
            $this->shopnumber = $config['ROKPAY_SHOP_NUMBER'];
        }
        if (!empty($config['ROKPAY_APY_KEY']))
        {
            $this->apikey = $config['ROKPAY_API_KEY'];
        }
        if (!empty($config['ROKPAY_API_ENVIRONMENT']))
        {
            $this->environment = $config['ROKPAY_API_ENVIRONMENT'];
        }
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Rokpay', [], 'Modules.Rokpay.Admin');
        $this->description = $this->trans('Accept payments by redirecting to Rokpay during the checkout.', [], 'Modules.Rokpay.Admin');
        $this->confirmUninstall = $this->trans('Are you sure about removing these details?', [], 'Modules.Rokpay.Admin');
        if (!isset($this->shopnumber) || !isset($this->apikey))
        {
            $this->warning = $this->trans('Shopnumber and Apikey details must be configured before using this module.', [], 'Modules.Rokpay.Admin');
        }
        if (!count(Currency::checkPaymentCurrencies($this->id)) && $this->active)
        {
            $this->warning = $this->trans('No currency has been set for this module.', [], 'Modules.Rokpay.Admin');
        }

        $this->extra_mail_vars = ['{shopnumber}' => $this->shopnumber, '{apikey}' => nl2br($this->apikey) ,

        ];
    }
    public function hookOrderConfirmation($params)
    {

    }
    public function addOrderState($name)
    {
        $state_exist = false;
        $states = OrderState::getOrderStates((int)$this
            ->context
            ->language
            ->id);
        $state_id = 0;
        // check if order state exist
        foreach ($states as $state)
        {
            if (in_array($name, $state))
            {
                $state_exist = true;
                $state_id = $state['id_order_state'];
                break;
            }
        }

        // If the state does not exist, we create it.
        if (!$state_exist)
        {
            // create new order state
            $order_state = new OrderState();
            $order_state->color = '#00ffff';
            $order_state->send_email = false;
            $order_state->module_name = $this->name;
            //$order_state->template = 'name of your email template';
            $order_state->name = array();
            $languages = Language::getLanguages(false);
            foreach ($languages as $language) $order_state->name[$language['id_lang']] = $name;

            // Update object
            $order_state->add();
            $state_id = $order_state->id;
        }

        return $state_id;

    }
    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS " . _DB_PREFIX_ . "rokpay_payments (id int(11) unsigned NOT NULL  AUTO_INCREMENT, id_cart int(10) unsigned NOT NULL, `status` enum('sucess','cancel','fail','start') NOT NULL DEFAULT 'start',date_added datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, date_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,id_customer int(10) unsigned NOT NULL,id_transaction varchar(15) NOT NULL, id_order int(10) unsigned DEFAULT NULL, PRIMARY KEY (id)) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=UTF8;";
        $rok = new RokpayFun();
        Configuration::updateValue('ROKPAY_API_ENVIRONMENT', $rok->getDefault());
        try
        {
            Db::getInstance()->execute($sql);

        }
        catch(Exception $e)
        {
            return false;
        }

        if (!parent::install() || !$this->registerHook('displayPaymentReturn') || !$this->registerHook('paymentOptions') || !$this->registerHook('displayOrderConfirmation'))
        {
            return false;
        }

        Configuration::updateValue('ROKPAY_ORDER_STATUS', $this->addOrderState('Rokpay processing'));
        return true;
    }

    public function uninstall()
    { //ROKPAY_SHOP_NUMBER', 'ROKPAY_API_KEY'
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'rokpay_payments`';
        if (Db::getInstance()->execute($sql) == false)
        {
            return false;
        }

        if (!Configuration::deleteByName('ROKPAY_SHOP_NUMBER') || !Configuration::deleteByName('ROKPAY_API_KEY')||!Configuration::deleteByName('ROKPAY_API_ENVIRONMENT') || !parent::uninstall())
        {
            return false;
        }

        return true;
    }

    protected function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit'))
        {

            if (!Tools::getValue('ROKPAY_SHOP_NUMBER'))
            {
                $this->_postErrors[] = $this->trans('Shop number is required.', [], 'Modules.Rokpay.Admin');
            }
            elseif (!Tools::getValue('ROKPAY_API_KEY'))
            {
                $this->_postErrors[] = $this->trans('API key is required.', [], 'Modules.Rokpay.Admin');
            }
             elseif (!Tools::getValue('ROKPAY_API_ENVIRONMENT'))
            {
                $this->_postErrors[] = $this->trans('please select environment', [], 'Modules.Rokpay.Admin');
            }
        }
    }

    protected function _postProcess()
    {
        
        if (Tools::isSubmit('btnSubmit'))
        {

            Configuration::updateValue('ROKPAY_SHOP_NUMBER', Tools::getValue('ROKPAY_SHOP_NUMBER'));
            Configuration::updateValue('ROKPAY_API_KEY', Tools::getValue('ROKPAY_API_KEY'));
            Configuration::updateValue('ROKPAY_API_ENVIRONMENT', Tools::getValue('ROKPAY_API_ENVIRONMENT'));
          
        }
        $this->_html .= $this->displayConfirmation($this->trans('Settings updated', [], 'Admin.Global'));
    }

    protected function _displayRokpay()
    {
        return $this->display(__FILE__, 'infos.tpl');
    }

    public function getContent()
    {

        $errors = array();
        $success = false;
        $rok = new RokpayFun();
        $environment = $rok->getEnvironmentURL();        

        if (Tools::isSubmit('btnSubmit'))
        {

            if (empty(Tools::getValue('ROKPAY_SHOP_NUMBER')) || empty(Tools::getValue('ROKPAY_API_KEY')))
            {
                $errors[] = "Kindly add shop number and api key";
            }
            else
            {
                $this->_postProcess();
                $success = true;
            }

        }
        elseif (Tools::isSubmit('btnTestAPI'))
        {

            $shopnumber = Tools::getValue('ROKPAY_SHOP_NUMBER');
            $apikey = Tools::getValue('ROKPAY_API_KEY');
            $url = Tools::getValue('ROKPAY_API_ENVIRONMENT');
            $response = $rok->testAPI($shopnumber, $apikey,$url);
            $api_success = true;

            if (array_key_exists("error", get_object_vars($response)))
            {
                $api_success = false;
            }
              if (array_key_exists("exception", get_object_vars($response)))
            {
                $api_success = false;
            }
            elseif (array_key_exists("orderRequest", get_object_vars($response)))
            {
                $api_success = true;

            }

            $this
                ->context
                ->smarty
                ->assign("api_success", $api_success);

        }
        $this
                ->context
                ->smarty
                ->assign($environment);

        $tplVars = array(
            'errors' => $errors,
            "success" => $success,
            "selected_env"=>Configuration::get('ROKPAY_API_ENVIRONMENT'),
        );

        $this
            ->context
            ->smarty
            ->assign($tplVars);
        return $this->display(__FILE__, 'views/templates/admin/settings.tpl');
        //return $this->_html;
        
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active)
        {
            return [];
        }

        if (!$this->checkCurrency($params['cart']))
        {
            return [];
        }

        $this
            ->smarty
            ->assign($this->getTemplateVarInfos());

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
            ->setCallToActionText($this->trans('Pay by Rokpay', [], 'Modules.Rokpay.Shop'))
            ->setAction($this
            ->context
            ->link
            ->getModuleLink($this->name, 'validation', [], true))
            ->setAdditionalInformation($this->fetch('module:rokpay/views/templates/hook/rokpay_intro.tpl'));
        $payment_options = [$newOption, ];

        return $payment_options;
    }

    public function hookPaymentReturn($params)
    {

        $state = $params['order']->getCurrentState();
        //ROKPAY_SHOP_NUMBER', 'ROKPAY_API_KEY'
        if (in_array($state, [Configuration::get('ROKPAY_SHOP_NUMBER') , Configuration::get('ROKPAY_API_KEY') ,

        ]))
        {
            $apikey = $this->apikey;

            $shopnumber = $this->shopnumber;
            $totalToPaid = $params['order']->getOrdersTotalPaid() - $params['order']->getTotalPaid();
            $this
                ->smarty
                ->assign(['shop_name' => $this
                ->context
                ->shop->name, 'total' => $this
                ->context
                ->getCurrentLocale()
                ->formatPrice($totalToPaid, (new Currency($params['order']->id_currency))
                ->iso_code) , 'apikey' => $apikey, 'shopnumber' => $shopnumber, 'status' => 'ok', 'reference' => $params['order']->reference, 'contact_url' => $this
                ->context
                ->link
                ->getPageLink('contact', true) , ]);
        }
        else
        {
            $this
                ->smarty
                ->assign(['status' => 'failed', 'contact_url' => $this
                ->context
                ->link
                ->getPageLink('contact', true) , ]);
        }

        return $this->fetch('module:rokpay/views/templates/hook/payment_return.tpl');
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module))
        {
            foreach ($currencies_module as $currency_module)
            {
                if ($currency_order->id == $currency_module['id_currency'])
                {
                    return true;
                }
            }
        }

        return false;
    }

    public function renderForm()
    {
        $fields_form = ['form' => ['legend' => ['title' => $this->trans('Rokpay details', [], 'Modules.Rokpay.Admin') , 'icon' => 'icon-envelope', ], 'input' => [['type' => 'text', 'label' => $this->trans('Rokpay shopnumber', [], 'Modules.Rokpay.Admin') , 'name' => 'ROKPAY_SHOP_NUMBER', 'required' => true, ], ['type' => 'password', 'label' => $this->trans('Rokpay api key', [], 'Modules.Rokpay.Admin') , 'name' => 'ROKPAY_API_KEY', 'desc' => $this->trans('Add rokpay api key', [], 'Modules.Rokpay.Admin') , 'required' => true, 'value' => "passowrd"], ], 'submit' => ['title' => $this->trans('Save', [], 'Admin.Actions') , ], ], ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;

        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this
            ->context
            ->link
            ->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = ['fields_value' => $this->getConfigFieldsValues() , 'languages' => $this
            ->context
            ->controller
            ->getLanguages() , 'id_language' => $this
            ->context
            ->language->id, ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {

        $languages = Language::getLanguages(false);

        return ['ROKPAY_API_KEY' => Configuration::get('ROKPAY_API_KEY') , 'ROKPAY_SHOP_NUMBER' => Configuration::get('ROKPAY_SHOP_NUMBER') ,
        'ROKPAY_API_ENVIRONMENT' => Configuration::get('ROKPAY_API_ENVIRONMENT')
        ];
    }

    public function getTemplateVarInfos()
    {
        $cart = $this
            ->context->cart;
        $total = sprintf($this->trans('%1$s (tax incl.)', [], 'Modules.Rokpay.Shop') , $this
            ->context
            ->getCurrentLocale()
            ->formatPrice($cart->getOrderTotal(true, Cart::BOTH) , $this
            ->context
            ->currency
            ->iso_code));

        $apikey = $this->apikey;
        $shopnumber = $this->shopnumber;

        return ['total' => $total, 'apikey' => $apikey, 'shopnumber' => $shopnumber, ];
    }
}

