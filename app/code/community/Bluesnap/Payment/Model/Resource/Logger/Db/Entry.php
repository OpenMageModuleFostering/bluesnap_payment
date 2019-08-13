<?php

class Bluesnap_Payment_Model_Resource_Logger_Db_Entry extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Define main table
     *
     */
    protected function _construct()
    {
        $this->_init('bluesnap/logger', 'event_id');
    }

}
    

