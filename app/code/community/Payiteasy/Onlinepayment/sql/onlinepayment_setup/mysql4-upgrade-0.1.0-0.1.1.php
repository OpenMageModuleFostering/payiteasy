<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();
$paymentTable = $this->getTable('sales_flat_order_payment');
$quotePaymentTable = $this->getTable('sales_flat_quote_payment');
$configTable = $this->getTable('core_config_data');

/** used in reversal of payments, we need to hold the action mode of the payment (authorization or pre-authorization) */
$installer->run("ALTER TABLE `{$paymentTable}` ADD `onlinepayment_action_mode` VARCHAR( 32 ) NULL DEFAULT NULL");
$installer->run("ALTER TABLE `{$quotePaymentTable}` ADD `onlinepayment_action_mode` VARCHAR( 32 ) NULL DEFAULT NULL");

$installer->run("DELETE FROM {$configTable} WHERE path = 'payment/directpos_cc/order_status'");
$installer->run("DELETE FROM {$configTable} WHERE path = 'payment/directpos_elv/order_status'");
$installer->run("DELETE FROM {$configTable} WHERE path = 'payment/directpos_std/order_status'");

$installer->endSetup();