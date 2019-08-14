<?php
/**
 * Created by JetBrains PhpStorm.
 * User: radu
 * Date: 13.06.2013
 * Time: 11:41
 * To change this template use File | Settings | File Templates.
 */

class Payiteasy_Onlinepayment_Model_ApiRequest_Refund extends Payiteasy_Onlinepayment_Model_ApiRequest_Base
{
    const ACTION = 'reversal';

    public function __construct(Mage_Sales_Model_Order $order, Payiteasy_Onlinepayment_Model_Method_Abstract $model)
    {
        $this->paymentRequest = new Payiteasy_Onlinepayment_Model_ApiRequest_Type_Payment($order, $model);

        $this->paymentRequest->action = self::ACTION;
        $this->paymentRequest->txReferenceExtId = $model->getOrderId($order->getId()) . '_01';
    }
}