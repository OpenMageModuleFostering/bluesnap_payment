<?php

/**
 * Class Bluesnap_Payment_Block_Buynow_Info_Order
 */
class Bluesnap_Payment_Block_Buynow_Info_Order extends Mage_Core_Block_Template
{
    /**
     * Init block
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bluesnap/payment/buynow/checkout/info/order.phtml');
    }

    /**
     * Get current order object
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }
}