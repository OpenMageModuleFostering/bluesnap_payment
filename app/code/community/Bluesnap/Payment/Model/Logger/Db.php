<?php

/**
 * detailed import log
 */
class Bluesnap_Payment_Model_Logger_Db extends Zend_Log_Writer_Db
{


    public function __construct($db = null, $table = null, $columnMap = null)
    {

        //parent::__construct(null);
        $resource = Mage::getSingleton('core/resource');
        $this->_db = $resource->getConnection('core_write');

        $this->_table = 'bluesnap_api_logger';

        $this->_columnMap = array(
            'priority' => "priority",
            'message' => "message",
            //   'order_id'=>'order_id',
            'increment_id' => 'increment_id',
            'request' => "request",
            'response' => "response",
            'method' => "method",
            'return_code' => 'return_code',
            'ip' => 'ip',
            'user_agent' => 'user_agent',
            'created_at' => 'created_at',
            'request_url' => 'request_url',
        );

        //write all
        //priority filter
        $this->addFilter(new Zend_Log_Filter_Priority((int)$this->getConfig()->getConfigData('logger/db_priority')));


    }

    /**
     * @return Bluesnap_Payment_Model_Api_Config
     *
     */
    function getConfig()
    {
        return Mage::getSingleton('bluesnap/api_config');
    }

    function resetFilters()
    {
        $this->_filters = array();
    }

    function _write($event)
    {
        parent::_write($event);
    }
}

