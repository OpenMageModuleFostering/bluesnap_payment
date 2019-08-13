<?php

/**
 * order return coupons for POS
 */
class Bluesnap_Payment_Block_Adminhtml_Api_Logger extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        parent::__construct();

        $this->_blockGroup = 'bluesnap';

        $this->_controller = 'adminhtml_api_logger';

        /**
         * The title of the page in the admin panel.
         */
        $this->_headerText = Mage::helper('adminhtml')
            ->__('Api Log');

        $this->_removeButton('add');

    }


}

