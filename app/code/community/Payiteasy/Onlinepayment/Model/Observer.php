<?php

class Payiteasy_Onlinepayment_Model_Observer
{

    public function sendPayment($observer)
    {
        $order = $observer->getEvent()->getOrder();

        $payment = $order->getPayment()->getMethodInstance();

        $allowed = array(
            Payiteasy_Onlinepayment_Model_Method_Creditcard::METHOD_CODE,
            Payiteasy_Onlinepayment_Model_Method_Debit::METHOD_CODE
        );

        if (in_array($payment->getCode(), $allowed)) {
            $paymentMethod = Payiteasy_Onlinepayment_Model_Method_Abstract::factory($payment->getCode());
            $paymentMethod->buildRequestCapture($order);
            return true;
        }
    }

    /**
     * refund action, triggers a 'reversal' process on directPOS
     *
     * refund is possible on any payment implemented via this service -
     *
     * triggered when shop admin cancels the order
     *
     * @param $observer
     * @return bool
     */
    public function reversal($observer)
    {
        /* @var $payment Mage_Sales_Model_Order_Payment */
        $payment = $observer->getPayment();
        $order = $payment->getOrder();
        /* @var $methodInstance Payiteasy_Onlinepayment_Model_Method_Abstract */
        $methodInstance = $payment->getMethodInstance();

        $allowed = array(
            Payiteasy_Onlinepayment_Model_Method_Creditcard::METHOD_CODE,
            Payiteasy_Onlinepayment_Model_Method_Standard::METHOD_CODE,
            Payiteasy_Onlinepayment_Model_Method_Debit::METHOD_CODE,
        );
        if (!in_array($payment->getMethod(), $allowed)) {
            return;
        }

        try {
            $actionMode = $payment->getData('onlinepayment_action_mode');
            if (empty($actionMode)) {
                $actionMode = $methodInstance->getActionMode();
            }
            $canSendReversal = true;
            switch ($actionMode) {
                case Payiteasy_Onlinepayment_Model_Method_Abstract::REQUEST_TYPE_AUTH:
                    /* in case of authorized transactions, only allow reversals of orders made in the last 24 hrs */
                    $createdAt = strtotime($order->getCreatedAt());
                    $now = time();
                    if ($now - $createdAt > (Payiteasy_Onlinepayment_Model_Method_Abstract::AUTHORIZED_REVERSAL_LIMIT_HOURS * 3600)) { /* older than 1 day */
                        $canSendReversal = false;
                    }
                    break;
                default:
                    break;
            }
            if (!$canSendReversal) {
                $message = Mage::helper('onlinepayment')->__('This order has been made more than %s hours ago, skipping the REVERSAL-request', Payiteasy_Onlinepayment_Model_Method_Abstract::AUTHORIZED_REVERSAL_LIMIT_HOURS);
                $order->addStatusHistoryComment($message)
                    ->save();
                return;
            }

            $response = $methodInstance->sendReversal($order, true);

            if (! $response || $response->rc != '0000') {
                $message = Mage::helper('onlinepayment')->__('Problems at reversal. Error code: ') . ($response ? $response->rc : 'empty response');
            } else {
                $message = Mage::helper('onlinepayment')->__('DirectPOS Reversal - request completed');
            }
            $order->addStatusHistoryComment($message)
                ->save();
            return true;
        } catch (Exception $e) {

        }
    }

    /**
     * capture a pre-authorized payment
     * @param $observer
     */
    public function capture($observer)
    {
        /* @var $payment Mage_Sales_Model_Order_Payment */
        $payment = $observer->getPayment();
        $order = $payment->getOrder();
        /* @var $methodInstance Payiteasy_Onlinepayment_Model_Method_Abstract */
        $methodInstance = $payment->getMethodInstance();

        $allowed = array(
            Payiteasy_Onlinepayment_Model_Method_Creditcard::METHOD_CODE,
            Payiteasy_Onlinepayment_Model_Method_Standard::METHOD_CODE,
            Payiteasy_Onlinepayment_Model_Method_Debit::METHOD_CODE,
        );
        if (!in_array($payment->getMethod(), $allowed)) {
            return;
        }
        $actionMode = $payment->getData('onlinepayment_action_mode');
        if (empty($actionMode)) {
            $actionMode = $methodInstance->getActionMode();
        }
        if ($actionMode == Payiteasy_Onlinepayment_Model_Method_Abstract::REQUEST_TYPE_AUTH) {
            $order->addStatusHistoryComment(Mage::helper('onlinepayment')->__('The action method for this payment was set to authorization, the payment should already be captured'))
                ->save();
            return;
        }
        try {
            $response = $methodInstance->sendCapture($order);

            if (! $response || $response->rc != '0000') {
                $message = Mage::helper('onlinepayment')->__('Problems at capture. Error code: ') . ($response ? $response->rc : 'empty response');
            } else {
                $message = Mage::helper('onlinepayment')->__('DirectPOS Payment Capture completed');
            }
            $order->addStatusHistoryComment($message)
                ->save();
            return true;
        } catch (Exception $e) {
            $message = Mage::helper('onlinepayment')->__('Problems at capture. Error: ') . $e->getMessage();
            $order->addStatusHistoryComment($message)
                ->save();
        }

    }
}

