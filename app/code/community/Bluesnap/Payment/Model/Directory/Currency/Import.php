<?php

class Bluesnap_Payment_Model_Directory_Currency_Import extends Mage_Directory_Model_Currency_Import_Abstract
{

    const AMOUNT = 10000;

    // protected $_url = 'tools/merchant-currency-convertor';
    // protected $_messages = array();

    /**
     * HTTP client
     *
     * @var Varien_Http_Client
     */
    // protected $_httpClient;

    public function __construct()
    {
        $this->_httpClient = new Varien_Http_Client();
    }

    function getMessages()
    {
        return $this->_messages;
    }

    public function convert($currencyFrom, $currencyTo, $retry = 0)
    {
        return $this->_convert($currencyFrom, $currencyTo, $retry);
    }

    protected function _convert($currencyFrom, $currencyTo, $retry = 0)
    {
        //fall back if not supported
        if (!$this->_currencyModel()->isBluesnapCurrencyExists($currencyFrom)
            || !$this->_currencyModel()->isBluesnapCurrencyExists($currencyTo)
        ) {
            return parent::_convert($currencyFrom, $currencyTo, $retry);
        }


        $value = $this->_api()->convert($currencyFrom, $currencyTo, self::AMOUNT);

        if (!$value) {
            $this->_messages[] = Mage::helper('directory')->__('Cannot retrieve BlueSnap rate from %s.', $this->_url);
            return null;
        }
        $this->_messages['info'][] = "Successfully retrieved $currencyFrom => $currencyTo = " . $value / self::AMOUNT;

        $this->getLogger()->logSuccess('convert rates import', "Successfully retrieved $currencyFrom => $currencyTo = " . $value / self::AMOUNT, 0, "Import Rates success", "convert rates", '', '');

        return (float)$value / self::AMOUNT;


    }

    /**
     * @return Bluesnap_Payment_Model_Directory_Currency
     *
     */
    protected function _currencyModel()
    {
        return Mage::getSingleton('directory/currency');

    }

    /**
     * @return Bluesnap_Payment_Model_Api_Currency
     *
     */
    protected function _api()
    {
        return Mage::getSingleton('bluesnap/api_currency');
    }

    /**
     * @return Bluesnap_Payment_Model_Api_Logger
     *
     */
    function getLogger()
    {
        return Mage::getSingleton('Bluesnap_Payment_Model_Api_Logger');
    }

    /**
     * this is not exactly magento rates fetcher. so let's change names
     * I think I need to remove it
     *
     */
    public function fetchBluesnapRates()
    {
        $data = array();
        $currencies = $this->_getCurrencyCodes();
        $defaultCurrencies = $this->_getDefaultCurrencyCodes();
        
      
        //use USD always
        if (!in_array('USD', $defaultCurrencies))
            $defaultCurrencies[] = 'USD';
        if (!in_array('EUR', $defaultCurrencies))
            $defaultCurrencies[] = 'EUR';

        @set_time_limit(0);
        foreach ($defaultCurrencies as $currencyFrom) {
            if (!isset($data[$currencyFrom])) {
                $data[$currencyFrom] = array();
            }
			$currency=Mage::getModel('directory/currency');
            foreach ($currencies as $currencyTo) {
                if ($currencyFrom == $currencyTo) {
                    $data[$currencyFrom][$currencyTo] = array(
                        'bluesnap_supported' => $currency->isBluesnapCurrencyExists($currencyTo),
                        'value' => $this->_numberFormat(1),
                    );
                } else {
                    $data[$currencyFrom][$currencyTo] = array(
                        'bluesnap_supported' => $currency->isBluesnapCurrencyExists($currencyTo) && $currency->isBluesnapCurrencyExists($currencyFrom),
                        'value' => $this->_numberFormat($this->_convert($currencyFrom, $currencyTo)),

                    );
                }
            }
            ksort($data[$currencyFrom]);
        }

        return $data;
    }

    /**
     * Import rates
     * @deprec to be removed
     *
     * @return Mage_Directory_Model_Currency_Import_Abstract
     */
    public function importBluesnapRates()
    {
        $data = array();
        $currencies = $this->_getCurrencyCodes();
        $defaultCurrencies = $this->_getDefaultCurrencyCodes();
        //use USD always
        if (!in_array('USD', $defaultCurrencies))
            $defaultCurrencies[] = 'USD';
        if (!in_array('EUR', $defaultCurrencies))
            $defaultCurrencies[] = 'EUR';

        @set_time_limit(0);
        foreach ($defaultCurrencies as $currencyFrom) {
            if (!isset($data[$currencyFrom])) {
                $data[$currencyFrom] = array();
            }

            foreach ($currencies as $currencyTo) {
                if ($currencyFrom == $currencyTo) {
                    $data[$currencyFrom][$currencyTo] = array(
                        'bluesnap_supported' => $this->_currency()->isBluesnapCurrencyExists($currencyTo),
                        'value' => $this->_numberFormat(1),
                    );
                } else {
                    $data[$currencyFrom][$currencyTo] = array(
                        'bluesnap_supported' => $this->_currency()->isBluesnapCurrencyExists($currencyTo) && $this->_currency()->isBluesnapCurrencyExists($currencyFrom),
                        'value' => $this->_numberFormat($this->_convert($currencyFrom, $currencyTo)),

                    );
                }


                Mage::getSingleton('directory/currency')->saveRates($data);
                $data[$currencyFrom] = array();

            }
            // ksort($data[$currencyFrom]);
        }

        return $this;
    }

    /**
     * @return Bluesnap_Payment_Helper_Data
     *
     */
    protected function _helper()
    {
        return Mage::helper('bluesnap');
    }

    /**
     * Saving currency rates
     * @deprec to be removed
     * @param   array $rates
     * @return  Mage_Directory_Model_Currency_Import_Abstract
     */
    protected function _saveBluesnapRates($rates)
    {
        //   foreach ($rates as $currencyCode => $currencyRates) {
        Mage::getModel('directory/currency')
            //        ->setId($currencyCode)
            //      ->setRates($currencyRates)
            //must be saveRates, not save()
            ->saveBluesnapRates($rates);
        //  }
        return $this;
    }


}

