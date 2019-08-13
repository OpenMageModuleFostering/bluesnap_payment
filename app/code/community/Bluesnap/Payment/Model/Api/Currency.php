<?php

class Bluesnap_Payment_Model_Api_Currency extends Bluesnap_Payment_Model_Api_Abstract
{

    /**
     * @var Varien_Http_Client
     */
    protected $_httpClient;

    protected $_messages = array();

    function convert($currencyFrom, $currencyTo, $amount)
    {
        $url = $this->getServiceUrl('tools/merchant-currency-convertor');
        $url .= '?from=' . $currencyFrom . '&to=' . $currencyTo . '&amount=' . $amount;

        $this->_httpClient = new Varien_Http_Client();

        try {
            // send request
            $response = $this->_httpClient
                ->setConfig(array('useragent' => $this->_helper()->getUserAgent()))
                ->setAuth($this->getConfig()->getBluesnapApiUsername(), $this->getConfig()->getBluesnapApiPassword())
                ->setUri($url)
                ->request('GET')
                ->getBody();


            if (!$response) {
                $this->getLogger()->logError("", "", 0, "Failed to retrive $currencyFrom to $currencyTo rate", "tools/merchant-currency-convertor", "", $url);
            }

            $xml = simplexml_load_string($response, null, LIBXML_NOERROR);

            if (!$xml) {
                $this->getLogger()->logError("", "", 0, "Failed to retrive $currencyFrom to $currencyTo rate", "tools/merchant-currency-convertor", "", $url);
                $this->_messages[] = Mage::helper('directory')->__('Cannot retrieve BlueSnap rate from %s.', $url);
                return null;
            }
            $value = (float)$xml->value;
            return $value;
        } catch (Exception $e) {
            Mage::logException($e);
            $this->getLogger()->logError("", "", 0, "currency convertor failed to $currencyFrom,$currencyTo", "", $url);

        }
        return null;
    }
}