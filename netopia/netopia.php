<?php
/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.md.
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__).'/Payment/Request/Abstract.php');
require_once (dirname(__FILE__).'/Payment/Request/Card.php');
require_once (dirname(__FILE__).'/Payment/Request/Notify.php');
require_once (dirname(__FILE__).'/Payment/Address.php');
require_once (dirname(__FILE__).'/Payment/Invoice.php');


use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
class Netopia extends PaymentModule
{
    protected $config_form = false;
    protected $paymentUrl;

    public function __construct()
    {
        $this->name = 'netopia';
        $this->tab = 'payments_gateways';
        $this->version = '0.0.1';
        $this->author = 'NETOPIA Payments';
        $this->display = 'view';
        $this->need_instance = 1;
        $this->is_eu_compatible = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('NETOPIA Payments');
        $this->description = $this->l('NETOPIA Payments îți pune la dispoziție cele mai performante, competitive și inovative soluții de încasare a tranzacțiilor online. Ușor de integrat cu Prestashop.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall NETOPIA Payments?');
        $this->limited_countries = array('RO');
        $this->limited_currencies = array('RON');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->controllers = array('payment', 'validation');
        $this->module_link = $this->context->link->getAdminLink('AdminModules', true).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $this->errors = '';
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false)
        {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $iso_code = Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT'));

        if (in_array($iso_code, $this->limited_countries) == false)
        {
            $this->_errors[] = $this->l('This module is not available in your country');
            return false;
        }

        Configuration::updateValue('NETOPIA_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('actionPaymentCCAdd') &&
            $this->registerHook('actionPaymentConfirmation') &&
            $this->registerHook('displayPayment') &&
            $this->registerHook('displayPaymentReturn');
    }

    public function uninstall()
    {
        Configuration::deleteByName('NETOPIA_LIVE_MODE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitNetopiaModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

//        return $output; // Without Form
        return $output.$this->renderForm(); // With Setup
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitNetopiaModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        /**
         * Generate Config Admin Form
         */
        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of Configuration form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'NETOPIA_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    // array(
                    //     'col' => 3,
                    //     'type' => 'text',
                    //     'prefix' => '<i class="icon icon-envelope"></i>',
                    //     'desc' => $this->l('Enter a valid email address (Optional)'),
                    //     'name' => 'NETOPIA_ACCOUNT_EMAIL',
                    //     'label' => $this->l('Email / Username'),
                    // ),
                    // array(
                    //     'type' => 'password',
                    //     'desc' => $this->l('Enter your Password (Optional)'),
                    //     'name' => 'NETOPIA_ACCOUNT_PASSWORD',
                    //     'label' => $this->l('Password'),
                    // ),
                    array(
                        'col' => 6,
                        'type' => 'text',
                        'desc' => $this->l('Enter Signature Key from NETOPIA Payments Admin. XXXX-XXXX-XXXX-XXXX-XXXX'),
                        'name' => 'NETOPIA_SIGNATURE',
                        'label' => $this->l('Signature Key'),
                        'required' => true,
                    ),
                    array(
                        'type' => 'file',
                        'desc' => $this->l('Upload live Public Key'),
                        'name' => 'NETOPIA_LIVE_PUB_KEY',
                        'label' => $this->l('Live public key'),
                        'required' => true,
                    ),
                    array(
                        'type' => 'file',
                        'desc' => $this->l('Upload live Private Key'),
                        'name' => 'NETOPIA_LIVE_PRI_KEY',
                        'label' => $this->l('Live private key'),
                        'required' => true,
                    ),
                    array(
                        'type' => 'file',
                        'desc' => $this->l('Upload sandbox Public Key'),
                        'name' => 'NETOPIA_SAND_PUB_KEY',
                        'label' => $this->l('Sandbox public key'),
                    ),
                    array(
                        'type' => 'file',
                        'desc' => $this->l('Upload sandbox Private Key'),
                        'name' => 'NETOPIA_SAND_PRI_KEY',
                        'label' => $this->l('Sandbox private key'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the Configuration inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'NETOPIA_LIVE_MODE' => Configuration::get('NETOPIA_LIVE_MODE', true),
            'NETOPIA_ACCOUNT_EMAIL' => Configuration::get('NETOPIA_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'NETOPIA_ACCOUNT_PASSWORD' => Configuration::get('NETOPIA_ACCOUNT_PASSWORD', null),
            'NETOPIA_SIGNATURE' => Configuration::get('NETOPIA_SIGNATURE', null),
            'NETOPIA_LIVE_PUB_KEY' => Configuration::get('NETOPIA_LIVE_PUB_KEY', null),
            'NETOPIA_LIVE_PRI_KEY' => Configuration::get('NETOPIA_LIVE_PRI_KEY', null),
            'NETOPIA_SAND_PUB_KEY' => Configuration::get('NETOPIA_SAND_PUB_KEY', null),
            'NETOPIA_SAND_PRI_KEY' => Configuration::get('NETOPIA_SAND_PRI_KEY', null),
            // 'NETOPIA_conditions_complete_description' => Configuration::get('NETOPIA_conditions_complete_description', true),           // To Be Delete
            // 'NETOPIA_conditions_prices_currency' => Configuration::get('NETOPIA_conditions_prices_currency', 'contact@prestashop.com'), // To Be Delete
            // 'NETOPIA_conditions_clarity_contact' => Configuration::get('NETOPIA_conditions_clarity_contact', null),                     // To Be Delete
            // 'NETOPIA_conditions_forbidden_business' => Configuration::get('NETOPIA_conditions_forbidden_business', null),               // To Be Delete
            // 'NETOPIA_conditions_has_ssl' => Configuration::get('NETOPIA_conditions_has_ssl', null),                                     // To Be Delete
            // 'NETOPIA_conditions_logo_status' => Configuration::get('NETOPIA_conditions_logo_status', null),                             // To Be Delete
            // 'NETOPIA_terms_conditions_url' => Configuration::get('NETOPIA_terms_conditions_url', null),                                 // To Be Delete
            // 'NETOPIA_privacy_policy_url' => Configuration::get('NETOPIA_privacy_policy_url', null),                                     // To Be Delete
            // 'NETOPIA_delivery_policy_url' => Configuration::get('NETOPIA_delivery_policy_url', null),                                   // To Be Delete
            // 'NETOPIA_return_cancel_policy_url' => Configuration::get('NETOPIA_return_cancel_policy_url', null),                         // To Be Delete
            // 'NETOPIA_gdpr_policy_url' => Configuration::get('NETOPIA_gdpr_policy_url', null),                                           // To Be Delete
            // 'NETOPIA_image_netopia_logo_link' => Configuration::get('NETOPIA_image_netopia_logo_link', null),                           // To Be Delete
        );
    }


    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            if(in_array($key, array('NETOPIA_LIVE_PUB_KEY', 'NETOPIA_LIVE_PRI_KEY', 'NETOPIA_SAND_PUB_KEY', 'NETOPIA_SAND_PRI_KEY'))){
                if($_FILES[$key]['size']) {
                    $this->uploadeFile($key);
                    Configuration::updateValue($key, Tools::getValue($key));
                    // error_log(print_r('Form_values : '.$_FILES[$key]['tmp_name'].' => KEY : '. $_FILES[$key]['name'], true));
                }
            }else{
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }
    }

    /*
     * Uploade Certificate Files
     * */
    protected function uploadeFile($certificateName) {
        $target_dir = _PS_MODULE_DIR_.$this->name.'/certificates/';
        if (isset($_FILES[$certificateName]))
        {
            $target_file = $target_dir . basename($_FILES[$certificateName]["name"]);
            $uploadMsg = '';
            $uploadOk = 1;

            $check = filesize($_FILES[$certificateName]["tmp_name"]);
            $fileType = pathinfo($target_file,PATHINFO_EXTENSION);
            if($check !== false) {
                $uploadOk = 1;
            } else {
                $uploadMsg =  "review your chosen file.";
                $uploadOk = 0;
            }

            // Check if file already exists
            if (file_exists($target_file)) {
                $uploadMsg =  "File is overwritten.";
                $uploadOk = 1;
            }
            // Allow certain file formats
            if( $fileType != "cer" && $fileType != "key" && $fileType != "pem" ) {
                $uploadMsg =  "File Format is not accepted.";
                $uploadOk = 0;
            }
            // Check if $uploadOk is set to 0 by an error
            if ($uploadOk == 0) {
                $uploadMsg .= "Sorry, your file was not uploaded.";
            }
            else
            {
                if (move_uploaded_file($_FILES[$certificateName]["tmp_name"], $target_file))
                {
                    $uploadMsg =  "The file ". basename($_FILES[$certificateName]["name"]). " has been uploaded.";
                    $certificateName_location = basename($_FILES[$certificateName]["name"]);
                    return (array('status' => 1, 'msg'=> $uploadMsg));
                }
                else
                {
                    $uploadMsg = "Sorry, there was an error uploading your file, try again please.";
                    return (array('status' => 0, 'msg'=> $uploadMsg));
                }
            }

        }
    }


    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
//            $this->context->controller->addJquery();
            $this->context->controller->setMedia();
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');

        }
    }


    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    /**
     * Return payment options available for PS 1.7+
     *
     * @param array Hook parameters
     *
     * @return array|null
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        // Add Netopia option to list of Payment Method
        $option = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $option->setCallToActionText($this->l('NETOPIA Payments'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setAdditionalInformation($this->fetch('module:netopia/views/templates/hook/ntp_introduse.tpl'));

        return [
            $option
        ];
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function hookActionPaymentCCAdd()
    {
        /* Place your code here. */
        $cart_id = $this->context->cart->id;
        $customer_id = $this->context->customer->id;
        $amount = (float)$this->context->cart->getOrderTotal(false, Cart::BOTH);


        $deliveryAddressId = $this->context->cart->id_address_delivery;
        $invoiceAddressId = $this->context->cart->id_address_invoice;

        $invoiceCountry = new Country((int)$invoiceAddressId);
        $invoiceAddress = new Address((int)$invoiceAddressId );

        $deliveryCountry = new Country((int)$deliveryAddressId);
        $deliveryAddress = new Address((int)$deliveryAddressId );
        $formattedDeliveryAddress = AddressFormat::generateAddress($deliveryAddress, array(), '<br />', ' ');

        /*
         * NETOPIA PAYMENYT - API - START
         * */
        $this->paymentUrl = (Configuration::get('NETOPIA_LIVE_MODE')) ? 'https://secure.mobilpay.ro/' : 'https://sandboxsecure.mobilpay.ro/';
        $publickey  = (Configuration::get('NETOPIA_LIVE_MODE')) ? Configuration::get('NETOPIA_LIVE_PUB_KEY') : Configuration::get('NETOPIA_SAND_PUB_KEY');
        $x509FilePath 	= dirname(__FILE__) .'/certificates/'.$publickey;
        if(!file_exists($x509FilePath)) {
            throw new Exception("{$publickey}.php was not found");
        }

        try {
            $objPmReqCard = new Mobilpay_Payment_Request_Card();
            $objPmReqCard->signature = Configuration::get('NETOPIA_SIGNATURE', null);
            $objPmReqCard->orderId = Order::getOrderByCartId((int)($cart_id));
            $order = new Order($objPmReqCard->orderId);

            $objPmReqCard->confirmUrl = $this->getUrl().htmlentities('?fc=module&module=netopia&controller=ipn');
            $objPmReqCard->returnUrl = $this->getUrl().htmlentities('?fc=module&module=netopia&controller=confirmation');

            // Invoice Section
            $objPmReqCard->invoice = new Mobilpay_Payment_Invoice();
            $objPmReqCard->invoice->currency	= Context::getContext()->currency->iso_code;
            $objPmReqCard->invoice->amount		= (float)$this->context->cart->getOrderTotal(true, Cart::BOTH);
            $objPmReqCard->invoice->tokenId 	= $order->getUniqReference();
            $objPmReqCard->invoice->details		= 'Plata online cu cardul - '.$objPmReqCard->invoice->tokenId.' - PRESETASHOP';

            // Billing Section
            $billingAddress = new Mobilpay_Payment_Address();
            $billingAddress->type			    = ($invoiceAddress->company) ? "company": "person";
            $billingAddress->firstName		    = $invoiceAddress->firstname;
            $billingAddress->lastName		    = $invoiceAddress->lastname;
            $billingAddress->address		    = $invoiceAddress->address1 .' - '. $invoiceAddress->address2;
            $billingAddress->address		    .= $invoiceAddress->postcode .' - '. $invoiceAddress->city;
            $billingAddress->email			    = $this->context->customer->email;
            $billingAddress->mobilePhone		= ($invoiceAddress->phone) ? $invoiceAddress->phone : $invoiceAddress->phone_mobile;
            $objPmReqCard->invoice->setBillingAddress($billingAddress);

            // Billing Section
            $shippingAddress 				= new Mobilpay_Payment_Address();
            $shippingAddress->type			= ($deliveryAddress->company) ? "company": "person";
            $shippingAddress->firstName		= $deliveryAddress->firstname;
            $shippingAddress->lastName		= $deliveryAddress->lastname;
            $shippingAddress->address		= $deliveryAddress->address1.' - '.$deliveryAddress->address2;
            $shippingAddress->email			= $this->context->customer->email;
            $shippingAddress->mobilePhone	= ($deliveryAddress->phone) ? $deliveryAddress->phone : $deliveryAddress->phone_mobile;
            $objPmReqCard->invoice->setShippingAddress($shippingAddress);

        } catch (\Exception $e) {
            echo ('Oops, Something happen!!! ');
        }

        $objPmReqCard->encrypt($x509FilePath);
        $this->context->smarty->assign([
            'paymentUrl' => $this->paymentUrl,
            'env_key' => $objPmReqCard->getEnvKey(),
            'data' => $objPmReqCard->getEncData()
        ]);

        /*
         * NETOPIA PAYMENYT - API - START
         * */
    }

    public function hookActionPaymentConfirmation()
    {
        /* Place your code here. */
        //echo "ActionPaymentConfirmation";
    }

    public function hookDisplayPayment()
    {
        /* Place your code here. */
        echo "DisplayPayment";
    }

    public function hookDisplayPaymentReturn()
    {
        /* custom Payment Return Template */
        $this->context->smarty->assign(array(

        ));
    }

    function getUrl(){
        if(isset($_SERVER['HTTPS'])){
            $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
        }
        else{
            $protocol = 'http';
        }
        return $protocol . "://" . $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
    }

    public function test() {
        die('TEST METHOD IN NETOPIA.PHP');
    }

}
