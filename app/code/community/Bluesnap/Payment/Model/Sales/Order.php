<?php

class Bluesnap_Payment_Model_Sales_Order extends Mage_Sales_Model_Order
{


    protected $_bluesnapCurrency = null;

    /**
     * Get formated price value including order currency rate to order website currency
     *
     * @param   float $price
     * @param   bool $addBrackets
     * @return  string
     */
    public function formatBluesnapPrice($price, $addBrackets = false)
    {
        return $this->formatBluensnapPricePrecision($price, 2, $addBrackets);
    }

    public function formatBluensnapPricePrecision($price, $precision, $addBrackets = false)
    {
        return $this->getBluesnapCurrency()->formatPrecision($price, $precision, array(), true, $addBrackets);
    }

    /**
     * Get currency model instance. Will be used currency with which order placed
     *
     * @return Mage_Directory_Model_Currency
     */
    public function getBluesnapCurrency()
    {
        if (is_null($this->_bluesnapCurrency)) {
            $this->_bluesnapCurrency = Mage::getModel('directory/currency')->load($this->getBluesnapCurrencyCode());
        }
        return $this->_bluesnapCurrency;
    }

}
