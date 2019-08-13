<?php

class Bluesnap_Payment_Block_Adminhtml_Sales_Order_Totals extends Mage_Adminhtml_Block_Sales_Totals//Mage_Adminhtml_Block_Sales_Order_Abstract
{
    function formatValue($total)
    {
        if (!$total->getIsFormated()) {
            return $this->helper('adminhtml/sales')->displayPricesBluesnap(
                $this->getOrder(),
                $total->getBaseValue(),
                $total->getValue(),
                $total->getBluesnapValue()
            );
        }
        return $total->getValue();

    }

    /**
     * Initialize order totals array
     *
     * @return Mage_Sales_Block_Order_Totals
     */
    protected function _initTotals()
    {
        $this->_totals = array();
        $this->_totals['subtotal'] = new Varien_Object(array(
            'code' => 'subtotal',
            'value' => $this->getSource()->getSubtotal(),
            'base_value' => $this->getSource()->getBaseSubtotal(),
            'bluesnap_value' => $this->getSource()->getBluesnapSubtotal(),
            'label' => $this->helper('sales')->__('Subtotal')
        ));

        /**
         * Add shipping
         */
        if (!$this->getSource()->getIsVirtual() && ((float)$this->getSource()->getShippingAmount() || $this->getSource()->getShippingDescription())) {
            $this->_totals['shipping'] = new Varien_Object(array(
                'code' => 'shipping',
                'value' => $this->getSource()->getShippingAmount(),
                'base_value' => $this->getSource()->getBaseShippingAmount(),
                'bluesnap_value' => $this->getSource()->getBluesnapShippingAmount(),
                'label' => $this->helper('sales')->__('Shipping & Handling')
            ));
        }

        /**
         * Add discount
         */
        if (((float)$this->getSource()->getDiscountAmount()) != 0) {
            if ($this->getSource()->getDiscountDescription()) {
                $discountLabel = $this->helper('sales')->__('Discount (%s)', $this->getSource()->getDiscountDescription());
            } else {
                $discountLabel = $this->helper('sales')->__('Discount');
            }
            $this->_totals['discount'] = new Varien_Object(array(
                'code' => 'discount',
                'value' => $this->getSource()->getDiscountAmount(),
                'base_value' => $this->getSource()->getBaseDiscountAmount(),
                'bluesnap_value' => $this->getSource()->getBluesnapDiscountAmount(),
                'label' => $discountLabel
            ));
        }

        $this->_totals['grand_total'] = new Varien_Object(array(
            'code' => 'grand_total',
            'strong' => true,
            'value' => $this->getSource()->getGrandTotal(),
            'base_value' => $this->getSource()->getBaseGrandTotal(),
            'bluesnap_value' => $this->getSource()->getBluesnapGrandTotal(),
            'label' => $this->helper('sales')->__('Grand Total'),
            'area' => 'footer'
        ));


        //end parent totals

        $this->_totals['paid'] = new Varien_Object(array(
            'code' => 'paid',
            'strong' => true,
            'value' => $this->getSource()->getTotalPaid(),
            'base_value' => $this->getSource()->getBaseTotalPaid(),
            'bluesnap_value' => $this->getSource()->getBluesnapTotalPaid(),
            'label' => $this->helper('sales')->__('Total Paid'),
            'area' => 'footer'
        ));
        $this->_totals['refunded'] = new Varien_Object(array(
            'code' => 'refunded',
            'strong' => true,
            'value' => $this->getSource()->getTotalRefunded(),
            'base_value' => $this->getSource()->getBaseTotalRefunded(),
            'bluesnap_value' => $this->getSource()->getBluesnapTotalRefunded(),
            'label' => $this->helper('sales')->__('Total Refunded'),
            'area' => 'footer'
        ));
        $this->_totals['due'] = new Varien_Object(array(
            'code' => 'due',
            'strong' => true,
            'value' => $this->getSource()->getTotalDue(),
            'base_value' => $this->getSource()->getBaseTotalDue(),
            'bluesnap_value' => $this->getSource()->getBluesnapTotalDue(),

            'label' => $this->helper('sales')->__('Total Due'),
            'area' => 'footer'
        ));
        return $this;
    }
}
