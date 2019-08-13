<?php

class Bluesnap_Payment_Model_Directory_Currency extends Mage_Directory_Model_Currency
{
    protected $_bluesnapCurrencies;

    function _construct()
    {
        parent::_construct();
        $this->_initBluesnapCurrencies();
    }

    protected function _initBluesnapCurrencies()
    {

        // if(!$this->_bluesnapCurrencies) {
        $classArr = explode('_', __CLASS__);
        $moduleName = $classArr[0] . '_' . $classArr[1];
        $etcDir = Mage::getConfig()->getModuleDir('etc', $moduleName);

        $fileName = $etcDir . DS . 'currencies.xml';
        if (is_readable($fileName)) {
            $currenciesXml = file_get_contents($fileName);
            $this->_bluesnapCurrencies = new Varien_Simplexml_Element($currenciesXml);
            $this->_bluesnapCurrencies = $this->_bluesnapCurrencies->asArray();
        }
        //}
    }

    public function getBluesnapCurrencies()
    {
        return $this->_bluesnapCurrencies;
    }

    /**
     * Get Currency name by code
     * @param $currencyCode
     * @return null|string
     */
    public function getBluesnapCurrencyNameByCode($code)
    {
        return $this->_bluesnapCurrencies[$code]['name'];
    }

    /**
     * Check if currency code exists in xml
     * @param $currencyCode
     * @return bool
     */
    public function isBluesnapCurrencyExists($code)
    {
        return array_key_exists($code, $this->_bluesnapCurrencies);
    }

    /**
     * Check status of the currency local_supported param
     * @param $currencyCode
     * @return bool
     */
    public function isBluesnapCurrencySupported($code)
    {
        if (!array_key_exists($code, $this->_bluesnapCurrencies))
            return false;

        return $this->_bluesnapCurrencies[$code]['local_supported'] == 'Y';
    }


    /**
     * @deprecated should be removed
     *
     * @param mixed $toCurrency
     */
    public function getBluesnapRate($toCurrency, $fromCurrency = NULL)
    {
        if (is_string($toCurrency)) {
            $code = $toCurrency;
        } elseif ($toCurrency instanceof Mage_Directory_Model_Currency) {
            $code = $toCurrency->getCurrencyCode();
        } else {
            throw Mage::exception('Mage_Directory', Mage::helper('directory')->__('Invalid target currency.'));
        }
        $bluesnapCurrencyCode = Bluesnap_Payment_Helper_Config::BLUESNAP_DEFAULT_CURRENCY;
        $allowedCurrencies = $this->getConfigAllowCurrencies();
        $rates = $this->getCurrencyRates($fromCurrency, $allowedCurrencies);
        if (!isset($rates[$code])) {
            $rate = $this->_getResource()->getRate($this->getCode(), $toCurrency);

            if (!$rate) {
                $rate = $this->_getResource()->getRate($toCurrency, $this->getCode());
                if ($rate)
                    $rate = 1 / $rate;
            }


            $rates[$code] = $rate;
            $this->setRates($rates);
        }
        return $rates[$code];
    }

    /**
     * @deprec to be removed
     *
     */
    function saveBluesnapRates()
    {

    }


    /**
     * Convert amount to the USD (bluesnap base currency)
     * @param $amount
     * @param $currencyCode
     * @param $baseAmount
     * @param $baseCurrencyCode
     * @return mixed
     */
    public function convertToBluesnapDefault($baseAmount, $baseCurrencyCode)
    {
        $toCurrency = Bluesnap_Payment_Helper_Data::BLUESNAP_DEFAULT_CURRENCY;

        // uncomment for realtime convertion
        $sum = null; //Mage::getModel('bluesnap/import')->convert($currencyCode, $toCurrency, $amount);

        if (!$sum) {
            $sum = Mage::helper('directory')->currencyConvert($baseAmount, $baseCurrencyCode, $toCurrency);
        }

        return $sum;
    }


    /**
     * @param float $amount
     * @param string|null $currency
     * @return string
     * currency formatter from helper moved here
     * @throws Zend_Currency_Exception
     */
    public function formatBluesnapDefault($amount, $currency = null)
    {
        if (is_null($currency)) {
            $currency = Bluesnap_Payment_Helper_Data::BLUESNAP_DEFAULT_CURRENCY;
        }

        return Mage::app()
            ->getLocale()
            ->currency($currency)
            ->toCurrency($amount);
    }

    /**
     * Convert currency and quote amount to the supported currency
     * seems to be no use
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     */
    public function prepareQuoteAmount(Mage_Sales_Model_Quote $quote)
    {
        $baseAmount = $quote->getBaseGrandTotal();
        $amount = $quote->getGrandTotal();
        $currency = $quote->getQuoteCurrencyCode();

        return $this->prepareAmount($amount, $currency, $baseAmount);
    }

    /**
     * Convert currency and amount to the supported currency
     * @param $amount
     * @param $currency
     * @param $baseAmount
     * @return array
     */
    protected function prepareAmount($amount, $currency, $baseAmount)
    {
        $baseCurrency = Mage::app()->getStore()->getBaseCurrencyCode();
        $currencyModel = Mage::getSingleton('bluesnap/currencies');
        /* @var $currencyModel Bluesnap_Payment_Model_Currencies */

        $result = array('amount' => $amount, 'currency' => $currency);
        if (!$currencyModel->isCurrencyExists($currency) || !$currencyModel->isCurrencySupported($currency)) {
            $result['currency'] = Bluesnap_Payment_Helper_Data::BLUESNAP_DEFAULT_CURRENCY;
            $result['amount'] = $this->convertToBase($amount, $currency, $baseAmount, $baseCurrency);
        }

        return $result;
    }

    /**
     * Convert currency and order amount to the supported currency
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function prepareOrderAmount(Mage_Sales_Model_Order $order)
    {
        $baseAmount = $order->getBaseGrandTotal();
        $amount = $order->getGrandTotal();
        $currency = $order->getOrderCurrencyCode();

        return array(
            'amount' => $amount = round($order->getBluesnapGrandTotal(), 2),
            'currency' => $order->getBluesnapCurrencyCode(),
        );
        // return $this->prepareAmount($amount, $currency, $baseAmount);
    }


    public function convert($price, $toCurrency = null)
    {
        if (is_null($toCurrency)) {
            return $price;
        } else {
            $rate = $this->getRate($toCurrency);
            if ($rate) {
                return $price * $rate;
            }
        }

        throw new Exception(Mage::helper('directory')->__('Undefined rate from "%s-%s".', $this->getCode(),
            is_object($toCurrency) ? $toCurrency->getCode() : $toCurrency));
    }

}
