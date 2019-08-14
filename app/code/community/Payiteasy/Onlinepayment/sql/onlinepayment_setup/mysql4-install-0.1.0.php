<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();
$installer->run("ALTER TABLE `sales_flat_order` ADD `onlinepayment_status_update` TINYINT( 1 ) UNSIGNED NULL DEFAULT NULL ");
$installer->endSetup();