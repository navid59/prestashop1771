<?php
/**
* 2007-2020 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
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

// Merging Order Status of Presta
define('confirmed', 2);
define('processing', 3);
define('canceled', 6);
define('paymentError', 8);
// define('refunded', 7);
// define('onBackorderPaid', 9);
// define('onBackorderNotPaid', 12);

class NetopiaConfirmationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        if (!isset($_GET['orderId']) || is_null($_GET['orderId'])) {
            return false;
        }

        $cart_id = Cart::getCartIdByOrderId($_GET['orderId']);
        $cart = new Cart((int) $cart_id);
        $customer = new Customer((int) $cart->id_customer);
        $secure_key = $customer->secure_key;


        /**
         * Since it's an example we are validating the order right here,
         * You should not do it this way in your own module.
         */
//        $payment_status = Configuration::get('PS_OS_PAYMENT'); // Default value for a payment that succeed.
        $message = null; // You can add a comment directly into the order so the merchant will see it in the BO.

        /**
         * Converting cart into a valid order
         */
        $module_name = $this->module->displayName;
        $currency_id = (int) Context::getContext()->currency->id;

        /**
         * If the order has been validated we try to retrieve it
         */
        $order_id = Order::getOrderByCartId((int) $cart->id);
       /**
        *  Get Order as Object 
        */
        $order = new Order((int)$order_id);
        $orderCurrentState = $order->current_state;
        // echo "<pre>";
        // die(print_r($order));

        if ($order_id && ($secure_key == $customer->secure_key)) {
            /**
             * The order has been placed so we redirect the customer on the confirmation page.
             */
            switch($orderCurrentState) {
                case 3: // Untifroud
                    $this->errors[] = $this->module->l('Thank you for the shoping.');
                    $this->errors[] = $this->module->l('Your, order need to be reviewing!!');
                    $this->errors[] = $this->module->l($order->reference);
                    $this->errors[] = $this->module->l($order->total_paid);
                    $this->errors[] = $this->module->l('We will let you know regarding the proccessing of your order');
                    $this->context->smarty->assign([
                        'errors' => $this->errors
                    ]);
                    return $this->setTemplate('module:netopia/views/templates/front/antifrauda.tpl');
                break;
                case 6: // canceled Order
                    $this->errors[] = $this->module->l('The order is canceled.');
                    $this->context->smarty->assign([
                        'errors' => $this->errors
                    ]);
                    return $this->setTemplate('module:netopia/views/templates/front/cancel.tpl');
                break;
                case 8: // Error payments
                    $this->errors[] = $this->module->l('An error is happening!!!!');
                    $this->context->smarty->assign([
                        'errors' => $this->errors
                    ]);
                    return $this->setTemplate('module:netopia/views/templates/front/error.tpl');
                break;
                default : 
                $module_id = $this->module->id;
                Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cart_id . '&id_module=' . $module_id . '&id_order=' . $order_id . '&key=' . $secure_key);
            }
        } else {
            /*
             * An error occured and is shown on a new page.
             */
            $this->errors[] = $this->module->l('An error occured. Please contact the merchant to have more informations');
            $this->context->smarty->assign([
                'errors' => $this->errors
            ]);
            return $this->setTemplate('module:netopia/views/templates/front/error.tpl');
        }
    }
}
