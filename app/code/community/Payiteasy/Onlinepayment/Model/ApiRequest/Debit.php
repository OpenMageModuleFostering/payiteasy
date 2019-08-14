<?php
/**
 * Created by JetBrains PhpStorm.
 * User: radu
 * Date: 13.06.2013
 * Time: 14:44
 * To change this template use File | Settings | File Templates.
 */

class Payiteasy_Onlinepayment_Model_ApiRequest_Debit extends Payiteasy_Onlinepayment_Model_ApiRequest_Base
{
    public function __construct(Mage_Sales_Model_Order $order, Payiteasy_Onlinepayment_Model_Method_Abstract $model)
    {
        /**
         * this does not currently work, there are some issues with the request xml schema validation
         *
         * TODO : should we remove these classes altogether ?
         */
        $this->paymentRequest = new Payiteasy_Onlinepayment_Model_ApiRequest_Type_Payment($order, $model);

        $this->paymentRequest->action = $model->getActionMode();
        $this->paymentRequest->amount = (string) ($this->_prepareAmount($order->getGrandTotal()));
        $this->paymentRequest->currency = $order->getBaseCurrencyCode();

        $this->paymentRequest->txReferenceExtId = $model->getOrderId($order->getId()) . '_01';

        $this->paymentRequest->bankAccount = new ArrayObject();
    }

    public function setBankData($data)
    {
        $this->paymentRequest->bankAccount->bankCode = $data['debit_bank_code'];
        $this->paymentRequest->bankAccount->accountNumber = $data['debit_account_number'];
        $this->paymentRequest->bankAccount->holder = $data['debit_holder'];
    }


}