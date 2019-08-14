<?php

class Payiteasy_Onlinepayment_Model_ApiRequest_Capture extends Payiteasy_Onlinepayment_Model_ApiRequest_Base
{
    const ACTION = 'capture';

    public function __construct(Mage_Sales_Model_Order $order, Payiteasy_Onlinepayment_Model_Method_Abstract $model)
    {
        $this->paymentRequest = new Payiteasy_Onlinepayment_Model_ApiRequest_Type_Payment($order, $model);

        $this->paymentRequest->action = self::ACTION;
        $this->paymentRequest->txReferenceExtId = $model->getOrderId($order->getId()) . '_01';
    }
}