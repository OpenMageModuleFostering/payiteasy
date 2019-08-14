<?php

class Payiteasy_Onlinepayment_Model_Method_Debit extends Payiteasy_Onlinepayment_Model_Method_Abstract
{
    /**
     *
     */
    const METHOD_CODE = 'directpos_elv';

    protected $_code = self::METHOD_CODE;

    protected $_requestKind = 'debit';

    protected $_isGateway = false;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_canSaveCc = false;
    protected $_formBlockType = 'onlinepayment/debit_form';

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('onlinepayment/payment/result');
    }

    public function buildRequestCapture($order)
    {
        $client = $this->_getHttpClient();
        $data = Mage::getSingleton('checkout/session')->paymentData;

        $requestBody = sprintf(
            '<?xml version="1.0" encoding="utf-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soapenv:Body>'
            . '<xmlApiRequest version="1.0" id="x1" xmlns="%s">'
            . '<paymentRequest id="ref_36b0e18138645f49ad2051ca25c3bf39">'
            . '<merchantId>%s</merchantId>'
            . '<eventExtId>%s</eventExtId>'
            . '<basketId>%s</basketId>'
            . '<kind>%s</kind>'
            . '<action>%s</action>'
            . '<amount>%s</amount>'
            . '<currency>%s</currency>'
            . "<bankAccount>"
            . '<bankCode>%s</bankCode>'
            . '<accountNumber>%s</accountNumber>'
            . '<holder>%s</holder>'
            . '</bankAccount>'
            . '</paymentRequest>'
            . '</xmlApiRequest>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>',
            $this->getXmlRequestUrl(), $this->getMerchantId(),
            $this->getOrderId($order->getId()), $order->getId(), $this->getRequestKind(),
            $this->getActionMode(), $this->_prepareAmount($order->getGrandTotal()), $order->getBaseCurrencyCode(),
            $data['debit_bank_code'], $data['debit_account_number'], $data['debit_holder']
        );
        $client->setRawData($requestBody);

        try {
            $responseBody = $client->request()->getBody();
            unset(Mage::getSingleton('checkout/session')->paymentData);
            return $responseBody;
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('onlinepayment')->__('Error sending payment data'));
        }
    }

}