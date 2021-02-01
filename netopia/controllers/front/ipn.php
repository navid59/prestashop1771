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
//define('NTP_PS_OS_PENDING', 14);
//define('NTP_PS_OS_PROCESSING', 15);

class NetopiaIpnModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        /*
         * Oops, an error occured.
         */
        if (Tools::getValue('action') == 'error') {
            //return $this->displayError('An error occurred while trying to redirect the customer');
        } else {
            $errorCode 		= 0;
            $errorType		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_NONE;
            $errorMessage	= '';

            if (strcasecmp($_SERVER['REQUEST_METHOD'], 'post') == 0)
            {
                if(isset($_POST['env_key']) && isset($_POST['data']))
                    {
                        $privateKeyFilePath = _PS_MODULE_DIR_ .'netopia/certificates/sandbox.YN8Q-RH4J-39C1-FPAG-2P8Aprivate.key';
                        try
                        {
                            $objPmReq = Mobilpay_Payment_Request_Abstract::factoryFromEncrypted($_POST['env_key'], $_POST['data'], $privateKeyFilePath);
                            $rrn = $objPmReq->objPmNotify->rrn;
                            if ($objPmReq->objPmNotify->errorCode == 0) {
                                switch($objPmReq->objPmNotify->action)
                                {
                                    case 'confirmed':
                                        //update DB, SET status = "confirmed/captured"
                                        $ntpStatus = confirmed;
                                        $errorMessage = $objPmReq->objPmNotify->errorMessage;
                                        break;
                                    case 'confirmed_pending':
                                        //update DB, SET status = "pending"
                                        $ntpStatus = processing;
                                        $errorMessage = $objPmReq->objPmNotify->errorMessage;
                                        break;
                                    case 'paid_pending':
                                        //update DB, SET status = "pending2"
                                        $ntpStatus = onBackorderNotPaid;
                                        $errorMessage = $objPmReq->objPmNotify->errorMessage;
                                        break;
                                    case 'paid':
                                        //update DB, SET status = "open/preauthorized"
                                        $ntpStatus = onBackorderPaid;
                                        $errorMessage = $objPmReq->objPmNotify->errorMessage;
                                        break;
                                    case 'canceled':
                                        //update DB, SET status = "canceled"
                                        $ntpStatus = canceled;
                                        $errorMessage = $objPmReq->objPmNotify->errorMessage;
                                        break;
                                    case 'credit':
                                        //update DB, SET status = "refunded"
                                        $ntpStatus = refunded;
                                        $errorMessage = $objPmReq->objPmNotify->errorMessage;
                                        break;
                                    default:
                                        $ntpStatus = paymentError;
                                        $errorType		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
                                        $errorCode 		= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_ACTION;
                                        $errorMessage 	= 'mobilpay_refference_action paramaters is invalid';
                                        break;
                                }
                                /*
                                 * Update Order Status - Start
                                 * */

                                $history           = new OrderHistory();
                                $history->id_order = $objPmReq->orderId;
                                $history->changeIdOrderState($ntpStatus, (int)($history->id_order));

                                /*
                                 * Update Order Status - End
                                 * */
                            }else{
                                //update DB, SET status = "rejected"
                                $errorMessage = $objPmReq->objPmNotify->errorMessage;
                            }
                        }catch (Exception $e){
                            $errorType 		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_TEMPORARY;
                            $errorCode		= $e->getCode();
                            $errorMessage 	= $e->getMessage();
                        }
                    }
                else{
                    $errorType 		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
                    $errorCode		= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_POST_PARAMETERS;
                    $errorMessage 	= 'mobilpay.ro posted invalid parameters';
                }
            }else {
                $errorType 		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
                $errorCode		= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_POST_METHOD;
                $errorMessage 	= 'invalid request metod for payment confirmation';
            }

            header('Content-type: application/xml');
            echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
            if($errorCode == 0)
            {
                echo "<crc>{$errorMessage}</crc>";
            }
            else
            {
                echo "<crc error_type=\"{$errorType}\" error_code=\"{$errorCode}\">{$errorMessage}</crc>";
            }

            ////////////////////////////////////////////////////////////////////////////////////////
            $this->context->smarty->assign(array(
                'errorMessage' => $errorMessage,
                'errorType' => $errorType ? $errorType : '',
                'errorCode' => $errorCode ? $errorCode : '',
                'secure_key' => Context::getContext()->customer->secure_key,
            ));

            return $this->setTemplate('module:netopia/views/templates/front/ipn.tpl');
            ////////////////////////////////////////////////////////////////////////////////////////
        }
    }
}