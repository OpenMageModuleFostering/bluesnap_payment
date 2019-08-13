<?php

/**
 * BlueSnap Saved API Calls
 */
class Bluesnap_Payment_Model_Api_Saved extends Bluesnap_Payment_Model_Api_Cse
{
    private $cardTypes = array(
        'VI' => 'VISA',
        'VISA' => 'VISA',
        'AE' => 'AMEX',
        'AMEX' => 'AMEX',
        'MC' => 'MASTERCARD',
        'MASTERCARD' => 'MASTERCARD',
        'DI' => 'DISCOVER',
        'DISCOVER' => 'DISCOVER',
        'DC' => 'DINERS',
        'DINERS' => 'DINERS',
        'JCB' => 'JCB',
        'CB' => 'CARTE_BLEUE',
        'CARTE_BLEUE' => 'CARTE_BLEUE',
    );

    /**
     * Trigger place order requests for returning shopper
     * @param Varien_Object $payment
     * @param int|null $bsShopperId
     * @return array
     */
    public function placeOrder(Varien_Object $payment, $bsShopperId = null, $action = NULL)
    {

        $paymentaction = strtolower(Mage::getStoreConfig('payment/cse/payment_action'));
        if ($paymentaction != 'authorize_capture' && $action != 'invoice') {
            $response = $this->createAuthOrder($payment, $bsShopperId);
        } else {
            $response = $this->createOrder($payment, $bsShopperId, $action);
			
        }
        return $response;
    }

    /**
     * Create shopping-context for the existing shopper
     * @link http://docs.bluesnap.com/api/services/shopping-context/create-shopping-context
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param int $bsShopperId
     * @return Varien_Simplexml_Element
     * @throws Mage_Core_Exception
     */
    public function createAuthOrder(Varien_Object $payment, $bsShopperId, $amount = null)
    {


        $order = $payment->getOrder();
        $sum = $this->_currencyModel()->prepareOrderAmount($order);

        // we are setting atuhorize amount to $1

        //we need to convert to USD if different
        if ($sum['currency'] != 'USD') {
            $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();

            $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
            $price = 1;


            // convert price from base currency to current currency
            $currencyApi = Mage::getModel('bluesnap/api_currency');
            $currResponse = $currencyApi->convert('USD', $baseCurrencyCode, $price);

            $sum['amount'] = $currResponse;

        } else $sum['amount'] = 1;

        if (Mage::app()->getRequest()->getParam('encryptedCreditCard')) {
            $response = $this->addCardToShopper($payment, $sum, $bsShopperId);
            
            $CCs = $response->descend("shopper-info/payment-info/credit-cards-info/credit-card-info");

			foreach($CCs AS $cc) {		
				if(
					(int)$cc->descend("credit-card/expiration-month") == $payment->getCcExpMonth() &&
					(int)$cc->descend("credit-card/expiration-year") == $payment->getCcExpYear() &&
					(int)$cc->descend("credit-card/card-last-four-digits") == $payment->getCcLast4()										
				) {
					$validCC = $cc->descend("credit-card");
				}		
			}			
                                      
           	if($validCC) {
				$type = (string) $validCC->descend("card-type");
				$payment->setCcType($type)->save();
			} else {
				$type = (isset($this->cardTypes[$payment->getCcType()]) ? $this->cardTypes[$payment->getCcType()] : '');
			}

        } else {
            $this->updateShopperWithNoCC($payment, $sum, $bsShopperId);
            $type = (isset($this->cardTypes[$payment->getCcType()]) ? $this->cardTypes[$payment->getCcType()] : '');
        }

        $data1 = array(
            'web-info' => array(
                'ip' => $order->getRemoteIp() ? $order->getRemoteIp() : $_SERVER['REMOTE_ADDR'],
                'remote-host' => $order->getRemoteIp() ? $order->getRemoteIp() : $_SERVER['REMOTE_ADDR'],
                'user-agent' => Mage::helper('core/http')->getHttpUserAgent(),
            ),
            'order-details' => array(
                'order' => array(
                    'ordering-shopper' => array(
                        'shopper-id' => $bsShopperId,
                        'credit-card' => array(
                            'card-last-four-digits' => $payment->getCcLast4(),
                            'card-type' => $type,
                        ),
                        'fraud-info' => array(
                            'fraud-session-id' => Mage::getSingleton('checkout/session')->getSessionId(),
                        ),
                    ),

                    'cart' => array(
                        'cart-item' => array(
                            'sku' => array(
                                'sku-id' => Mage::helper('bluesnap')->getBluesnapBuynowOrderContractId(),
                                'sku-charge-price' => array(
                                    'charge-type' => 'initial',
                                    'amount' => $sum['amount'],
                                    'currency' => $sum['currency'],
                                    'charged-currency' => $sum['currency'],
                                ),
                            ),
                            'quantity' => '1',
                            'sku-parameter' => array(
                                'param-name' => Bluesnap_Payment_Model_Ipn::PARAM_ORDER_INCREMENT_ID,
                                'param-value' => $order->getIncrementId(),
                            ),
                        ),
                    ),
                    'expected-total-price' => array(
                        'amount' => $sum['amount'],
                        'currency' => $sum['currency'],
                        'charged-currency' => $sum['currency'],
                    ),
                ),
            ),
        );

        $xml = new SimpleXMLElement('<shopping-context xmlns="' . $this->_getXmlNs() . '"/>');
        $request = Mage::helper('bluesnap')->arrayToXml($data1, $xml)->asXML();


        $logData = $data1;
      
        $logRequestXml = new SimpleXMLElement('<shopping-context xmlns="' . $this->_getXmlNs() . '"/>');
        $logRequestXml = Mage::helper('bluesnap')->arrayToXml($logData, $logRequestXml)->asXML();


        // send request
        $url = $this->getServiceUrl('shopping-context');
        $response = $this->_request($url, $request, 0, false, true);

        $responseArr = $this->parseHeaders($response);
        // @todo : no $logResponse;
        $logResponse->shopper->{'shopper-info'}->password = '****';

        //  $this->_responseXml=$logResponse->asXml();


        try {
            //   $response = $this->parseCreateResponse($response, null, $order);
        } catch (Exception $e) {
            throw new Bluesnap_Payment_Model_Api_Exception(Mage::helper('core')->__('Something went wrong during placing your order. Please contact support.'));
        }

        if ($responseArr['http_code'] == '201') $this->getLogger()->logSuccess($logRequestXml, $response, 0, "Authorize Saved success", "Authorize", $order->getIncrementId(), $url);
        else  $this->getLogger()->logError($logRequestXml, $response, 0, "Authorize Error", "Authorize", $order->getIncrementId(), $url);

        return $responseArr;

    }

    public function parseHeaders($headers_string)
    {
        $headers = array();
        foreach (explode("\n", $headers_string) as $line) {
            $line = trim($line);
            if (strpos($line, ':') !== false) {
                list($headerName, $headerValue) = explode(':', $line, 2);
                $headerValue = ltrim($headerValue);
                $headerName = strtolower(rtrim($headerName));
                if (isset($headers[$headerName])) {
                    if (is_array($headers[$headerName])) {
                        $headers[$headerName][] = $headerValue;
                    } else {
                        $headers[$headerName] = array(
                            $headers[$headerName],
                            $headerValue
                        );
                    }
                } else {
                    $headers[$headerName] = $headerValue;
                }
            } else {
                if (strpos($line, 'HTTP/1.1') !== false && strpos($line, '201') !== false) {
                    $headers['http_code'] = '201';
                }
            }
        }
        return $headers;
    }

    /**
     * Create order for the existing shopper
     * @param Varien_Object $payment
     * @param int $bsShopperId
     * @return Varien_Simplexml_Element
     * @throws Mage_Core_Exception
     */
    public function createOrder(Varien_Object $payment, $bsShopperId, $action = null)
    {
        $order = $payment->getOrder();
        $sum = $this->_currencyModel()->prepareOrderAmount($order);

        if ($action != 'invoice') {
            if ($payment->getCcNumber()) $this->updateShopper($payment, $sum, $bsShopperId);
            else $this->updateShopperWithNoCC($payment, $sum, $bsShopperId);
        }

        $response = $this->retrieveShopper($bsShopperId, $action, true);
       $CCs = $response->descend("shopper-info/payment-info/credit-cards-info/credit-card-info");

        foreach($CCs AS $cc) {
            if(
                (int)$cc->descend("credit-card/expiration-month") == $payment->getCcExpMonth() &&
                (int)$cc->descend("credit-card/expiration-year") == $payment->getCcExpYear() &&
                (int)$cc->descend("credit-card/card-last-four-digits") == $payment->getCcLast4()
            ) {
                $validCC = $cc->descend("credit-card");
            }
        }

        if($validCC) {
            $type = (string) $validCC->descend("card-type");
            $payment->setCcType($type)->save();
        } else {
            $type = (isset($this->cardTypes[$payment->getCcType()]) ? $this->cardTypes[$payment->getCcType()] : '');
        }

        $data = array(
            'ordering-shopper' => array(
                'shopper-id' => $bsShopperId,
                'credit-card' => array(
                    'card-last-four-digits' => $payment->getCcLast4(),
                    'card-type' => $type,
                ),
                'web-info' => array(
                    'ip' => $order->getRemoteIp() ? $order->getRemoteIp() : $_SERVER['REMOTE_ADDR'],
                    'remote-host' => $order->getRemoteIp() ? $order->getRemoteIp() : $_SERVER['REMOTE_ADDR'],
                    'user-agent' => Mage::helper('core/http')->getHttpUserAgent(),
                ),
                'fraud-info' => array(
                    'fraud-session-id' => Mage::getSingleton('checkout/session')->getSessionId(),
                ),
            ),
            'cart' => array(
                'charged-currency' => $sum['currency'],
                'cart-item' => array(
                    'sku' => array(
                        'sku-id' => Mage::helper('bluesnap')->getBluesnapBuynowOrderContractId(),
                        'sku-charge-price' => array(
                            'charge-type' => 'initial',
                            'amount' => $sum['amount'],
                            'currency' => $sum['currency'],
                        ),
                        'soft-descriptor' => $order->getIncrementId(),
                        'description' => $order->getIncrementId(),
                        'title' => $order->getIncrementId(),
                        //BSNPMG-78
                        'sku-name' => 'Order #' . $order->getIncrementId(),
                    ),
                    'quantity' => '1',
                    'sku-parameter' => array(
                        'param-name' => Bluesnap_Payment_Model_Ipn::PARAM_ORDER_INCREMENT_ID,
                        'param-value' => $order->getIncrementId(),
                    ),
                ),
            ),
            'expected-total-price' => array(
                'amount' => $sum['amount'],
                'currency' => $sum['currency'],
                'charged-currency' => $sum['currency']
            ),
        );

        if ($this->isAdminCapture($action)) {
            unset($data['ordering-shopper']['fraud-info']);
        }

        $xml = new SimpleXMLElement('<order xmlns="' . $this->_getXmlNs() . '"/>');
        $request = Mage::helper('bluesnap')->arrayToXml($data, $xml)->asXML();

        // send request
        $url = $this->getServiceUrl('orders');

        $responseXml = $this->_request($url, $request);
        //return $this->_parseXmlResponse($response);

        $response = $this->_parseXmlResponse($responseXml);

        try {
            $response = $this->parseCreateResponse($response, $bsShopperId, $order);
        } catch (Exception $e) {
            Mage::logException($e);
            
            $error_message = Mage::helper('bluesnap')->getErroMessage($error_code);
            throw new Bluesnap_Payment_Model_Api_Exception($error_message);
        }

        $this->getLogger()->logSuccess($this->_requestXml, $this->_responseXml, 0, "createOrder success", "createOrder", $order->getIncrementId(), $url);

        return $response;

    }

}