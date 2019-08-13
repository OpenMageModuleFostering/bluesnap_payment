<?php

class Bluesnap_Payment_Model_Logger_File extends Zend_Log_Writer_Abstract
{

    protected $_logFilename = 'bluesnap_payment.log';

    public function __construct($db = null, $table = null, $columnMap = null)
    {
        $this->addFilter(new Zend_Log_Filter_Priority((int)$this->getConfig()->getConfigData('logger/file_priority')));
    }

    /**
     * @return Bluesnap_Payment_Model_Api_Config
     *
     */
    function getConfig()
    {
        return Mage::getSingleton('bluesnap/api_config');
    }

    public static function factory($config)
    {

    }

    function resetFilters()
    {
        $this->_filters = array();
    }

    function _write($event)
    {
        Mage::log("\nincrement id:\t{$event['increment_id']}"
            . "\nmethod:{$event['method']}"
            . "\nmessage:\t{$event['message']}"
            . "\n return_code:\t{$event['return_code']}"
            . "\nrequest:\t{$event['request']}"
            . "\nresponse:\t{$event['response']}\n\n",

            $event['priority'], $this->_logFilename, true);

    }

}

