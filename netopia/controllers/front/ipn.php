<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (_PS_MODULE_DIR_.'netopia/Payment/Request/Abstract.php');
require_once (_PS_MODULE_DIR_.'netopia/Payment/Request/Card.php');
require_once (_PS_MODULE_DIR_.'netopia/Payment/Request/Notify.php');
require_once (_PS_MODULE_DIR_.'netopia/Payment/Address.php');
require_once (_PS_MODULE_DIR_.'netopia/Payment/Invoice.php');

// Merging Order Status of Presta
define('confirmed', 2);
define('processing', 3);
define('canceled', 6);
define('paymentError', 8);
define('refunded', 7);
define('onBackorderPaid', 9);
define('onBackorderNotPaid', 12);

class NetopiaIpnModuleFrontController extends ModuleFrontController
{
    public $errorCode;
    public $errorType;
    public $errorMessage;

    public function postProcess()
    {
        /*
         * Oops, an error occured.
         */
        if (Tools::getValue('action') == 'error') {
            //return $this->displayError('An error occurred while trying to redirect the customer');
        } else {
            $this->errorCode 		= 0;
            $this->errorType		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_NONE;
            $this->errorMessage	= '';

            if (strcasecmp($_SERVER['REQUEST_METHOD'], 'post') == 0)
            {
                if(isset($_POST['env_key']) && isset($_POST['data']))
                    {
                        $privateKey  = (Configuration::get('NETOPIA_LIVE_MODE')) ? Configuration::get('NETOPIA_LIVE_PRI_KEY') : Configuration::get('NETOPIA_SAND_PRI_KEY');
                        $privateKeyFilePath = _PS_MODULE_DIR_ .'netopia/certificates/'.$privateKey;
                        if(!file_exists($privateKeyFilePath)) {
                            throw new Exception("{$privateKey}.php was not found");
                        }
                        // Test Manual to add order on IPN
                        //$this->module->validateOrder(10000, 'NTP_PS_OS_PENDING', 10.20, $this->module->displayName, 'NAVID TEST MANUAL', array(), 1, false, Context::getContext()->customer->secure_key);

                        try
                        {
                            $objPmReq = Mobilpay_Payment_Request_Abstract::factoryFromEncrypted($_POST['env_key'], $_POST['data'], $privateKeyFilePath);
                            $rrn = $objPmReq->objPmNotify->rrn;
                            if ($objPmReq->objPmNotify->errorCode == 0) {
                                $this->setLog(array('#ACTION - ', $objPmReq->objPmNotify->action)); // LOGURI
                                switch($objPmReq->objPmNotify->action)
                                {
                                    case 'confirmed':
                                        //update DB, SET status = "confirmed/captured"
                                        $ntpStatus = confirmed;
                                        $this->errorMessage = $objPmReq->objPmNotify->errorMessage;
                                        break;
                                    case 'confirmed_pending':
                                        //update DB, SET status = "pending"
                                        $ntpStatus = processing;
                                        $this->errorMessage = $objPmReq->objPmNotify->errorMessage;
                                        break;
                                    case 'paid_pending':
                                        //update DB, SET status = "pending2"
                                        $ntpStatus = onBackorderNotPaid;
                                        $this->errorMessage = $objPmReq->objPmNotify->errorMessage;
                                        break;
                                    // case 'paid':
                                    //     //update DB, SET status = "open/preauthorized"
                                    //     $ntpStatus = onBackorderPaid;
                                    //     $errorMessage = $objPmReq->objPmNotify->errorMessage;
                                    //     break;
                                    case 'canceled':
                                        //update DB, SET status = "canceled"
                                        $ntpStatus = canceled;
                                        $this->errorMessage = $objPmReq->objPmNotify->errorMessage;
                                        break;
                                    case 'credit':
                                        //update DB, SET status = "refunded"
                                        $ntpStatus = refunded;
                                        $this->errorMessage = $objPmReq->objPmNotify->errorMessage;
                                        break;
                                    default:
                                        $ntpStatus = paymentError;
                                        $this->errorType		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
                                        $this->errorCode 		= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_ACTION;
                                        $this->errorMessage 	= 'mobilpay_refference_action paramaters is invalid';
                                        break;
                                }
                                /*
                                 * Update Order Status
                                 * */
                                $history           = new OrderHistory();
                                $history->id_order = $objPmReq->orderId;
                                $history->changeIdOrderState($ntpStatus, (int)($history->id_order));
                            }else{
                                if( $objPmReq->objPmNotify->action == "paid") {
                                    switch ($objPmReq->objPmNotify->errorMessage) {
                                        case 'Cardul nu permite plata online.':
                                        case 'Cod CVV2/CCV incorect':
                                        case 'Fonduri insuficiente.':
                                        case 'Card expirat':
                                        case ' ':
                                            $ntpStatus = paymentError;
                                            $this->errorMessage = $objPmReq->objPmNotify->errorMessage;
                                        break;
                                        default :
                                            $this->setLog('Opps, Action is "paid" and message is: '.$objPmReq->objPmNotify->errorMessage.'BUT...!!!');    // LOGURI  
                                    }
                                    $this->setLog($objPmReq->objPmNotify->errorMessage);    // LOGURI 
                                    
                                    /*
                                    * Update Order Status in Order un success situations
                                    * */
                                    $history           = new OrderHistory();
                                    $history->id_order = $objPmReq->orderId;
                                    $history->changeIdOrderState($ntpStatus, (int)($history->id_order));
                                } else {
                                    $this->setLog('Opps, Action is "NOT PAID!!!" , and the ACTION is :'.$objPmReq->objPmNotify->action);          // LOGURI 
                                    $this->setLog('Action is "NOT PAID!!!", and MESSAGE is :'.$objPmReq->objPmNotify->errorMessage);              // LOGURI 
                                }
                            }
                        }catch (Exception $e){
                            $this->errorType 		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_TEMPORARY;
                            $this->errorCode		= $e->getCode();
                            $this->errorMessage 	= $e->getMessage();
                        }
                    }
                else{
                    $this->errorType 		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
                    $this->errorCode		= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_POST_PARAMETERS;
                    $this->errorMessage 	= 'mobilpay.ro posted invalid parameters';
                }
            }else {
                $this->errorType 		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
                $this->errorCode		= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_POST_METHOD;
                $this->errorMessage 	= 'invalid request metod for payment confirmation';
            }

            /**
             * The following method will generate thx XML inside the Controller
             */
            header('Content-type: application/xml');
            echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
            if($this->errorCode == 0)
            {
                echo "<crc>{$this->errorMessage}</crc>";
            }
            else
            {
                echo "<crc error_type=\"{$this->errorType}\" error_code=\"{$this->errorCode}\">{$this->errorMessage}</crc>";
            }

            /** 
             * The following method by geting help  from CONTEXT
             * Can be used to generate XML in "ipn.tpl"
            */
            // $this->context->smarty->assign(array(
            // 'errorMessage' => $this->errorMessage,
            // 'errorType' => $this->errorType ? $this->errorType : '',
            // 'errorCode' => $this->errorCode ? $this->errorCode : '',
            // 'secure_key' => Context::getContext()->customer->secure_key,
            // ));

            /**
             *  get the templet and send parameters define in CONTEXT
             *  curently ipn.tpl is empty
             */
            return $this->setTemplate('module:netopia/views/templates/front/ipn.tpl'); 
        }
    }

    public function setLog($obj){
        try {
            $log_file = "./netopia-errors.log";       
            $str = serialize($obj);
            error_log("[".date("F j, Y, g:i:s a")."] ".$str.PHP_EOL, 3, $log_file);
        } catch (Exception $e) {
            print $e->getMessage();
        }
    }

    /*
    public function makeOrder() {
        //
        // Get Cart data,
        //
       $cart_id = $this->context->cart->id;
       $customer_id = $this->context->customer->id;
       $amount = (float)$this->context->cart->getOrderTotal(false, Cart::BOTH);

       //
       // Restore the context from the $cart_id & the $customer_id to process the validation properly.
       //

       Context::getContext()->cart = new Cart((int) $cart_id);
       Context::getContext()->customer = new Customer((int) $customer_id);
       Context::getContext()->currency = new Currency((int) Context::getContext()->cart->id_currency);
       Context::getContext()->language = new Language((int) Context::getContext()->customer->id_lang);

       $secure_key = Context::getContext()->customer->secure_key;

       if ($this->isValidOrder() === true) {
           $payment_status = Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'); // Status of Order after Place Order & Befor payment
           $message = null;
       } else {
           $payment_status = Configuration::get('PS_OS_ERROR');

           //
           // Add a message to explain why the order has not been validated
           //

           $message = $this->module->l('An error occurred while processing payment');
       }

       $module_name = $this->module->displayName;
       $currency_id = (int) Context::getContext()->currency->id;

       $this->module->validateOrder($cart_id, $payment_status, $amount, $module_name, $message, array(), $currency_id, false, $secure_key);

       //
       // Order have to be added Now
       //
   }

   protected function isValidOrder()
        {
            //
            // Add your checks right there
            //

            if($this->context->cart->id){
                if($this->context->cart->OrderExists() == false){
                    return true;
                }
            } else {
                Tools::redirect($this->context->link->getPageLink('order'));
                return false;
            }
        }
    */
}