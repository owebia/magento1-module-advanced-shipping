<?php

//UPDATE `magento__core_config_data` SET `path` = REPLACE(`path`, 'owebia_shipping2/', 'owebia-shipping2/') WHERE `path` LIKE 'owebia_shipping2/%';
//DELETE FROM `magento__core_resource` WHERE code = 'owebia_shipping2_setup';

$installer = $this; /* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();
$installer->run("UPDATE `{$this->getTable('core_config_data')}` SET `path` = REPLACE(`path`, 'owebia-shipping2/bundle-product/', 'owebia_shipping2/bundle_product/') WHERE `path` LIKE 'owebia-shipping2/bundle-product/%';");
$installer->run("UPDATE `{$this->getTable('core_config_data')}` SET `path` = REPLACE(`path`, 'owebia-shipping2/configurable-product/', 'owebia_shipping2/configurable_product/') WHERE `path` LIKE 'owebia-shipping2/configurable-product/%';");
$installer->run("UPDATE `{$this->getTable('core_config_data')}` SET `path` = REPLACE(`path`, 'owebia-shipping2/', 'owebia_shipping2/') WHERE `path` LIKE 'owebia-shipping2/%';");
$installer->endSetup();
