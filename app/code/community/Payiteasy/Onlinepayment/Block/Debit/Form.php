<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Authorizenet
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Onlinepayment form block
 *
 * @category   Mage
 * @package    Mage_Authorizenet
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Payiteasy_Onlinepayment_Block_Debit_Form extends Mage_Payment_Block_Form
{

    /**
     * Internal constructor
     * Set info template for payment step
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('onlinepayment/debit_form.phtml');
    }

}
