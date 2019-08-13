<?php

class Bluesnap_Payment_Block_Adminhtml_Sales_Order_Invoice_Totals extends Mage_Adminhtml_Block_Sales_Order_Invoice_Totals
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

        return $this;
    }
}

