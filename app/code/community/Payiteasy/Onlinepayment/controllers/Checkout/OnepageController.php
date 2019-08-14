<?php

/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License (GPL 3)
 * that is bundled with this package in the file LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Payone_Core to newer
 * versions in the future. If you wish to customize Payone_Core for your
 * needs please refer to http://www.payone.de for more information.
 *
 */
require_once 'Mage/Checkout/controllers/OnepageController.php';

class Payiteasy_Onlinepayment_Checkout_OnepageController extends Mage_Checkout_OnepageController
{
    /**
     * Save payment ajax action
     *
     * Sets either redirect or a JSON response
     */
    public function savePaymentAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        try {
            if (!$this->getRequest()->isPost()) {
                $this->_ajaxRedirectResponse();
                return;
            }

            // set payment to quote
            $result = array();
            $data = $this->getRequest()->getPost('payment', array());

            $paymentMethod = Payiteasy_Onlinepayment_Model_Method_Abstract::factory($data['method']);
            if (null !== $paymentMethod) {
                $data['onlinepayment_action_mode'] = $paymentMethod->getActionMode();
            }

            if ($data['method'] == Payiteasy_Onlinepayment_Model_Method_Creditcard::METHOD_CODE) {
                $data['cc_number_enc'] = Mage::getModel('onlinepayment/method_creditcard')->buildRequestCreatePanalias($data);
            } elseif ($data['method'] == Payiteasy_Onlinepayment_Model_Method_Debit::METHOD_CODE) {
                Mage::getSingleton('checkout/session')->paymentData = $data;
            }
            $result = $this->getOnepage()->savePayment($data);

            // get section and redirect data
            $redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
            if (empty($result['error']) && !$redirectUrl) {
                $this->loadLayout('checkout_onepage_review');
                $result['goto_section'] = 'review';
                $result['update_section'] = array(
                    'name' => 'review',
                    'html' => $this->_getReviewHtml()
                );
            }
            if ($redirectUrl) {
                $result['redirect'] = $redirectUrl;
            }
        } catch (Mage_Payment_Exception $e) {
            if ($e->getFields()) {
                $result['fields'] = $e->getFields();
            }
            $result['error'] = $e->getMessage();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $result['error'] = $this->__('Unable to set Payment Method.');
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

}