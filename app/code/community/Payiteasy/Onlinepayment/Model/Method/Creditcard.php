<?php

class Payiteasy_Onlinepayment_Model_Method_Creditcard extends Payiteasy_Onlinepayment_Model_Method_Abstract
{

    const REQUEST_TYPE_CREATE = 'create';

    const METHOD_CODE = 'directpos_cc';

    protected $_code = self::METHOD_CODE;

    protected $_requestKind = 'creditcard';

    /**
     * Availability options
     */
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
    protected $_formBlockType = 'onlinepayment/creditcard_form';

    public function getMethodCode()
    {
        return self::METHOD_CODE;
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('onlinepayment/payment/result');
    }

    public function buildRequestCreatePanalias($data)
    {
        $requestBody = sprintf(
            '<?xml version="1.0" encoding="utf-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soapenv:Body>'
            . '<xmlApiRequest version="1.0" id="x1" xmlns="%s">'
            . '<panAliasRequest id="ref_36b0e18138645f49ad2051ca25c3bf39">'
            . '<merchantId>%s</merchantId>'
            . '<action>%s</action>'
            . '<pan>%s</pan>'
            . '<expiryDate>'
            . '<month>%s</month>'
            . '<year>%s</year>'
            . '</expiryDate>'
            . '</panAliasRequest>'
            . '</xmlApiRequest>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>', $this->getXmlRequestUrl(), $this->getMerchantId(), self::REQUEST_TYPE_CREATE, $data["cc_number"], $data["cc_exp_month"], $data["cc_exp_year"]
        );

        $client = new Zend_Http_Client();
        $uri = ($this->getConfigData('mode') == 1) ? self::API_URL_TEST : self::API_URL;
        $client->setUri($uri);
        $client->setConfig(array('timeout' => 45));
        $client->setHeaders(array('Content-Type: text/xml'));
        ($this->getConfigData('mode') == 1) ? $client->setAuth($this->getUsername(), $this->getPassword()) : $client->setAuth($this->getUsernameLive(), $this->getPasswordLive());
        $client->setMethod(Zend_Http_Client::POST);
        $client->setRawData($requestBody);

        $debugData = array('request' => $requestBody);
        try {
            $responseBody = $client->request()->getBody();
            $debugData['result'] = $responseBody;
            $this->_debug($debugData);
            $panalias = $this->_returnPanaliasFromString($responseBody);
            return $panalias;
        } catch (Exception $e) {
            $message = Mage::helper('onlinepayment')->__('Payment updating error.');
            $message .= ' (' . $e->getMessage() . ')';
            Mage::throwException($message);
        }
    }

    public function buildRequestCapture($order)
    {
        switch (trim(Mage::getStoreConfig('payment/directpos_cc/action', Mage::app()->getStore()))) {
            case 'authorization':
                $actionMode = self::REQUEST_TYPE_AUTH;
                break;
            case 'preauthorization':
                $actionMode = self::REQUEST_TYPE_PRE_AUTH;
                break;
            default:
                $actionMode = self::REQUEST_TYPE_PRE_AUTH;
        }

        $payment = $order->getPayment();

        $amount = $this->_prepareAmount($order->getGrandTotal());

        if ($amount > 0) {
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
                . '<creditCard>'
                . "<panalias generate='false'>%s</panalias>"
                . '</creditCard>'
                . '</paymentRequest>'
                . '</xmlApiRequest>'
                . '</soapenv:Body>'
                . '</soapenv:Envelope>', $this->getXmlRequestUrl(), $this->getMerchantId(), $this->getOrderId($order->getId()),
                $order->getId(), self::FORM_REQUEST_KIND, $actionMode, $amount, $order->getBaseCurrencyCode(), $payment->getCcNumberEnc()
            );

            $client = new Zend_Http_Client();
            $uri = ($this->getConfigData('mode') == 1) ? self::API_URL_TEST : self::API_URL;
            $client->setUri($uri);
            $client->setConfig(array('timeout' => 45));
            $client->setHeaders(array('Content-Type: text/xml'));
            ($this->getConfigData('mode') == 1) ? $client->setAuth($this->getUsername(), $this->getPassword()) : $client->setAuth($this->getUsernameLive(), $this->getPasswordLive());
            $client->setMethod(Zend_Http_Client::POST);
            $client->setRawData($requestBody);

            try {
                $responseBody = $client->request()->getBody();
                return $responseBody;
            } catch (Exception $e) {
                Mage::throwException(Mage::helper('onlinepayment')->__('Payment updating error.'));
            }
        }
    }

    private function _returnPanaliasFromString($body)
    {
        if (!preg_match('/<panalias>([^<]+)<\/panalias>/', $body, $m)) {
            throw new Exception(Mage::helper('onlinepayment')->__('Please try again using a different payment method.'));
        }
        return $m[1];
    }

}

