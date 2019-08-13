<?php

$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn($installer->getTable('bluesnap/logger'), 'request_url', array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable' => false,
        'length' => 255,
        'comment' => 'request url'
    ));

$installer->endSetup();