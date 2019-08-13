<?php

/**
 * BlueSnap API Client
 * refactored from Bluesnap_Payment_Model_Request_Api
 */
class Bluesnap_Payment_Model_Api_Abstract
{
    const API_BASE_URL = 'https://ws.bluesnap.com/services';
    const API_BASE_URL_SANDBOX = 'https://sandbox.bluesnap.com/services';
    const VERSION = '2';
    const XML_NS = 'http://ws.plimus.com';

    const EXCEPTION_HTTP_CLIENT_ERROR = 1;
    const EXCEPTION_API_ERROR = 2;

    const HTTP_METHOD_POST = 0;
    const HTTP_METHOD_PUT = 1;
    const HTTP_METHOD_GET = 2;

    const CURLOPT_CONNECTTIMEOUT = 30;
    const CURLOPT_TIMEOUT = 30;

    protected $_requestXml;
    protected $_responseXml;
    protected $_curlInfo;
    protected $_curlError;

    /**
     * @return Mage_Checkout_Model_Session
     *
     */
    function getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * @return Bluesnap_Payment_Helper_Data
     *
     */
    function _helper()
    {
        return Mage::helper('bluesnap');
    }

    /**
     * @return Bluesnap_Payment_Model_Directory_Currency
     *
     */
    function _currencyModel()
    {
        return Mage::getSingleton('Bluesnap_Payment_Model_Directory_Currency');
    }

    /**
     * Get API Service URL
     * @param string $service Service name (path from base URL)
     * @param string
     * @return string
     */
    public function getServiceUrl($service)
    {
        $apiUrl = $this->getConfig()->isSandBoxMode()
            ? self::API_BASE_URL_SANDBOX
            : self::API_BASE_URL;
        $url = implode('/', array($apiUrl, self::VERSION, $service));
        return $url;
    }

    /**
     * @return Bluesnap_Payment_Model_Api_Config
     *
     */
    function getConfig()
    {
        return Mage::getSingleton('Bluesnap_Payment_Model_Api_Config');
    }

    function http_parse_headers($header)
    {
        $retVal = array();
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
        foreach ($fields as $field) {
            if (preg_match('/([^:]+): (.+)/m', $field, $match)) {
                $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
                if (isset($retVal[$match[1]])) {
                    $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
                } else {
                    $retVal[$match[1]] = trim($match[2]);
                }
            }

            return $retVal;
        }
    }

    /**
     * Send request, return response XML object.
     * @param string $url API Service Url
     * @param string $requestXml
     * @param int $httpMethod
     * @param bool $parse_headers
     * @return Varien_Simplexml_Element $responseXml
     * @throws Mage_Core_Exception
     */
    protected function _request(
        $url,
        $requestXml,
        $httpMethod = self::HTTP_METHOD_POST,
        $parse_headers = false,
        $curl_headers = false
    )
    {
        $this->_requestXml = $requestXml;

        $ch = curl_init();
        $options = array(
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => Mage::helper('bluesnap')->getUserAgent(),
            CURLOPT_COOKIESESSION => true,
            CURLOPT_HTTPHEADER => array('Content-Type: application/xml'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->getConfig()->getBluesnapApiUsername() . ':' . $this->getConfig()->getBluesnapApiPassword(),
            CURLOPT_ENCODING => 'gzip,deflate',
            CURLOPT_URL => $url,
            CURLOPT_CONNECTTIMEOUT => self::CURLOPT_CONNECTTIMEOUT,
            CURLOPT_TIMEOUT => self::CURLOPT_TIMEOUT,
        );


        if (!empty($requestXml)) {
            $options[CURLOPT_HTTPHEADER][] = 'Content-length: ' . strlen($requestXml);
        }

        if ($parse_headers || $curl_headers) {
            $options[CURLOPT_HEADER] = true;
            $options[CURLOPT_VERBOSE] = true;
        } else {
            $options[CURLOPT_HEADER] = false;
        }

        switch ($httpMethod) {
            case self::HTTP_METHOD_PUT:
                // $options[CURLOPT_PUT] = true;
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';

                if (!empty($requestXml)) {
                    $options[CURLOPT_POSTFIELDS] = $requestXml;
                }
                break;
            case self::HTTP_METHOD_POST:
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = $requestXml;
                break;
            case self::HTTP_METHOD_GET:
                $options[CURLOPT_CUSTOMREQUEST] = 'GET';
                $options[CURLOPT_HTTPGET] = true;
                break;
        }
        curl_setopt_array($ch, $options);
        $responseXml = curl_exec($ch);
        $this->_responseXml = $responseXml;
        $this->_curlInfo = curl_getinfo($ch);
        $this->_curlError = curl_error($ch);

        if ($this->_curlInfo['http_code'] == 401) {
            //bad credentials
            $this->_curlError = "Bad credentials (401)";
            $e = Mage::exception(
                'Bluesnap_Payment',
                'cURL error ' . ' Bad credentials (401).',
                self::EXCEPTION_HTTP_CLIENT_ERROR
            );

            //  $this->_curlError=$error;
            curl_close($ch);
            Mage::logException($e);
            $this->getLogger()->logError($this->_requestXml, $this->_responseXml, 0, $url . ': ' . $this->_curlError, "", "", $url);
            throw $e;

        }
        if ($parse_headers) {
            $response = str_ireplace('HTTP/1.1 100 Continue', '', $responseXml);
            $headers = http_parse_headers($response);
            if (isset($headers['Response Code'], $headers['Location']) && $headers['Response Code'] == '201') {
                $responseXml = $headers['Location'];
            } else {
                $e = Mage::exception(
                    'Bluesnap_Payment',
                    'cURL error ' . curl_errno($ch) . ': ' . curl_error($ch),
                    self::EXCEPTION_HTTP_CLIENT_ERROR
                );

                //  $this->_curlError=$error;
                curl_close($ch);
                Mage::logException($e);
                $this->getLogger()->logError($this->_requestXml, $this->_responseXml, 0, $url . ': ' . $this->_curlError, "", "", $url);
                throw $e;
            }
        } else {
            if ($responseXml === false) {
                $error = $url . ': ' . 'cURL error ' . curl_errno($ch) . ': ' . curl_error($ch);
                $this->_curlError = $error;
                curl_close($ch);

                $e = new Bluesnap_Payment_Model_Api_Exception(
                    $error,
                    self::EXCEPTION_HTTP_CLIENT_ERROR
                );
                Mage::logException($e);
                $this->getLogger()->logError($this->_requestXml, $this->_responseXml, 0, $error, "", "", $url);
                throw $e;
            } else {
                if ($this->_curlInfo['http_code'] == 201) {
                    return $responseXml;
                }
            }
        }

        curl_close($ch);

        //  $this->_logDebug("Response text:\n{$responseXml}\n");
        return $responseXml; // some methods don't return XML!
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
     * COnvert response to Simplexml_Element
     * @param $responseXml
     * @return Varien_Simplexml_Element
     * @throws Mage_Core_Exception
     */
    protected function _parseXmlResponse($responseXml)
    {
        // $responseXml.="stam";

        try {
            libxml_use_internal_errors(true);
            $xml = new Varien_Simplexml_Element($responseXml);
            $xml->registerXPathNamespace('ns1', $this->_getXmlNs());
            libxml_use_internal_errors(false);
        } catch (Exception $exc) {
            libxml_use_internal_errors(false);
            $e = new Bluesnap_Payment_Model_Api_Exception('Invalid response XML: ' . $responseXml, self::EXCEPTION_API_ERROR);
            Mage::logException($e);
            $this->getLogger()->logError($this->_requestXml, $this->_responseXml, 0, "Invalid response XML", "parseXmlResponse");
            // throw $e;

        }
        return $xml;
    }

    /**
     * Get API request XML namespace
     * @return string
     */
    protected function _getXmlNs()
    {
        return self::XML_NS;
    }

    /**
     * Wrap string into <![CDATA[ ]]>
     * @param string $string
     * @return string
     */
    protected function _wrapCdata($string)
    {
        return '<![CDATA['
        . str_replace(']]>', ']]><![CDATA[', $string)
        . ']]>';
    }


}