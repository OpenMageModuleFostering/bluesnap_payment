<?php

/* @var $installer Mage_Customer_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->run(
    "DROP TABLE IF EXISTS ".$installer->getTable('bluesnap/logger').";
CREATE TABLE  ".$installer->getTable('bluesnap/logger')." (
  `event_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request` text,
  `response` text,
  `method` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `priority` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `return_code` int(10) unsigned DEFAULT NULL,
  `increment_id` varchar(15) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text,
  PRIMARY KEY (`event_id`),
  KEY `increment_id` (`increment_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;"

);


$installer->endSetup();