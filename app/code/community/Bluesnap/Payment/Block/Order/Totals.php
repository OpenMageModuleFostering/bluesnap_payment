<?php

/**
 * Prepare order totals for payment page summary
 */
class Bluesnap_Payment_Block_Order_Totals extends Mage_Sales_Block_Order_Totals
{

    /**
     * @return Bluesnap_Payment_Model_Directory_Currency
     *
     */
    function _currencyModel()
    {
        return Mage::getSingleton('Bluesnap_Payment_Model_Directory_Currency');
    }


    /**
     * Initialize order totals array
     *
     * @return Mage_Sales_Block_Order_Totals
     */
    protected function _initTotals()
    {
        $source = $this->getSource();

        $this->_totals = array();
        $this->_totals['subtotal'] = new Varien_Object(array(
            'code' => 'subtotal',
            'value' => $source->getSubtotal(),
            'label' => $this->__('Subtotal')
        ));


        /**
         * Add shipping
         */
        if (!$source->getIsVirtual() && ((float)$source->getShippingAmount() || $source->getShippingDescription())) {
            $this->_totals['shipping'] = new Varien_Object(array(
                'code' => 'shipping',
                'field' => 'shipping_amount',
                'value' => $this->getSource()->getShippingAmount(),
                'label' => $this->__('Shipping & Handling')
            ));
        }

        /**
         * Add discount
         */
        if (((float)$this->getSource()->getDiscountAmount()) != 0) {
            if ($this->getSource()->getDiscountDescription()) {
                $discountLabel = $this->__('Discount (%s)', $source->getDiscountDescription());
            } else {
                $discountLabel = $this->__('Discount');
            }
            $this->_totals['discount'] = new Varien_Object(array(
                'code' => 'discount',
                'field' => 'discount_amount',
                'value' => $source->getDiscountAmount(),
                'label' => $discountLabel
            ));
        }

        $this->_totals['grand_total'] = new Varien_Object(array(
            'code' => 'grand_total',
            'field' => 'grand_total',
            'strong' => true,
            'value' => $source->getGrandTotal(),
            'label' => $this->__('Grand Total'),
            //'base' => $this->getBaseGrandTotal(),
        ));

        /**
         * Base grandtotal
         */
        if ($this->getOrder()->getBaseCurrencyCode() != $this->getOrder()->getBluesnapCurrencyCode()
            && in_array($source->getPayment()->getMethod(), array('cse', 'buynow', 'bluesnap_checkout'))
        ) {
            //bluesnap currency
            $this->_totals['bluesnap_grandtotal'] = new Varien_Object(array(
                'code' => 'bluesnap_grandtotal',
                // 'value' => $this->getOrder()->formatPrice($source->getBluesnapCurrencyAmount(),Bluesnap_Payment_Helper_Config::BLUESNAP_DEFAULT_CURRENCY),
                'value' => $this->getOrder()->formatBluesnapPrice($source->getBluesnapGrandTotal()),
                'label' => $this->__('Grand Total to be Charged'),
                'is_formated' => true,
            ));

        } elseif ($this->getOrder()->isCurrencyDifferent()) {
            //base currency
            $this->_totals['base_grandtotal'] = new Varien_Object(array(
                'code' => 'base_grandtotal',
                'value' => $this->getOrder()->formatBasePrice($source->getBaseGrandTotal()),
                'label' => $this->__('Grand Total to be Charged'),
                'is_formated' => true,
            ));
        }
        return $this;
    }


}