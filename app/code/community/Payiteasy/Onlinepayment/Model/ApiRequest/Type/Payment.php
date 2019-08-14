<?php
/**
 * Created by JetBrains PhpStorm.
 * User: radu
 * Date: 13.06.2013
 * Time: 11:48
 * To change this template use File | Settings | File Templates.
 */

class Payiteasy_Onlinepayment_Model_ApiRequest_Type_Payment extends ArrayObject
{
    public $id = 'ref_36b0e18138645f49ad2051ca25c3bf39';
    public $xid = '1234567890123456789012345678';
    public $kind = 'creditcard';

    public function __construct(Mage_Sales_Model_Order $order, Payiteasy_Onlinepayment_Model_Method_Abstract $model)
    {
        $this->merchantId = $model->getMerchantId();
        $this->eventExtId = $model->getOrderId($order->getId());

        $this->kind = $model->getRequestKind();

    }
}