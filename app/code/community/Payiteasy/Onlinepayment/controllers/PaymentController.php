<?php

class Payiteasy_Onlinepayment_PaymentController extends Mage_Core_Controller_Front_Action {
    
    /*
     * Onlinepayment redirect after clicking the return button
     *
     * just for standard payments (!)
     */
    public function responseAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = file_get_contents('php://input');
            Mage::getModel('onlinepayment/method_standard')->processResponse($data);
            exit(); //this is a POST request, made by directPOS server directly. we can stop here
        }

        /** GET request, customer is on this page (he clicked "Return to shop") **/
        $order = Mage::getModel('sales/order')->load(Mage::getSingleton('checkout/session')->getLastOrderId());

        if ($order->getOnlinepaymentStatusUpdate() != '1') {
            /* something went wrong with the payment */
            $order->cancel();
            $order->setStatus(Mage_Sales_Model_Order::STATE_CANCELED, true);

            $message = Mage::helper('onlinepayment')->__('Some transaction problems caused this order to change it\'s status to cancelled (did not receive payment response)');
            $order->addStatusHistoryComment($message);
            $order->save();
            return $this->_redirect('checkout/onepage/failure');
        }

        /* everything looks fine */
        return $this->_redirect('checkout/onepage/success');
    }
    
    /*
     * this method redirects the customer to the payment gateway to enter credentials there
     * SOAP is used 
     */
    public function redirectAction() {
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('Payiteasy_Onlinepayment_Block_Standard_Redirect','pay',array('template' => 'onlinepayment/standard/redirect.phtml'));
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }
    
    public function resultAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $lastOrderId = $session->getLastOrderId();
        
        $order = Mage::getModel('sales/order')->load($lastOrderId);
        $payment = $order->getPayment()->getMethodInstance();
        
        $code = $payment->getCode();
        
        if ($code == Payiteasy_Onlinepayment_Model_Method_Creditcard::METHOD_CODE || $code == Payiteasy_Onlinepayment_Model_Method_Debit::METHOD_CODE) {

            $model = Payiteasy_Onlinepayment_Model_Method_Abstract::factory($code);
            $responseBody = $model->buildResponse($lastOrderId);
            
            if (!empty($responseBody)) {
                $this->_processData($responseBody, $order);
            }
        }
    }
    
    private function _processData($xmlResponse, $order)
    {
        if (!empty($xmlResponse)) {

            $payment = $order->getPayment()->getMethodInstance();
            $code = $payment->getCode();
            $model = Payiteasy_Onlinepayment_Model_Method_Abstract::factory($code);
            
            preg_match('/<message>([^<]+)<\/message>/', $xmlResponse, $transaction);
            preg_match('/<rc>([^<]+)<\/rc>/', $xmlResponse, $m);
            
            if ($m[1] != '0000') {
                //cancel order
                $order->cancel();
                $order->setStatus(Mage_Sales_Model_Order::STATE_CANCELED, true);

                $message = Mage::helper('onlinepayment')->__('%s - Some transaction problems caused this order to change it\'s status to cancelled:', $model->getConfigData('title'));
                $message .= '<br />' . $transaction[1];
                $order->addStatusHistoryComment($message);
                $order->save();
                $this->_redirect('checkout/onepage/failure');
            } else {
                //redirect to success              
                if ($order->getEmailSent() != 1) {
                    $order->sendNewOrderEmail();
                }

                $message = Mage::helper('onlinepayment')->__('Payment success.');
                $order->addStatusHistoryComment($message);
                $order->save();
                
                $this->_redirect('checkout/onepage/success');
            }
        }
    }
}