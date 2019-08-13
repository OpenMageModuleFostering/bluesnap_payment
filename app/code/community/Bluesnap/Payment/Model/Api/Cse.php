<?php

/**
 * BlueSnap CSE API Calls
 */
class Bluesnap_Payment_Model_Api_Cse extends Bluesnap_Payment_Model_Api_Abstract
{
    /**
     * create order errors:
     * http://docs.bluesnap.com/api/services/orders/create-order
     * General Error Name    Description
     * AMOUNT_EXCEEDED_MAXIMUM_ALLOWED_FRACTION_DIGITS    The amount contains more decimal fraction digits than allowed for the chosen currency.
     * EMPTY_CART    The shopping cart is empty.
     * EXPECTED_TOTAL_PRICE_FAILURE    Expected total price does not match the calculated total price.
     * FRAUD_DETECTED    Failure due to a fraud detection.
     * INVALID_AFFILIATE_ID    Invalid affiliate ID.
     * CDOD_NOT_SUPPORTED_IN_CART    The cart contains CDoD but no item in the cart supports CDoD.
     * EDW_NOT_SUPPORTED_IN_CART    The cart contains EDW but no item in the cart supports EDW.
     * MULTIPLE_PAYMENT_METHODS_NON_SELECTED    Shopper has multiple payment methods, but none is selected.
     * NEGATIVE_AMOUNT_FAILURE    The total amount is negative.
     * NO_AVAILABLE_PROCESSORS    There are no available processors for the specific request.
     * ORDER_INVOICE_OR_SUBSCRIPTION_ID_REQUIRED    Order invoice or subscription ID is required.
     * ORDER_NOT_FOUND    The order ID passed in the request was not found.
     * PAYMENT_INFO_REQUIRED    Payment info is required.
     * PAYMENT_METHOD_NOT_SUPPORTED    The payment method is not supported for this request.
     * SELLER_SHOPPER_ALREADY_EXISTS    Internal merchant shopper ID already exists.
     * SERVER_GENERAL_FAILURE    A Server general failure has occurred.
     * SHOPPER_ID_REQUIRED    Shopper ID is required.
     * SHOPPER_IP_REQUIRED    Shopper IP address is required.
     * SHOPPER_NOT_FOUND    The Shopper ID passed in the request was not found.
     * SKRILL_PROCESSING_FAILURE    Skrill processing failure.
     * SKU_NOT_FOUND    The SKU ID passed in the request was not found.
     * THREE_D_SECURITY_AUTHENTICATION_REQUIRED    3D security authentication is required.
     * TOO_MANY_PAYMENT_METHODS_SELECTED    Only one selected payment method is allowed for shopper.
     * USER_NOT_AUTHORIZED    The user is not authorized to perform this operation.
     * VAT_VALIDATOR_GENERAL_FAILURE    General Vat ID validation failure.
     * Processing Error Name    Description
     * CALL_ISSUER    Payment processing failure due to an unspecified error . Please contact the issuing bank.
     * CVV_ERROR    Payment processing failure due to CVV error.
     * DO_NOT_HONOR    Payment processing failure due to a "Do not honor" status. The issuing bank has put a temporary hold on the card, retry with a different card
     * HIGH_RISK_ERROR    Payment processing failure due to high risk.
     * INCORRECT_INFORMATION    Payment processing failure due to incorrect information.
     * INVALID_CARD_NUMBER    Payment processing failure due to invalid card number.
     * INVALID_CARD_TYPE    Payment processing failure due to invalid card type.
     * INVALID_PIN_OR_PW_OR_ID_ERROR    Payment processing failure due to invalid PIN or password or ID error.
     * LIMIT_EXCEEDED    Payment processing failure because card limit has exceeded.
     * PICKUP_CARD    Payment processing failure. The card has been reported lost or stolen and should be removed from useץ
     * PROCESSING_AMOUNT_ERROR    Payment processing failure due to invalid amount.
     * PROCESSING_DUPLICATE    Payment processing failure due to duplicatation. The transaction is a duplicate of a previously submitted transaction.
     * PROCESSING_GENERAL_DECLINE    Payment processing failure due to an unspecified error returned. Retry the transaction and if problem continues contact the issuing bank
     * PROCESSING_TIMEOUT    Payment processing failure due to timeout.
     * REFUND_FAILED    Payment processing failure. Refund failed.
     * RESTRICTED_CARD    Payment processing failure due to restricted card.
     * SYSTEM_TECHNICAL_ERROR    Payment processing failure due to system technical error.
     * THE_ISSUER_IS_UNAVAILABLE_OR_OFFLINE    Payment processing failure because the issuer is unavailable or offline.
     * THREE_D_SECURE_FAILURE    Payment processing failure due to 3D secure failure.
     *
     */

    /**
     * @var array Magento-BlueSnap card type mapper
     */
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
     * Trigger place order requests based on the customer status (returning/new)
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param null|int $bsShopperId
     * @return array
     */
    public function placeOrder(Varien_Object $payment, $bsShopperId = null, $action = null)
    {
        $paymentaction = strtolower(Mage::getStoreConfig('payment/cse/payment_action'));
        if ($paymentaction != 'authorize_capture') {
            //Auth minimal amount
            if ($bsShopperId) {
                //Here we Check if order is comming from admin or frontend
                $order = $payment->getOrder();
                $api = Mage::getModel('bluesnap/api_saved');
                $response = $api->createAuthOrder($payment, $bsShopperId);
            } else {
                // new customer
                $response = $this->placeAuthOrder($payment);
            }

        } else {
            //Here we Check if order is comming from admin or frontend
            $order = $payment->getOrder();
            if ($order->getRemoteIp()) {
                //frontend order
                if ($bsShopperId) {
                    // returning shopper
                    // create order with saved card
                    $response = $this->createOrder($payment, $bsShopperId);
                } else {
                    // new customer
                    $response = $this->placeBatchOrder($payment);
                }
            } else {
                if ($bsShopperId) {
                    // returning shopper
                    // create order with saved card
                    $response = $this->createOrder($payment, $bsShopperId);
                } else {
                    // new customer
                    $response = $this->placeBatchOrder($payment);
                }
            }
        }
        // process API response
        return $response;
    }

    /**
     * Create shopper and place order with auth only
     * services/2/transactions/
     * @link http://docs.bluesnap.com/api/services/shopping-context/create-shopping-context
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Varien_Simplexml_Element
     * @throws Mage_Core_Exception
     */

    public function placeAuthOrder(Varien_Object $payment, $amount = null)
    {
        $order = $payment->getOrder();
        $billing = $order->getBillingAddress();

        if (!Mage::helper('bluesnap')->isStateSupported($billing->getRegionCode())) {
            throw new Bluesnap_Payment_Model_Api_Exception(Mage::helper('core')->__('State is not supported'));
        }

        if (!Mage::helper('bluesnap')->isCountrySupported($billing->getCountry())) {
            throw new Bluesnap_Payment_Model_Api_Exception(Mage::helper('core')->__('Country is not supported'));
        }

        $sum = $this->_currencyModel()->prepareOrderAmount($order);

        // we are setting atuhorize amount to $1 per BSNPMG-181

        //we need to convert to USD if different
        if ($sum['currency'] != 'USD') {
            $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();

            $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();

            $price = 1;
            $currencyApi = Mage::getModel('bluesnap/api_currency');
            $currResponse = $currencyApi->convert('USD', $baseCurrencyCode, $price);
            $sum['amount'] = $currResponse;
        } else $sum['amount'] = 1;

        $data = array(
            'web-info' => array(
                'ip' => $order->getRemoteIp() ? $order->getRemoteIp() : $_SERVER['REMOTE_ADDR'],
                'remote-host' => $order->getRemoteIp() ? $order->getRemoteIp() : $_SERVER['REMOTE_ADDR'],
                'user-agent' => Mage::helper('core/http')->getHttpUserAgent(),
            ),
            'shopper-details' => array(
                'shopper' => array(
                    'shopper-info' => array(
                        'shopper-contact-info' => array(
                            'first-name' => $billing->getFirstname(),
                            'last-name' => $billing->getLastname(),
                            'email' => $order->getCustomerEmail(),
                            'state' => strtoupper($billing->getCountry()) == 'US' || strtoupper($billing->getCountry()) == 'CA' ? $billing->getRegionCode() : '',
                            'country' => strtoupper($billing->getCountry()),
                        ),
                        'shopper-currency' => $sum['currency'],
                        'payment-info' => array(
                            'credit-cards-info' => array(
                                'credit-card-info' => array(
                                    'billing-contact-info' => array(
                                        'first-name' => $billing->getFirstname(),
                                        'last-name' => $billing->getLastname(),
                                        'state' => strtoupper($billing->getCountry()) == 'US' || strtoupper($billing->getCountry()) == 'CA' ? $billing->getRegionCode() : '',
                                        'country' => strtoupper($billing->getCountry()),
                                    ),
                                    'credit-card' => array(
                                        'encrypted-card-number' => Mage::app()->getRequest()->getParam('encryptedCreditCard'),
                                        // 'card-type' => (isset($this->cardTypes[$payment->getCcType()]) ? $this->cardTypes[$payment->getCcType()] : ''),
                                        'expiration-month' => $payment->getCcExpMonth(),
                                        'expiration-year' => $payment->getCcExpYear(),
                                        'encrypted-security-code' => Mage::app()->getRequest()->getParam('encryptedCvv'),
                                    ),
                                ),
                            ),
                        ),
                        'store-id' => Mage::helper('bluesnap')->getBluesnapStoreId(),
                    ),
                    'fraud-info' => array(
                        'fraud-session-id' => Mage::getSingleton('checkout/session')->getSessionId(),
                    ),
                ),
            ),
            'order-details' => array(
                'order' => array(
                    'ordering-shopper' => array(
                        'seller-shopper-id' => '',
                        'web-info' => array(
                            'ip' => $order->getRemoteIp() ? $order->getRemoteIp() : $_SERVER['REMOTE_ADDR'],
                            'remote-host' => $order->getRemoteIp() ? $order->getRemoteIp() : $_SERVER['REMOTE_ADDR'],
                            'user-agent' => Mage::helper('core/http')->getHttpUserAgent(),
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
                    ),
                ),
            ),
        );
		// Zend_Debug::dump($data); exit;
        $xml = new SimpleXMLElement('<shopping-context xmlns="' . $this->_getXmlNs() . '"/>');
        $request = Mage::helper('bluesnap')->arrayToXml($data, $xml)->asXML();

        // remove sensitive data from logs
        $logData = $data;
        $logData['shopper-details']['shopper']['shopper-info']['payment-info']['credit-cards-info']['credit-card-info']['credit-card']['encrypted-card-number'] = '****';
        $logData['shopper-details']['shopper']['shopper-info']['payment-info']['credit-cards-info']['credit-card-info']['credit-card']['encrypted-security-code'] = '****';

        $logRequestXml = new SimpleXMLElement('<shopping-context xmlns="' . $this->_getXmlNs() . '"/>');
        $logRequestXml = Mage::helper('bluesnap')->arrayToXml($logData, $logRequestXml)->asXML();


        // send request
        $url = $this->getServiceUrl('shopping-context');
        $response = $this->_request($url, $request, 0, false, true);
       
        $responseArr = $this->parseHeaders($response);
       
        try {
            //   $response = $this->parseCreateResponse($response, null, $order);
        } catch (Exception $e) {

            throw new Bluesnap_Payment_Model_Api_Exception(Mage::helper('core')->__('Something went wrong during placing your order. Please contact support.'));

        }

        if ($responseArr['http_code'] == '201') $this->getLogger()->logSuccess($logRequestXml, $response, 0, "Authorize success", "Authorize", $order->getIncrementId(), $url);
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
     * /services/2/orders
     * @link http://docs.bluesnap.com/api/services/orders/create-order
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param int $bsShopperId
     * @return Varien_Simplexml_Element
     * @throws Mage_Core_Exception
     */
    public function createOrder(Varien_Object $payment, $bsShopperId, $amount = null)
    {
        $order = $payment->getOrder();
        $total = $this->_currencyModel()->prepareOrderAmount($order);
        if (is_numeric($amount)) {
            $total['amount'] = $amount;
        }

        // if card was not used before — save card for shopper
      		$validCC = false;
        if ($payment->getCcNumber()) {
            $response = $this->addCardToShopper($payment, $total, $bsShopperId);

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
        } else {   //BSNPMG-34 no need to update shopper call when new card added

            $this->updateShopper($payment, $total, $bsShopperId);
        }
		
		if($validCC) {
			$type = (string) $validCC->descend("card-type");
			
			$payment->setCcType($type)->save();
		} else {
			$type = (isset($this->cardTypes[$payment->getCcType()]) ? $this->cardTypes[$payment->getCcType()] : '');
		}

        $data = array(
            'ordering-shopper' => array(
                'charged-currency' => $total['currency'],

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
                'charged-currency' => $total['currency'],
                'cart-item' => array(
                    'sku' => array(
                        'sku-id' => Mage::helper('bluesnap')->getBluesnapBuynowOrderContractId(),
                        'sku-charge-price' => array(
                            'charge-type' => 'initial',
                            'amount' => $total['amount'],
                            'currency' => $total['currency'],
                            'charged-currency' => $total['currency'],

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
                'amount' => $total['amount'],
                'currency' => $total['currency'],
                'charged-currency' => $total['currency'],

            )
        );
        $xml = new SimpleXMLElement('<order xmlns="' . $this->_getXmlNs() . '"/>');

        $request = Mage::helper('bluesnap')->arrayToXml($data, $xml)->asXML();
        // send request
        $url = $this->getServiceUrl('orders');
        $responseXml = $this->_request($url, $request);
        $response = $this->_parseXmlResponse($responseXml);

        try {

            $response = $this->parseCreateResponse($response, $bsShopperId, $order,$url);
        } catch (Exception $e) {
            throw new Bluesnap_Payment_Model_Api_Exception(Mage::helper('core')->__('Something went wrong during placing your order. Please contact support.'));
        }

        $this->getLogger()->logSuccess($this->_requestXml, $this->_responseXml, 0, "createOrder success", "createOrder", $order->getIncrementId(), $url);

        return $response;

    }

    /**
     * Update shopper's record and add new card in payment info
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param int $bsShopperId
     * @throws Mage_Core_Exception
     */
    public function addCardToShopper(Varien_Object $payment, $total, $bsShopperId)
    {
        $order = $payment->getOrder();
        $billing = $order->getBillingAddress();
        if (!Mage::helper('bluesnap')->isStateSupported($billing->getRegionCode())) {
            throw new Bluesnap_Payment_Model_Api_Exception(Mage::helper('core')->__('State is not supported'));
        }

        if (!Mage::helper('bluesnap')->isCountrySupported($billing->getCountry())) {
            throw new Bluesnap_Payment_Model_Api_Exception(Mage::helper('core')->__('Country is not supported'));
        }

        $data = array(
            'web-info' => array(
                'ip' => $order->getRemoteIp() ? $order->getRemoteIp() : $_SERVER['REMOTE_ADDR'],
            ),
            //BSNPMG-163 - fraud support
            //http://docs.bluesnap.com/api/services/shoppers/create-shopper
            'fraud-info' => array(
                'fraud-session-id' => Mage::getSingleton('checkout/session')->getSessionId(),
            ),
            'shopper-info' => array(
                'store-id' => Mage::helper('bluesnap')->getBluesnapStoreId(),
                'shopper-currency' => $total['currency'],
                'charged-currency' => $total['currency'],

                'payment-info' => array(
                    'credit-cards-info' => array(
                        'credit-card-info' => array(
                            'billing-contact-info' => array(
                                'first-name' => $billing->getFirstname(),
                                'last-name' => $billing->getLastname(),
                                'state' => strtoupper($billing->getCountry()) == 'US' || strtoupper($billing->getCountry()) == 'CA' ? $billing->getRegionCode() : '',
                                'country' => strtoupper($billing->getCountry()),
                            ),
                            'credit-card' => array(
                                'encrypted-card-number' => Mage::app()->getRequest()->getParam('encryptedCreditCard'),
                                'expiration-month' => $payment->getCcExpMonth(),
                                'expiration-year' => $payment->getCcExpYear(),
                                'encrypted-security-code' => Mage::app()->getRequest()->getParam('encryptedCvv'),
                                'card-last-four-digits' => $payment->getCcLast4(),
                            ),
                        ),
                    )
                ),
            ),
        );
        $xml = new SimpleXMLElement('<shopper xmlns="' . $this->_getXmlNs() . '"/>');
        $requestXml = Mage::helper('bluesnap')->arrayToXml($data, $xml)->asXML();

        $url = $this->getServiceUrl('shoppers/' . $bsShopperId);
        $responseXml = $this->_request($url, $requestXml, self::HTTP_METHOD_PUT);
        
        //http://docs.bluesnap.com/api/services/shoppers/update-shopper
        //If successful, the response HTTP status code is 204 No Content.
        //Otherwise, Errors will be returned in a messages resource.
        //@todo: verify response code (204 for update shopper)

        //remove sensitive data from logs
        $logData = $data;
        $logData['shopper-info']['payment-info']['credit-cards-info']['credit-card-info']['credit-card']['encrypted-card-number'] = '****';
        $logData['shopper-info']['payment-info']['credit-cards-info']['credit-card-info']['credit-card']['encrypted-security-code'] = '****';

        $logRequestXml = new SimpleXMLElement('<shopper xmlns="' . $this->_getXmlNs() . '"/>');
        $logRequestXml = Mage::helper('bluesnap')->arrayToXml($logData, $logRequestXml)->asXML();

        if ($this->_curlInfo['http_code'] !== 204 || $responseXml) {

            $this->getLogger()->logError($logRequestXml, $this->_responseXml, 0, 'addCardToShopper error', "addCardToShopper", $order->getIncrementId(), $url);
            $response = $this->_parseXmlResponse($responseXml);

            $e = new Bluesnap_Payment_Model_Api_Exception((string)$response->message->description, (string)$response->message->code);
            Mage::logException($e);

            Mage::throwException(mage::helper('bluesnap')->__('Can not authorize your card'));
          
        }
        else {
        	//log success add card to shopper API call
    		$this->getLogger()->logSuccess($logRequestXml, $this->_responseXml, 0, "addCardToShopper success", "addCardToShopper", $order->getIncrementId(), $url);

        }

        //need to unset the value in session for new card to be avaialable
        $this->getSession()->unsetData('bs_shopper');
		
		
		
		
        // and save it again in the session
        $response = $this->retrieveShopper($bsShopperId);

        // unregister cards from registery
        Mage::unregister('bs_shopper_cards');
		
        return $response;

    }

    /**
     * Get shopper information
     * http://docs.bluesnap.com/api/services/shoppers/retrieve-shopper
     * @param int $bsShopperId
     * @return Varien_Simplexml_Element
     * @throws Mage_Core_Exception
     */
    public function retrieveShopper($bsShopperId , $action=NULL, $forceCall = false)
    {
       

        if (!$this->getSession()->getData('bs_shopper')  || $action=="invoice" || $forceCall) {

            // send request
            $url = $this->getServiceUrl('shoppers/' . $bsShopperId);
            $responseXml = $this->_request($url, '', self::HTTP_METHOD_GET);
            $response = $this->_parseXmlResponse($responseXml);
            //checks:
            //1. response should be xml object
            //2. shopper info should be present
            //3. shopper id should be present in shopper info

            if ($response) {
                if ((string)$response->{'shopper-info'}->{'shopper-id'} == $bsShopperId) {
                    $this->getLogger()->logSuccess($this->_requestXml, $this->_responseXml, 0, "retrieveShopper $bsShopperId", "retrieveShopper", "", $url);
                } else {
                    $this->getLogger()->logError($this->_requestXml, $this->_responseXml, 0, "retrieveShopper error: " . $bsShopperId, "retrieveShopper", "", $url);
                }
            } else {
                $e = new Bluesnap_Payment_Model_Api_Exception("Retrieve Shopper Error. Wrong Xml. " . $bsShopperId);
                Mage::logException($e);
                $this->getLogger()->logError($this->_requestXml, $this->_responseXml, 0, "retrieveShopper error: " . $bsShopperId, "retrieveShopper", "", $url);
                throw $e;
            }


            $this->getSession()->setData('bs_shopper', $responseXml);
        }

        $responseXml = $this->getSession()->getData('bs_shopper');
        $response = $this->_parseXmlResponse($responseXml);

        return $response;
    }

    /**
     * @param Varien_Object $payment
     * http://docs.bluesnap.com/api/services/shoppers/create-shopper
     * /services/2/shoppers
     * http://docs.bluesnap.com/api/services/shoppers/update-shopper
     * /services/2/shoppers/{shopper-id}
     * @param array $sum
     * @param int $shopperId
     * @throws Mage_Core_Exception
     */
    public function updateShopper($payment, $sum, $shopperId)
    {
        $order = $payment->getOrder();
        $billing = $order->getBillingAddress();

        if (!Mage::helper('bluesnap')->isStateSupported($billing->getRegionCode())) {
            throw new Bluesnap_Payment_Model_Api_Exception(Mage::helper('core')->__('State is not supported'));
        }

        if (!Mage::helper('bluesnap')->isCountrySupported($billing->getCountry())) {
            throw new Bluesnap_Payment_Model_Api_Exception(Mage::helper('core')->__('Country is not supported'));
        }

        $data = array(
            'web-info' => array(
                'ip' => $order->getRemoteIp() ? $order->getRemoteIp() : $_SERVER['REMOTE_ADDR'],
            ),
            
            //http://docs.bluesnap.com/api/services/shoppers/create-shopper
            'fraud-info' => array(
                'fraud-session-id' => Mage::getSingleton('checkout/session')->getSessionId(),
            ),
            'shopper-info' => array(
                'store-id' => Mage::helper('bluesnap')->getBluesnapStoreId(),
                'shopper-currency' => $sum['currency'],
                'charged-currency' => $sum['currency'],
                'shopper-contact-info' => array(
                    'first-name' => $billing->getFirstname(),
                    'last-name' => $billing->getLastname(),
                    'email' => $order->getCustomerEmail(),
                    'state' => strtoupper($billing->getCountry()) == 'US' || strtoupper($billing->getCountry()) == 'CA' ? $billing->getRegionCode() : '',
                    'country' => strtoupper($billing->getCountry()),
                ),
                'payment-info' => array(
                    'credit-cards-info' => array(
                        'credit-card-info' => array(
                            'billing-contact-info' => array(
                                'first-name' => $billing->getFirstname(),
                                'last-name' => $billing->getLastname(),
                                'state' => strtoupper($billing->getCountry()) == 'US' || strtoupper($billing->getCountry()) == 'CA' ? $billing->getRegionCode() : '',
                                'country' => strtoupper($billing->getCountry()),
                            ),
                            'credit-card' => array(
                                'encrypted-card-number' => Mage::app()->getRequest()->getParam('encryptedCreditCard'),
                                'expiration-month' => $payment->getCcExpMonth(),
                                'expiration-year' => $payment->getCcExpYear(),
                                'encrypted-security-code' => Mage::app()->getRequest()->getParam('encryptedCvv'),
                                'card-last-four-digits' => $payment->getCcLast4(),
                            ),
                        ),
                    )
                ),
            ),
        );


        $xml = new SimpleXMLElement('<shopper xmlns="' . $this->_getXmlNs() . '"/>');
        $request = Mage::helper('bluesnap')->arrayToXml($data, $xml)->asXML();

        // send request
        $url = $this->getServiceUrl('shoppers/' . $shopperId);
        $this->_request($url, $request, self::HTTP_METHOD_PUT);

        //http://docs.bluesnap.com/api/services/shoppers/update-shopper
        if ($this->_curlInfo['http_code'] != 204 || $this->_responseXml) {
            $response = $this->_parseXmlResponse($this->_responseXml);

            $this->getLogger()->logError($this->_requestXml, $this->_responseXml, 0, 'updateShopper error', "updateShopper", $order->getIncrementId(), $url);
            $e = new Bluesnap_Payment_Model_Api_Exception("Can not update shopper");
            Mage::logException($e);

            Mage::throwException(mage::helper('bluesnap')->__('Can not authorize your card'));
            //throw $e;
        }

        $this->getLogger()->logSuccess($this->_requestXml, $this->_responseXml, 0, "updateShopper success", "updateShopper", $order->getIncrementId(), $url);

    }

    /**
     * Process API response after placing order
     * @param Varien_Simplexml_Element $responseXml
     * @param null|int $bsShopperId
     * @return array
     * @throws Mage_Core_Exception
     */
    protected function parseCreateResponse($responseXml, $bsShopperId = null, $order = null, $url= " ")
    {
        $result = array('shopperId' => null, 'invoiceId' => null);
        //  $error = Mage::helper('bluesnap')->__('Unfortunately the transaction cannot be processed at this time due to an unspecified error, please try a different card or try again at a later time');

        if ($responseXml->message) {
            // $error = Mage::helper('bluesnap')->__('Unfortunately the transaction cannot be processed at this time due to an unspecified error, please try a different card or try again at a later time');
            $error = (string)$responseXml->message->description;

            $e = new Bluesnap_Payment_Model_Api_Exception($error, (int)$responseXml->message->code);
            Mage::logException($e);
            $this->getLogger()->logError($this->_requestXml, $this->_responseXml, (int)$responseXml->message->code, $error, "createOrder", $order->getIncrementId(),$url);

            throw $e;

        }
        // $response= new Varien_Simplexml_Element($responseXml->AsXml());

        $shopperXml = $bsShopperId ?
            $responseXml->xpath('//ns1:ordering-shopper') :
            $responseXml->xpath('//ns1:shopper-info');

        if ($shopperXml) {
            $result['shopperId'] = (string)$shopperXml[0]->{'shopper-id'};
        }

        if ($invoiceXml = $responseXml->xpath('//ns1:invoice')) {
            $result['invoiceId'] = (string)$invoiceXml[0]->{'invoice-id'};
        }

        $result['status'] = Bluesnap_Payment_Helper_Config::BLUESNAP_DEFAULT_INVOICE_STATUS;
        if ($statusXml = $responseXml->xpath('//ns1:financial-transaction')) {
            foreach ($statusXml as $transaction) {
                $result['status'] = (string)$transaction->status;
                $result['transaction'] = $transaction->asArray();
            }
        }


        return $result;
    }

    /**
     * Create shopper and place order
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Varien_Simplexml_Element
     * @throws Mage_Core_Exception
     */
    public function placeBatchOrder(Varien_Object $payment, $amount = null)
    {
        $order = $payment->getOrder();
        $billing = $order->getBillingAddress();
		
        if (!Mage::helper('bluesnap')->isStateSupported($billing->getRegionCode())) {
            throw new Bluesnap_Payment_Model_Api_Exception(Mage::helper('core')->__('State is not supported'));
        }

        if (!Mage::helper('bluesnap')->isCountrySupported($billing->getCountry())) {
            throw new Bluesnap_Payment_Model_Api_Exception(Mage::helper('core')->__("Unfortunately this order can't be completed due to a restriction regarding the selected billing country"));
        }

        $sum = $this->_currencyModel()->prepareOrderAmount($order);
        if ($amount) {
            $sum['amount'] = $amount;
        }

        //http://docs.bluesnap.com/api/services/orders/batch-create-shopper-and-order
		
        $data = array(
            'shopper' => array(
                'web-info' => array(
                    'ip' => $order->getRemoteIp() ? $order->getRemoteIp() : $_SERVER['REMOTE_ADDR'],
                ),
                //BSNPMG-163 - fraud support
                //http://docs.bluesnap.com/api/services/shoppers/create-shopper
              
                'shopper-info' => array(
                    'store-id' => Mage::helper('bluesnap')->getBluesnapStoreId(),
                    'shopper-currency' => $sum['currency'],
                    'charged-currency' => $sum['currency'],
                    'locale' => substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2),
                    'permitted-future-charges' => 'true',
                    'shopper-contact-info' => array(
                        'first-name' => $billing->getFirstname(),
                        'last-name' => $billing->getLastname(),
                        'email' => $order->getCustomerEmail(),
                        
                        'state' => strtoupper($billing->getCountry()) == 'US' || strtoupper($billing->getCountry()) == 'CA' ? $billing->getRegionCode() : '',
                        'country' => strtoupper($billing->getCountry()),
                    ),
                    'payment-info' => array(
                        'credit-cards-info' => array(
                            'credit-card-info' => array(
                                'billing-contact-info' => array(
                                    'first-name' => $billing->getFirstname(),
                                    'last-name' => $billing->getLastname(),
                                    'state' => strtoupper($billing->getCountry()) == 'US' || strtoupper($billing->getCountry()) == 'CA' ? $billing->getRegionCode() : '',
                                    'country' => strtoupper($billing->getCountry()),
                                ),
                                'credit-card' => array(
                                    'encrypted-card-number' => Mage::app()->getRequest()->getParam('encryptedCreditCard'),
                                    'expiration-month' => $payment->getCcExpMonth(),
                                    'expiration-year' => $payment->getCcExpYear(),
                                    'encrypted-security-code' => Mage::app()->getRequest()->getParam('encryptedCvv'),
                                ),
                            ),
                        )
                    ),
                ),
            ),
            'order' => array(
                'ordering-shopper' => array(
                    'seller-shopper-id' => '',
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
                    'charged-currency' => $sum['currency'],
                ),
            ),
        );

        $xml = new SimpleXMLElement('<batch-order xmlns="' . $this->_getXmlNs() . '"/>');
        $request = Mage::helper('bluesnap')->arrayToXml($data, $xml)->asXML();
		
        //BSNPMG-86 - remove sensitive data from logs
        $logData = $data;
        $logData['shopper']['shopper-info']['payment-info']['credit-cards-info']['credit-card-info']['credit-card']['encrypted-card-number'] = '****';
        $logData['shopper']['shopper-info']['payment-info']['credit-cards-info']['credit-card-info']['credit-card']['encrypted-security-code'] = '****';

        $logRequestXml = new SimpleXMLElement('<batch-order xmlns="' . $this->_getXmlNs() . '"/>');
        $logRequestXml = Mage::helper('bluesnap')->arrayToXml($logData, $logRequestXml)->asXML();


        // send request
        $url = $this->getServiceUrl('batch/order-placement');

        $responseXml = $this->_request($url, $request);
		
        $response = $this->_parseXmlResponse($responseXml);
		$ccType = (string) $response->descend("shopper/shopper-info/payment-info/credit-cards-info/credit-card-info/credit-card/card-type");
			$payment->setCcType($ccType)->save();
        //hide sensitive data
        $this->_requestXml = $logRequestXml;
        $logResponse = $this->_parseXmlResponse($responseXml);
        
        $logResponse->shopper->{'shopper-info'}->password = '****';

        $this->_responseXml = $logResponse->asXml();


        try {

            $response = $this->parseCreateResponse($response, null, $order);

        } catch (Exception $e) {

            $error_code = $logResponse->message->{'code'};
            $error_message = Mage::helper('bluesnap')->getErroMessage($error_code);
            throw new Bluesnap_Payment_Model_Api_Exception($error_message);

        }


        $this->getLogger()->logSuccess($this->_requestXml, $this->_responseXml, 0, "placeBatchOrder success", "placeBatchOrder", $order->getIncrementId(), $url);

        return $response;

    }

    /**
     * Get shopper and order info for shopper context
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return array
     */
    public function retriveShoppingContext(Varien_Object $payment, $amount = null)
    {
        $order = $payment->getOrder();
        if ($bsShopingContext = $order->getBluesnapReferenceNumber()) {
            // send request
            $url = $this->getServiceUrl('shopping-context/' . $bsShopingContext);
            $responseXml = $this->_request($url, '', self::HTTP_METHOD_GET);
            $response = $this->_parseXmlResponse($responseXml);
        }

        if ($response) {
            if ((string)$response->{'order-details'}->{'order'}->{'order-id'} == $bsShopingContext) {
                $this->getLogger()->logSuccess($this->_requestXml, $this->_responseXml, 0, "retrieve Shopper Context $bsShopingContext", "retrieve Shopper Context", "", $url);
            } else {
                $this->getLogger()->logError($this->_requestXml, $this->_responseXml, 0, "retrieve Shopper error: " . $bsShopingContext, "retrieve Shopper Context", "", $url);
            }
        } else {
            $e = new Bluesnap_Payment_Model_Api_Exception("Retrieve Shopper Context Error. Wrong Xml. " . $bsShopingContext);
            Mage::logException($e);
            $this->getLogger()->logError($this->_requestXml, $this->_responseXml, 0, "retrieve ShopperContext error: " . $bsShopperId, "retrieve Shopper", "", $url);
            throw $e;
        }
        return $response;
    }

    public function retriveShoppingContextById(Varien_Object $payment, $bsShopingContext)
    {
        if ($bsShopingContext) {
            // send request
            $url = $this->getServiceUrl('shopping-context/' . $bsShopingContext);
            $responseXml = $this->_request($url, '', self::HTTP_METHOD_GET);
            $response = $this->_parseXmlResponse($responseXml);
        } else {
            $response = false;
            $url = null;
            $responseXml = null;
        }

        try {
            if ($response && ((string)$response->{'order-details'}->{'order'}->{'order-id'} == $bsShopingContext)) {
                $this->getLogger()->logSuccess($this->_requestXml, $this->_responseXml, 0, "retrive Shopping Context $bsShopingContext", "retrive Shopping Context", "", $url);
            } else {
                throw new Bluesnap_Payment_Model_Api_Exception("Retrieve Shopper Context Error. Wrong Xml. " . $bsShopingContext);
            }
        } catch (Bluesnap_Payment_Model_Api_Exception $e) {
            Mage::logException($e);
            $this->getLogger()->logError($url, $responseXml, 0, "retrive Shopping Context error: " . $bsShopingContext, "retrieve Shopper", "", $url);
            throw $e;
        }


        return $response;
    }

    /**
     * Get list of saved card for shopper
     * @param int $bsShopperId
     * @return array
     */
    public function getSavedCards($bsShopperId)
    {

        if (!Mage::registry('bs_shopper_cards')) {
           try
			{
			      $xml = $this->retrieveShopper($bsShopperId);
            	  $cards_info = $xml->xpath('//ns1:credit-card');

            	  $cards = array();
            	  foreach ($cards_info as $info) {
               		 $last = (string)$info->{'card-last-four-digits'};
               		 $type = (string)$info->{'card-type'};
              		 $cards[$last] = $type;
           		  }

            	  Mage::register('bs_shopper_cards', $cards);
			}
		   catch(Exception $e)
			{
    		   // throw new Exception("cound not find cards");
			}
         
        }


        return Mage::registry('bs_shopper_cards');
    }

    /**
     * @param int $invoiceId
     * @return string
     * @throws Mage_Core_Exception
     */
    public function getInvoiceStatus($invoiceId)
    {
        if (!$invoiceId) {
            throw new Exception("invoice id required!");
        }
        $url = $this->getServiceUrl('orders/resolve?invoiceId=' . $invoiceId);

        $response = $this->_request($url, '', self::HTTP_METHOD_GET);
        $responseXml = $this->_parseXmlResponse($response);

        $data = $responseXml->xpath('//ns1:financial-transaction');
        $status = Bluesnap_Payment_Helper_Config::BLUESNAP_DEFAULT_INVOICE_STATUS;

        foreach ($data as $transaction) {
            $status = (string)$transaction->status;
        }
        return $status;
    }

    /**
     * @param Varien_Object $payment
     * http://docs.bluesnap.com/api/services/shoppers/create-shopper
     * /services/2/shoppers
     * http://docs.bluesnap.com/api/services/shoppers/update-shopper
     * /services/2/shoppers/{shopper-id}
     * @param array $sum
     * @param int $shopperId
     * @throws Mage_Core_Exception
     */
    public function updateShopperWithNoCC($payment, $sum, $shopperId)
    {
        $order = $payment->getOrder();
        $billing = $order->getBillingAddress();

        if (!Mage::helper('bluesnap')->isStateSupported($billing->getRegionCode())) {
            throw new Bluesnap_Payment_Model_Api_Exception(Mage::helper('core')->__('State is not supported'));
        }

        if (!Mage::helper('bluesnap')->isCountrySupported($billing->getCountry())) {
            throw new Bluesnap_Payment_Model_Api_Exception(Mage::helper('core')->__('Country is not supported'));
        }

        $data = array(
            'web-info' => array(
                'ip' => $order->getRemoteIp() ? $order->getRemoteIp() : $_SERVER['REMOTE_ADDR'],
            ),
            'fraud-info' => array(
                'fraud-session-id' => Mage::getSingleton('checkout/session')->getSessionId(),
            ),
            'shopper-info' => array(
                'store-id' => Mage::helper('bluesnap')->getBluesnapStoreId(),
                'shopper-currency' => $sum['currency'],
                'shopper-contact-info' => array(
                    'first-name' => $billing->getFirstname(),
                    'last-name' => $billing->getLastname(),
                    'email' => $order->getCustomerEmail(),
                    'state' => strtoupper($billing->getCountry()) == 'US' || strtoupper($billing->getCountry()) == 'CA' ? $billing->getRegionCode() : '',
                    'country' => strtoupper($billing->getCountry()),
                ),
            ),
        );


        $xml = new SimpleXMLElement('<shopper xmlns="' . $this->_getXmlNs() . '"/>');
        $request = Mage::helper('bluesnap')->arrayToXml($data, $xml)->asXML();

        // send request
        $url = $this->getServiceUrl('shoppers/' . $shopperId);
        $response = $this->_request($url, $request, self::HTTP_METHOD_PUT);

        //http://docs.bluesnap.com/api/services/shoppers/update-shopper
        if ($this->_curlInfo['http_code'] != 204) {

            $this->_parseXmlResponse($this->_responseXml);
            $this->getLogger()->logError($this->_requestXml, $response, 0, 'updateShopper without CC error', "'updateShopper without CC", $order->getIncrementId(), $url);
            $e = new Bluesnap_Payment_Model_Api_Exception("Can not update shopper");
            Mage::logException($e);
            Mage::throwException(mage::helper('bluesnap')->__('Can not update shopper'));
        }

        $this->getLogger()->logSuccess($this->_requestXml, $response, 0, "updateShopper success", "updateShopper without CC", $order->getIncrementId(), $url);

    }
}
