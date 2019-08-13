<?php

class Bluesnap_Payment_Block_Checkout_Cart_Totals extends Mage_Checkout_Block_Cart_Totals
{
    /**
     * Check if we have display grand total in base currency
     *
     * @return bool
     */
    public function needDisplayBaseGrandtotal()
    {
        $quote = $this->getQuote();
        $payment = $quote->getPayment();

        if ($payment && in_array($payment->getMethod(), Mage::helper('bluesnap')->getCodes())) {
            return false;
        } elseif ($quote->getBaseCurrencyCode() != $quote->getQuoteCurrencyCode()) {
            return true;
        }

        return false;
    }


    function getTotals()
    {
        $this->_totals = parent::getTotals();
        return $this->_totals;
    }
}
