<?php
/**
 * Created by JetBrains PhpStorm.
 * User: radu
 * Date: 12.06.2013
 * Time: 17:06
 * To change this template use File | Settings | File Templates.
 */

class Payiteasy_Onlinepayment_Model_Method_Abstract extends Mage_Payment_Model_Method_Abstract
{
    /**
     * number of hours that can pass before a payment made via 'AUTHORIZED' method can be REVERSED
     */
    const AUTHORIZED_REVERSAL_LIMIT_HOURS = '24';

    /**
     * Onlinepayment gateway URL
     */
    const API_URL = 'https://merch.directpos.de/soapapi/services/XmlApiNl';

    /**
     * Onlinepayment test gateway URL
     */
    const API_URL_TEST = 'https://testmerch.directpos.de/soapapi/services/XmlApiNl';

    /*
     *  Onlinepayment Xml Request URL
     */
    const DP_URL_TD = 'http://www.voeb-zvd.de/xmlapi/1.0';

    const REQUEST_TYPE_AUTH = 'authorization';
    const REQUEST_TYPE_PRE_AUTH = 'preauthorization';
    /**
     * the "REFUND" functionality seems to be a 'reversal' action on directPOS admin panel
     */
    const REQUEST_TYPE_REFUND = 'reversal';

    const RESPONSE_CODE_APPROVED = '0000';

    const FORM_REQUEST_KIND = 'creditcard';
    const CSS_URL = 'custom.css';

    protected $_code;
    protected $_requestKind;

    /**
     * @param $methodCode
     * @return Mage_Core_Model_Abstract
     */
    public static function factory($methodCode)
    {
        $modelKey = '';
        switch ($methodCode) {
            case Payiteasy_Onlinepayment_Model_Method_Standard::METHOD_CODE:
                $modelKey = 'onlinepayment/method_standard';
                break;
            case Payiteasy_Onlinepayment_Model_Method_Creditcard::METHOD_CODE:
                $modelKey = 'onlinepayment/method_creditcard';
                break;
            case Payiteasy_Onlinepayment_Model_Method_Debit::METHOD_CODE:
                $modelKey = 'onlinepayment/method_debit';
                break;
        }
        if ($modelKey) {
            return Mage::getModel($modelKey);
        }

        return null;
    }


    /**
     * issue a refund for a order
     *
     * @see Payiteasy_Onlinepayment_Model_Observer::refund
     *
     * @param $order
     * @return string
     */
    public function sendReversal($order)
    {
        try {
            $soap = $this->_getSoapClient();
            $request = new Payiteasy_Onlinepayment_Model_ApiRequest_Refund($order, $this);
            $response = $soap->process($request);
            return $response->paymentResponse;

        } catch (Exception $e) {
            Mage::throwException(Mage::helper('onlinepayment')->__('Refund updating error.'));
        }

        return;
    }

    /**
     * send a capture-payment request
     * @param Mage_Sales_Model_Order $order
     */
    public function sendCapture(Mage_Sales_Model_Order $order)
    {
        try {
            $soap = $this->_getSoapClient();
            $request = new Payiteasy_Onlinepayment_Model_ApiRequest_Capture($order, $this);
            $response = $soap->process($request);
            return $response->paymentResponse;
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('onlinepayment')->__('Error at payment capture:') . $e->getMessage());
        }

    }

    /**
     * verify the previously made payment. if something is wrong, set order status to canceled
     *and redirect customer to failed url
     * @param $lastOrderId
     * @return string
     */
    public function buildResponse($lastOrderId)
    {
        $requestBody = sprintf(
            '<?xml version="1.0" encoding="utf-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soapenv:Body>'
            . '<xmlApiRequest version="1.0" id="x1" xmlns="%s">'
            . '<txDiagnosisRequest id="ref_36b0e18138645f49ad2051ca25c3bf39">'
            . '<merchantId>%s</merchantId>'
            . '<eventExtId>%s</eventExtId>'
            . '<kind>%s</kind>'
            . '<txReferenceExtId>%s_01</txReferenceExtId>'
            . '</txDiagnosisRequest>'
            . '</xmlApiRequest>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>', $this->getXmlRequestUrl(), $this->getMerchantId(), $this->getOrderId($lastOrderId), $this->getRequestKind(), $this->getOrderId($lastOrderId)
        );
        try {
            $client = $this->_getHttpClient();
            $client->setRawData($requestBody);
            $responseBody = $client->request()->getBody();
            return $responseBody;
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('onlinepayment')->__('Payment updating error.'));
        }
    }

    /**
     *
     * instantiate soap client
     * @return Zend_Soap_Client
     */
    protected function _getSoapClient()
    {
        $username = ($this->getConfigData('mode') == 1) ? $this->getUsername() : $this->getUsernameLive();
        $password = ($this->getConfigData('mode') == 1) ? $this->getPassword() : $this->getPasswordLive();

        $client = new Zend_Soap_Client($this->getApiWsdl(), array(
            'login' => $username,
            'password' => $password
        ));
        return $client;
    }

    /**
     * sometimes SOAP will not work .......
     */
    protected function _getHttpClient()
    {
        $client = new Zend_Http_Client();
        $uri = ($this->getConfigData('mode') == 1) ? self::API_URL_TEST : self::API_URL;
        $client->setUri($uri);
        $client->setConfig(array('timeout' => 45));
        $client->setHeaders(array('Content-Type: text/xml'));
        ($this->getConfigData('mode') == 1) ? $client->setAuth($this->getUsername(), $this->getPassword()) : $client->setAuth($this->getUsernameLive(), $this->getPasswordLive());
        $client->setMethod(Zend_Http_Client::POST);

        return $client;
    }

    public function getApiWsdl()
    {
        return ($this->getConfigData('mode') == 1 ? self::API_URL_TEST : self::API_URL) . '?wsdl';
    }

    /**
     * @param $orderId
     * @return string
     */
    public function getOrderId($orderId)
    {
        return trim(Mage::getStoreConfig('onlinepayment/onlinepayment_group/onlinepayment_merchant_id', Mage::app()->getStore())) . "_RB" . $orderId;
    }

    /**
     * @return string
     */
    public function getXmlRequestUrl()
    {
        return self::DP_URL_TD;
    }

    /**
     * methods for accessing configuration values
     */
    /**
     * @return string
     */
    public function getMerchantId()
    {
        return trim(Mage::getStoreConfig('onlinepayment/onlinepayment_group/onlinepayment_merchant_id', Mage::app()->getStore()));
    }

    /**
     * @return string
     */
    public function getUsernameLive()
    {
        return trim(Mage::getStoreConfig('onlinepayment/onlinepayment_group/onlinepayment_username_live', Mage::app()->getStore()));
    }

    /**
     * @return string
     */
    public function getPasswordLive()
    {
        return trim(Mage::getStoreConfig('onlinepayment/onlinepayment_group/onlinepayment_password_live', Mage::app()->getStore()));
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return trim(Mage::getStoreConfig('onlinepayment/onlinepayment_group/onlinepayment_username', Mage::app()->getStore()));
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return trim(Mage::getStoreConfig('onlinepayment/onlinepayment_group/onlinepayment_password', Mage::app()->getStore()));
    }

    /**
     * @return string
     */
    public function getCssUrl()
    {
        return trim(Mage::getStoreConfig('onlinepayment/onlinepayment_group/onlinepayment_css_url', Mage::app()->getStore()));
    }

    /**
     * get curernt actionm mode
     * @return string
     */
    public function getActionMode()
    {
        return trim($this->getConfigData('action'));
    }

    /**
     * @return string
     */
    public function getRequestKind()
    {
        return $this->_requestKind;
    }

    protected function _prepareAmount($amount)
    {
        return number_format($amount, 2) * 100;
    }

    /**
     *
     * @param Mage_Sales_Model_Order $order
     */
    public function createInvoice($order)
    {
        try {
            if ($order->canInvoice()) {
                $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->sendEmail();
                $order->addStatusHistoryComment("Invoice generation finished");
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $transactionSave->save();
            }
        } catch (Mage_Core_Exception $e) {

        }
    }
}