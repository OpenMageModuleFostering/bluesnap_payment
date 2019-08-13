<?php

class Bluesnap_Payment_Block_Customer_Widget_Name extends Mage_Customer_Block_Widget_Name
{
    public function _construct()
    {
        parent::_construct();

        // default template location
        $this->setTemplate('bluesnap/customer/widget/name.phtml');
    }
}

