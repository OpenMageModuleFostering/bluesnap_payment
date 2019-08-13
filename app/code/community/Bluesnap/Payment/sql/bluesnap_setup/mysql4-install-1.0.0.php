<?php

$installer = Mage::getResourceModel('catalog/setup', 'default_setup');

$installer->startSetup();

//stub for installer. Actual installation is done in update 1.0.0-1.0.2
$installer->endSetup();