<?php

$installer = Mage::getResourceModel('catalog/setup', 'default_setup');

$installer->startSetup();
/**
 * Create customer's attribute for linking with the BlueSnap Shopper Id
 */
$data = array(
    'label' => 'BlueSnap Account Id',
    'type' => 'varchar',
    'input' => 'text',
    'required' => 0,
    'user_defined' => 0,
    'group' => 'General',
);

$installer->addAttribute('customer', 'bs_account_id', $data);

// Required tables
$statusTable = $installer->getTable('sales/order_status');
$statusStateTable = $installer->getTable('sales/order_status_state');


$statuses = array(
    array('status' => 'declined', 'label' => 'Declined'),
    array('status' => 'refunded', 'label' => 'Refunded'),
    array('status' => 'charged', 'label' => 'Charged'),
    array('status' => 'chargeback', 'label' => 'Chargeback'),
    array('status' => 'specialorder_canceled', 'label' => 'Special Order Canceled')
);

foreach ($statuses as $status) {
    try {

        $installer->getConnection()->insert($statusTable, $status);

    } catch (Exception $e) {
        //echo "1.0.2". $e->getMessage()."\n"; 
    }
}


// Insert statuses

$states = array(
    array(
        'status' => 'declined',
        'state' => 'canceled',
        'is_default' => 0
    ),
    array(
        'status' => 'refunded',
        'state' => 'closed',
        'is_default' => 0
    ),
    array(
        'status' => 'charged',
        'state' => 'processing',
        'is_default' => 0
    ),

    array(
        'status' => 'chargeback',
        'state' => 'closed',
        'is_default' => 0
    ),
    //  array(
    //      'status' => 'specialorder_canceled',
    //      'state' => 'specialorder_state',
    //      'is_default' => 0
    //  )
);

foreach ($states as $state) {
    try {


        // Insert states and mapping of statuses to states
        $installer->getConnection()->insertArray($statusStateTable, $state);

    } catch (Exception $e) {
        //echo "1.0.2". $e->getMessage()."\n"; 
    }
}


//add historical currency rate to sales_order table
$connection = $this->getConnection();


try {
    /**
     * Create the payment method dropdown field, because this field _may_ be
     * used for searching we will create an index for it.
     */
    $connection->addColumn(
        $this->getTable('sales/order'),
        'bluesnap_currency_rate',
        "DECIMAL(12,4) DEFAULT 0 COMMENT 'Historical Currence Rates from Bluesnap returns'"
    );

    $connection->addColumn(
        $this->getTable('sales/order'),
        'bluesnap_currency_code',
        "CHAR(3) DEFAULT NULL COMMENT 'Currency used by Bluesnap'"
    );

    $connection->addColumn(
        $this->getTable('sales/order'),
        'bluesnap_grand_total',
        "DECIMAL(12,4) DEFAULT NULL COMMENT 'Bluesnap Grand Total'"
    );

    $connection->addColumn(
        $this->getTable('sales/order'),
        'bluesnap_total_invoiced',
        "DECIMAL(12,4) DEFAULT NULL COMMENT 'Bluesnap Total Invoiced'"
    );

    $connection->addColumn(
        $this->getTable('sales/order'),
        'bluesnap_total_paid',
        "DECIMAL(12,4) DEFAULT NULL COMMENT 'Bluesnap Total Paid'"
    );

    $connection->addColumn(
        $this->getTable('sales/order'),
        'bluesnap_total_canceled',
        "DECIMAL(12,4) DEFAULT NULL COMMENT 'Bluesnap Total Canceled'"
    );

    $connection->addColumn(
        $this->getTable('sales/order'),
        'bluesnap_total_refunded',
        "DECIMAL(12,4) DEFAULT NULL COMMENT 'Bluesnap Total Refunded'"
    );

    //invoice

    $connection->addColumn(
        $this->getTable('sales/invoice'),
        'bluesnap_currency_code',
        "CHAR(3) DEFAULT NULL COMMENT 'Currency used by Bluesnap'"
    );
    $connection->addColumn(
        $this->getTable('sales/invoice'),
        'bluesnap_grand_total',
        "DECIMAL(12,4) DEFAULT NULL COMMENT 'Bluesnap Grand Total'"
    );


    //     $connection->addColumn(
    //       $this->getTable('sales/invoice'),
    //       'billing_name',
    //       "VARCHAR(45) DEFAULT NULL COMMENT 'Billing Name'"
    //   );


    //credit memo
    $connection->addColumn(
        $this->getTable('sales/creditmemo'),
        'bluesnap_currency_code',
        "CHAR(3) DEFAULT NULL COMMENT 'Currency used by Bluesnap'"
    );

    $connection->addColumn(
        $this->getTable('sales/creditmemo'),
        'bluesnap_grand_total',
        "DECIMAL(12,4) DEFAULT NULL COMMENT 'Bluesnap Grand Total'"
    );

    //  $connection->addColumn(
    //     $this->getTable('sales/creditmemo'),
    //     'billing_name',
    //     "VARCHAR(45) DEFAULT NULL COMMENT 'Billing Name'"
    // );


    $connection->addColumn(
        $this->getTable('sales/order_payment'),
        'bluesnap_total_refunded',
        "DECIMAL(12,4) DEFAULT NULL COMMENT 'Bluesnap Total Refunded'"
    );


    //BSNPMG-91
    $connection->addColumn(
        $this->getTable('sales/order'),
        'bluesnap_reference_number',
        "VARCHAR(45) DEFAULT NULL COMMENT 'Bluesnap Reference Number'"
    );


    //add historical currency rates to quote table

    $connection->addColumn(
        $this->getTable('sales/quote'),
        'bluesnap_currency_rate',
        "DECIMAL(12,4) DEFAULT 0 COMMENT 'Historical Currence Rates from Bluesnap returns'"
    );

    $connection->addColumn(
        $this->getTable('sales/quote'),
        'bluesnap_currency_code',
        "CHAR(3) DEFAULT NULL COMMENT 'Currency used by Bluesnap'"
    );

    $connection->addColumn(
        $this->getTable('sales/quote'),
        'bluesnap_grand_total',
        "DECIMAL(12,4) DEFAULT NULL COMMENT 'Bluesnap Grand Total'"
    );

    $connection->addColumn(
        $this->getTable('directory/currency_rate'),
        'bluesnap_supported',
        "BOOLEAN COMMENT 'Bluesnap supported currency'"
    );

    $connection->addColumn(
        $this->getTable('directory/currency_rate'),
        'date_updated',
        "DATETIME COMMENT 'date updated currency'"
    );


    $connection->addColumn(
        $this->getTable('directory/currency_rate'),
        'result',
        "VARCHAR(45) COMMENT 'RESULT'"
    );
} catch (Exception $e) {
    // echo $e->getMessage();
}


//$connection->addKey($this->getTable('sales/order_grid'), 'x_payment_type', 'x_payment_type');


$installer->endSetup();