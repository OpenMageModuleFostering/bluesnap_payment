<?php

class Bluesnap_Payment_Block_Checkout_Grandtotal extends Mage_Tax_Block_Checkout_Grandtotal
{
    protected $allowedActions = array('checkout_onepage_savePayment', 'bluesnap_checkout_redirect');

    public function _construct()
    {
        $request = Mage::app()->getRequest();
        $handler = $request->getRouteName() . '_' . $request->getControllerName() . '_' . $request->getActionName();

        if (in_array($handler, $this->allowedActions)) {
            $payment = $this->getQuote()->getPayment();

            if ($payment && in_array($payment->getMethod(), Mage::helper('bluesnap')->getCodes())) {
                $this->_template = 'bluesnap/payment/tax/checkout/grandtotal.phtml';
            }
        }

        parent::_construct();
    }

    public function getBaseGrandTotal()
    {
        $quote = $this->getQuote();
        $baseCurrency = $this->getQuote()->getBaseCurrencyCode();


        $result = null;

        if (!$this->_currencyModel()->isBluesnapCurrencyExists($baseCurrency)
            || !$this->_currencyModel()->isBluesnapCurrencySupported($baseCurrency)
        ) {
            $bluesnapAmount = $quote->getBluesnapGrandTotal();
            $result = sprintf('[%s]', $this->_currencyModel()->formatBluesnapDefault($bluesnapAmount));
            return $result;
        } elseif ($quote->getBaseCurrencyCode() != $quote->getQuoteCurrencyCode()) {

            $baseCurrency = Mage::getModel('directory/currency')->load($quote->getBaseCurrencyCode());
            return "[" . $baseCurrency->format($quote->getBaseGrandTotal()) . "]";
        }


        //return $result;
    }

    /**
     * @return Bluesnap_Payment_Model_Directory_Currency
     *
     */
    function _currencyModel()
    {
        return Mage::getSingleton('Bluesnap_Payment_Model_Directory_Currency');
    }
}