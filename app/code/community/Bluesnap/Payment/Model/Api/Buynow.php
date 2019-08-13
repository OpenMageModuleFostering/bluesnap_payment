<?php

/**
 * BlueSnap BuyNow2 API Calls
 * refactored from Bluesnap_Payment_Model_Request_Buynow
 */
class Bluesnap_Payment_Model_Api_Buynow extends Bluesnap_Payment_Model_Api_Abstract
{

    const PAYMENT_PAGE_URL = 'https://checkout.bluesnap.com/buynow/checkout';
    const PAYMENT_PAGE_URL_SANDBOX = 'https://sandbox.bluesnap.com/buynow/checkout';

    /**
     * http://docs.bluesnap.com/api/services/catalog/decrypt-parameters
     *
     * @param string $token
     * @param mixed $order
     * @return mixed
     */
    public function paramDecryption($token)
    {
        // compose request XML
        $paramsXml = '';
        foreach ($params as $key => $value) {
            $paramsXml .= "<parameter>\n"
                . "<param-key>{$this->_wrapCdata($key)}</param-key>\n"
                . "<param-value>{$this->_wrapCdata($value)}</param-value>\n"
                . "</parameter>\n";
        }
        $requestXml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<param-decryption xmlns=\"{$this->_getXmlNs()}\">"
            . "<encrypted-token>"
            . $token
            . "</encrypted-token>\n"
            . "</param-decryption>\n";

        // send request
        $url = $this->getServiceUrl('tools/param-decryption');
        // $this->_logDebug($requestXml);
        $responseXml = $this->_request($url, $requestXml);
        $xml = $this->_parseXmlResponse($responseXml);

        if ($xml->message && (string)$xml->message->{'error-name'}) {
            $e = new Bluesnap_Payment_Model_Api_Exception($xml->message->{'error-name'} . "\n" . $xml->message->description, (int)$xml->message->code);
            Mage::logException($e);
            $this->getLogger()->logError($requestXml, $responseXml, (int)$xml->message->code,
                $xml->message->description, "tools/param-encryption", "", $url);
            throw $e;
        }

        return (string)$xml->{'decrypted-token'};
    }

    /**
     * Get order payment pate URL
     *
     * @param Mage_Sales_Model_Order $order
     * moved from helper
     * @return string
     */
    public function getOrderBuynowRedirectUrl(Mage_Sales_Model_Order $order)
    {
        $helper = $this->_helper();
        /* @var $helper Bluesnap_Payment_Helper_Data */
        //these are url params, added to url
        $params = array();
        $params['storeId'] = $this->getConfig()->getBluesnapStoreId();
        // billing address
        $params += $this->billingAddressParams($order->getBillingAddress());

        $orderSku = $this->getConfig()->getBluesnapBuynowOrderContractId();

        $params['sku' . $orderSku] = 1;

        $sum = $this->_currencyModel()->prepareOrderAmount($order);

        // order ID field
        $params['custom1'] = $order->getIncrementId();
        $params['language'] = $helper->getLangByLocale();
        //itzik
        //      $params['currency'] = $order->getOrderCurrencyCode();
        $params['currency'] = $order->getBluesnapCurrencyCode();
        //$params['returnUrl'] = 'http://bsmagento.qa.fisha.co.il/thankyou.php';

        // $params['returnUrl']=  Mage::app()->getStore()->getUrl("checkout/onepage/success");

        //these are encryption params
        // http://docs.bluesnap.com/api/services/tools/encrypt-parameters
        $paramsEnc = array(
            "sku{$orderSku}priceamount" => round($sum['amount'], 2),
            "sku{$orderSku}name" => $this->_getOrderItemOverrideName($order),
            "sku{$orderSku}pricecurrency" => $sum['currency'],
            'expirationInMinutes' => 90
        );

        if ($order->getCustomerId()) {
            //if returning customer
            $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
            if ($customer->getId() && $customer->getBsAccountId()) {
                $paramsEnc['shopperId'] = $customer->getBsAccountId();
                $paramsEnc['expirationInMinutes'] = 300;
                $paramsEnc['pageName'] = 'AUTO_LOGIN_PAGE';
            }
        }

        if ($callbackUrl = $this->getConfig()->getConfigData('general/callback_url')) {
            $paramsEnc['thankyou.backtosellerurl'] = urlencode($callbackUrl);
        }


        $enc = $this->paramEncryption(
            $paramsEnc, $order);

        $url = $this->_getCheckoutUrl()
            . '?' . http_build_query($params)
            . '&enc=' . $enc;
        return $url;
    }

    /**
     * Create array of Billing Address Buynow Parameters.
     * @param Mage_Customer_Model_Address_Abstract $address
     * @return array
     */
    protected function billingAddressParams(Mage_Customer_Model_Address_Abstract $address)
    {
        $params = array();
        $params['email'] = $address->getEmail();
        //$params['validateEmail'] = $address->getEmail();
        $params['firstName'] = $address->getFirstname();
        $params['lastName'] = $address->getLastname();

        //$params['city'] = $address->getCity();
        $params['state'] = $address->getRegionCode();
        //$params['zipCode'] = $address->getPostcode();
        $params['country'] = strtolower($address->getCountry());
        //$params['workPhone'] = $address->getTelephone();

        return $params;
    }

    /**
     * Get BuyNow Checkout product name for given Order
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    protected function _getOrderItemOverrideName(Mage_Sales_Model_Order $order)
    {
        return sprintf('Order #%s', $order->getIncrementId());
    }

    /**
     * Send Parameter Encryption request,
     * return URL-encoded (by BlueSnap service) encryption token
     *
     * @param array $params Parameters to be encoded
     * @see http://docs.bluesnap.com/api/services/tools/encrypt-parameters
     * @params Mage_Sales_Model_Order
     *
     * @return string
     * @throws Mage_Core_Exception
     */
    public function paramEncryption(array $params, $order = null)
    {
        // compose request XML
        $paramsXml = '';
        foreach ($params as $key => $value) {
            $paramsXml .= "<parameter>\n"
                . "<param-key>{$this->_wrapCdata($key)}</param-key>\n"
                . "<param-value>{$this->_wrapCdata($value)}</param-value>\n"
                . "</parameter>\n";
        }
        $requestXml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<param-encryption xmlns=\"{$this->_getXmlNs()}\">\n"
            . "<parameters>\n"
            . $paramsXml
            . "</parameters>\n"
            . "</param-encryption>\n";

        // send request
        $url = $this->getServiceUrl('tools/param-encryption');
        // $this->_logDebug($requestXml);
        $responseXml = $this->_request($url, $requestXml);
        $xml = $this->_parseXmlResponse($responseXml);

        if ($xml->message && (string)$xml->message->{'error-name'}) {
            $e = new Bluesnap_Payment_Model_Api_Exception($xml->message->{'error-name'} . "\n" . $xml->message->description, (int)$xml->message->code);
            Mage::logException($e);
            $this->getLogger()->logError($requestXml, $responseXml, (int)$xml->message->code,
                $xml->message->description, "tools/param-encryption", $order->getIncrementId(), $url);
            throw $e;
        }

        $this->getLogger()->logSuccess($requestXml, $responseXml, 0,
            "param-encryption success", "param-encryption", $order->getIncrementId(), $url);
        return (string)$xml->{'encrypted-token'};
    }

    /**
     * Get BuyNow Checkout URL depending on Sandbox Mode flag
     * @return string
     */
    protected function _getCheckoutUrl()
    {
        return $this->getConfig()->isSandBoxMode()
            ? self::PAYMENT_PAGE_URL_SANDBOX
            : self::PAYMENT_PAGE_URL;
    }

    /**
     * Create array of Shipping Address Buynow Parameters.
     * @param Mage_Customer_Model_Address_Abstract $address
     * @return array
     */
    protected function shippingAddressParams(
        Mage_Customer_Model_Address_Abstract $address
    )
    {
        $params = array();
        $params['shippingFirstName'] = $address->getFirstname();
        $params['shippingLastName'] = $address->getLastname();
        $params['shippingAddress1'] = $address->getStreet1();
        $address2 = $address->getStreet2();
        if (!empty($address2)) {
            $params['shippingAddress2'] = $address2;
        }
        $params['shippingCity'] = $address->getCity();
        $params['shippingState'] = $address->getRegionCode();
        $params['shippingZipCode'] = $address->getPostcode();
        $params['shippingCountry'] = strtolower($address->getCountry());
        $params['shippingWorkPhone'] = $address->getTelephone();
        $params['shippingFaxNumber'] = $address->getFax();

        return $params;
    }

    /**
     * Get second address string
     * Implode all address strings starting from second
     * @param Mage_Customer_Model_Address_Abstract $address
     * @return string
     */
    private function _getAddress2(
        Mage_Customer_Model_Address_Abstract $address
    )
    {
        $streetArr = $address->getStreet();
        return implode(' ', array_slice($streetArr, 1));
    }

    /**
     * Convert BlueSnap country code to ISO 2-letter country code
     * @param string $countryCode
     * @return string
     */
    private function _countryCodeBluesnapToIso2($countryCode)
    {
        return strtoupper($countryCode);
    }


}
