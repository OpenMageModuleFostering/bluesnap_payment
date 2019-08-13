<?php

class Bluesnap_Payment_Block_Adminhtml_Sales_Order_Grid_Renderer_Paymentmethod
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $method = $row->getData('payment_method');
        if ($method == 'cse') {
            return 'BlueSnap Credit/Debit card';
        } else {
            return $method;
        }
    }
}