<?php

class Payiteasy_Onlinepayment_Model_Method_Standard extends Payiteasy_Onlinepayment_Model_Method_Abstract
{

    const METHOD_CODE = 'directpos_std';

    protected $_code = self::METHOD_CODE;

    protected $_requestKind = 'creditcard';

    /**
     * Availability options
     */
    protected $_isGateway = false;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_canSaveCc = false;
    protected $_canCreditmemo = false;

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('onlinepayment/payment/redirect', array('_secure' => true));
    }

    public function getRedirectAfterUrl()
    {
        return Mage::getUrl('onlinepayment/payment/response', array('_secure' => true));
    }

    public function getMethodCode()
    {
        return self::METHOD_CODE;
    }

    public function getNotificationUrl()
    {
        return Mage::getUrl('onlinepayment/payment/response');
    }

    public function buildRequest(Varien_Object $order)
    {
        switch (trim(Mage::getStoreConfig('payment/directpos_std/action', Mage::app()->getStore()))) {
            case 'authorization':
                $actionMode = self::REQUEST_TYPE_AUTH;
                break;
            case 'preauthorization':
                $actionMode = self::REQUEST_TYPE_PRE_AUTH;
                break;
            default:
                $actionMode = self::REQUEST_TYPE_PRE_AUTH;
        }

        $amount = $order->getGrandTotal();
        if ($amount > 0) {
            $amount = $this->_prepareAmount($amount);
            $requestBody = sprintf(
                '<?xml version="1.0" encoding="utf-8"?>'
                . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
                . '<soapenv:Body>'
                . '<xmlApiRequest version="1.0" id="x1" xmlns="%s">'
                . '<formServiceRequest id="ref_36b0e18138645f49ad2051ca25c3bf39">'
                . '<merchantId>%s</merchantId>'
                . '<eventExtId>%s</eventExtId>'
                . '<basketId>%s</basketId>'
                . '<kind>%s</kind>'
                . '<action>%s</action>'
                . '<amount>%s</amount>'
                . '<currency>%s</currency>'
                . '<formData><cssURL>%s</cssURL></formData>'
                . '<callbackData><notifyURL>%s</notifyURL></callbackData>'
                . '<customerContinuation><successURL>%s</successURL></customerContinuation>'
                . '</formServiceRequest>'
                . '</xmlApiRequest>'
                . '</soapenv:Body>'
                . '</soapenv:Envelope>', $this->getXmlRequestUrl(), $this->getMerchantId(), $this->getOrderId($order->getId()),
                $order->getId(), self::FORM_REQUEST_KIND, $actionMode, $amount, $order->getBaseCurrencyCode(), $this->getCssUrl(),
                $this->getNotificationUrl(), $this->getRedirectAfterUrl()
            );

            if ($order->getEmailSent() != 1) {
                //$order->sendNewOrderEmail();
            }

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
                $this->_processData($responseBody, $order);
            } catch (Exception $e) {
                Mage::throwException(Mage::helper('onlinepayment')->__('Payment updating error.'));
            }
        }
        return true;
    }

    protected function _processData($xmlResponse, $order, $type = 'redirect')
    {
        if ($type == 'redirect') {
            if (preg_match('/<formServiceURL>([^<]+)<\/formServiceURL>/', $xmlResponse, $m)) {
                $this->_adminComments($order, Mage::helper('onlinepayment')->__('Client redirected to OnlinePayment Gateway'));
                header("Location: {$m[1]}");
                exit();
            } else {
                $this->_adminComments($order, Mage::helper('onlinepayment')->__('Error occured while redirecting to Gateway.'));
                header("Location:" . Mage::getUrl());
                exit();
            }
        }

        if ($type == 'response') {
            preg_match('/<rc>([^<]+)<\/rc>/', $xmlResponse, $m);
            Mage::log(var_export($m, true));
            return $m[1];
        }
    }

    public function _adminComments($order, $message = '')
    {
        $order->addStatusHistoryComment($message);
        $order->save();
        return true;
    }

    /**
     * read response from directPOS
     * if everything is ok, set order status etc etc
     * @param string $xml xml string containing response
     *
     * @return bool the result of the operation, true for successful payment, false otherwise
     */
    public function processResponse($xml)
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $orderId = $dom->getElementsByTagName('basketId')->item(0)->nodeValue;
        $rc = $dom->getElementsByTagName('rc')->item(0)->nodeValue;
        $message = $dom->getElementsByTagName('message')->item(0)->nodeValue;

        $order = Mage::getModel('sales/order')->load($orderId);

        if ($rc != self::RESPONSE_CODE_APPROVED) {
            //cancel order
            $order->cancel();
            $order->setStatus(Mage_Sales_Model_Order::STATE_CANCELED, true);
            $message = Mage::helper('onlinepayment')->__('FormularService payment - Some transaction problems caused this order to change it\'s status to cancelled. (%s)', $message);
            $order->addStatusHistoryComment($message);
            $order->save();
            return false;
        }

        /* everything looks fine, create invoice (if needed) */
        $order->setOnlinepaymentStatusUpdate(1); //save payment status flag, so we can redirect the user to success page
        if ($order->getEmailSent() != 1) {
            $order->sendNewOrderEmail();
        }

        //redirect to success
        $message = Mage::helper('onlinepayment')->__('Order paid successfully via Onlinepayment FormularService method.');
        $order->addStatusHistoryComment($message);
        $order->save();

        return true;
    }
}