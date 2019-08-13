<?php

/**
 * Client-side encription Payment Method
 * refactored version
 */
class Bluesnap_Payment_Model_Payment_Cse extends Bluesnap_Payment_Model_Payment_Abstract
{
    const ACTION_AUTHORIZE = 'authorize';
    const ACTION_AUTHORIZE_CAPTURE = 'authorize_capture';
    /**
     * @var string
     */
    protected $_code = 'cse';


    /**
     * Availability options
     */
    /**
     * itzik. try to make offline invoice available
     *
     * @var mixed
     */
    // protected $_isGateway = false;
    /**
     * Form block type
     */
    protected $_formBlockType = 'bluesnap/payment_form_cse';
    protected $_infoBlockType = 'bluesnap/payment_info_cse';

    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canUseForMultishipping = false;
    protected $_canSaveCc = false;
    protected $_allowManualCapture = 1;

    /**
     * Check whether payment method can be used
     * @param Mage_Sales_Model_Quote|null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        $helper = Mage::helper('bluesnap');
        /* @var $helper Bluesnap_Payment_Helper_Data */

        if (!$helper->isCseActive()) {
            return false;
        }


        if ($quote) {
            $isApplicableMasks = array(
                // self::CHECK_USE_CHECKOUT,
                // self::CHECK_USE_FOR_COUNTRY,
                // self::CHECK_ORDER_TOTAL_MIN_MAX
            );
            foreach ($isApplicableMasks as $mask) {
                if (!$this->isApplicableToQuote($quote, $mask)) {
                    return false;
                }
            }

            // if returning shoopper and has saved cards
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $customerData = Mage::getSingleton('customer/session')->getCustomer();
                if ($bsShopperId = $customerData->getBsAccountId()) {
                    //$api = Mage::getModel('bluesnap/request_saved');
                    /* @var $api Bluesnap_Payment_Model_Request_Saved */
                    $this->getApi('saved')->getSavedCards($bsShopperId);
                }
            }
        }

        return parent::isAvailable($quote);
    }


    /**
     * Get payment method description
     * @return string
     */
    public function getComment()
    {
        return $this->getConfig()->getCseComment();
    }

    /**
     * Get merchant's public key
     * @return string
     */
    public function getPublicKey()
    {
        return $this->getConfig()->getPublicKey();
    }

    /**
     * Retrieve payment method title
     * @return string
     */
    public function getTitle()
    {
        return $this->getConfig()->getCseTitle();
    }

    /**
     * To check billing country is allowed for the payment method
     * @param array $country
     * @return bool
     */
    public function canUseForCountry_($country)
    {
        //for specific country, the flag will set up as 1
        if (Mage::getStoreConfig('bluesnap/cse/allowspecific') == 1) {
            $availableCountries = explode(',', Mage::getStoreConfig('bluesnap/cse/specificcountry'));
            if (!in_array($country, $availableCountries)) {
                return false;
            }

        }
        return true;
    }

    /**
     * Check whether payment method is applicable to quote
     * @param Mage_Sales_Model_Quote $quote
     * @param int|null $checksBitMask
     * @return bool
     */
    public function isApplicableToQuote_($quote, $checksBitMask)
    {
        if ($checksBitMask & self::CHECK_ORDER_TOTAL_MIN_MAX) {
            $total = $quote->getBaseGrandTotal();
            $minTotal = Mage::getStoreConfig('bluesnap/cse/min_order_total');
            $maxTotal = Mage::getStoreConfig('bluesnap/cse/max_order_total');
            if (!empty($minTotal) && $total < $minTotal || !empty($maxTotal) && $total > $maxTotal) {
                return false;
            }
        } else {
            return parent::isApplicableToQuote($quote, $checksBitMask);
        }
        return true;
    }

    /**
     * Validate payment method information object
     * @return $this
     */
    public function assignData($data)
    {
        parent::assignData($data);
        $savedCard = Mage::app()->getRequest()->getParam('bluesnap_card_saved', 'no');

        $isSaved = !$isCse = ($savedCard == 'no');

        $info = $this->getInfoInstance();
        $info->setIsSaved($isSaved);

        if ($isSaved) {
            $this->assignSaved($info, $savedCard);
        } else {
            $this->assignCse($info);
        }

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Quote_Payment|Mage_Sales_Model_Order_Payment $info
     * @param string $bluesnapCardSaved
     * @throws Mage_Core_Exception
     */
    public function assignSaved($info, $bluesnapCardSaved)
    {
        $saved = Mage::helper('core')->decrypt($bluesnapCardSaved);

        $savedArray = explode('/', $saved);

        if (count($savedArray) == 2) {
            $info = $this->getInfoInstance();

            $info->setCcLast4($savedArray[0]);
            $info->setCcLast($savedArray[0]);
            $info->setCcType($savedArray[1]);
            $info->setAdditionalInformation('is_saved', true);
        } else {
            Mage::throwException(Mage::helper('bluesnap')->__('Credit card is not accepted'));
        }
    }

    /**
     * @param Mage_Sales_Model_Quote_Payment|Mage_Sales_Model_Order_Payment $info
     */
    public function assignCse($info)
    {
        $encryptedCreditCard = Mage::app()->getRequest()->getParam('encryptedCreditCard', null);
        $encryptedCvv = Mage::app()->getRequest()->getParam('encryptedCvv', null);
        $cseCcLast = Mage::app()->getRequest()->getParam('cse_cc_last', null);

        // add leading zeros oif needed
        if (strlen($cseCcLast) < 4) {
            $cseCcLast = str_pad($cseCcLast, 4, '0', STR_PAD_LEFT);
        }

        $info->setCcNumber($encryptedCreditCard);
        $info->setCcCid($encryptedCvv);
        $info->setCcLast($cseCcLast);
        $info->setCcLast4($cseCcLast);
        $info->setAdditionalInformation('is_saved', false);

        return $info;
    }

    /**
     * no validation can be applied to encrypted data
     *
     */
    function validate()
    {
        return $this;
    }

    public function authorize(Varien_Object $payment, $amount)
    {
        if ($amount <= 0) {
            Mage::throwException(Mage::helper('bluesnap')->__('Invalid amount for authorization.'));
        }
		
        $apiType = $payment->getAdditionalInformation('is_saved') ? 'saved' : 'cse';
		
		
		
        $api = $this->getApi($apiType);
        /* @var $api Bluesnap_Payment_Model_Payment_Cse */

        $customerData = Mage::getSingleton('customer/session')->getCustomer();
        //for tests
        if (!$customerData->getId())
            $customerData = Mage::getModel('customer/customer')->load($payment->getOrder()->getCustomerId());

        $bsShopperId = $customerData->getBsAccountId();

        if ($result = $api->placeOrder($payment, $bsShopperId)) {

            if ($result['http_code'] == '201') {
                $order = $payment->getOrder();

                if (!$order->getRemoteIp()) $payment->unsAdditionalInformation();

                $payment->setAdditionalInformation('transaction_type', 'shopping-context');
				
                if ($order->getRemoteIp()) $payment->setAdditionalInformation('is_saved', true);
                //getting the tranasaction id from location info
                $loc = explode("/", $result['location']);
                $transaction_id = $loc[count($loc) - 1];

                $order->setBluesnapReferenceNumber($transaction_id);

                //adding BS shopper to customer:
                if ($order->getCustomerId()) {
                    $api = $this->getApi('cse');
                    $result = $api->retriveShoppingContextById($payment, $transaction_id);

                    $bsShopperId = (string)$result->{'order-details'}->{'order'}->{'ordering-shopper'}->{'shopper-id'};

                    $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
                    $customer->setBsAccountId($bsShopperId);
                    $customer->save();
                }

                $order->addStatusHistoryComment(
                    sprintf("BlueSnap Credit/Debit card authorized transaction. Order ID: \"%s\".", $transaction_id));

            } else {
                $this->processError($result,'authorize');
            }

        } else {
            $message = 'Unfortunately the transaction cannot be processed at this time due to an unspecified error, please try a different card or try again at a later time';

            Mage::log($message, Zend_Log::ERR);
            $this->getLogger()->logError("", "", 0, $message, "cse::authorize", $payment->getOrder()->getIncrementId());

            Mage::throwException(Mage::helper('bluesnap')->__($message));

        }
    }

    /**
     * Format error message for the API call
     * @param $result
     * @throws Mage_Core_Exception
     */
    public function processError($result, $type=NULL)
    {
        $error = Mage::helper('bluesnap')->__('Unfortunately the transaction cannot be processed at this time due to an unspecified error, please try a different card or try again at a later time');
        if (isset($result['message']['description'])) {
            $error .= "\r\n" . $result['message']['description'];

            if (isset($result['message']['invalid-property']['message-value'])) {
                $error .= "\r\n" . ' (' . $result['message']['invalid-property']['message-value'] . ')';
            }
        }
        if($type=='authorize') $this->getLogger()->logError("", "", "", $error, "cse::authorize");
        else $this->getLogger()->logError("", "", "", $result['message']['description'], "cse::capture");
        Mage::throwException($error);
    }

    /**
     * Capture payment
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @throws Exception
     * @throws Mage_Core_Exception
     * @return void
     */
    public function capture(Varien_Object $payment, $amount)
    {
    	//Mage::log($payment->getData(),NULL,'bs.log');
        $apiType = $payment->getAdditionalInformation('is_saved') ? 'saved' : 'cse';
        $type = $payment->getAdditionalInformation('transaction_type') == 'shopping-context' ? 'auth' : 'capture';

        // fixing issue with admin order
        $order = $payment->getOrder();
      
        $api = $this->getApi($apiType);
        /* @var $api Bluesnap_Payment_Model_Payment_Cse */

        if (Mage::app()->getStore()->isAdmin() && Mage::app()->getRequest()->getParam('invoice_id')) {
            return $this->captureInvoice($api, $payment);
        }

        $customerData = Mage::getSingleton('customer/session')->getCustomer();
        //for tests
        if (!$customerData->getId())
            $customerData = Mage::getModel('customer/customer')->load($payment->getOrder()->getCustomerId());
			
			$bsShopperId = $customerData->getBsAccountId();
			
        if ($type != 'auth') {
            if ($result = $api->placeOrder($payment, $bsShopperId)) {
                if (!empty($result['invoiceId'])) {
                    $bsTransactionId = $result['invoiceId'];
                  
                  // Mage::log('error101',NULL,'errorTest.log');
                  
                    $status = $result['status'];
					 if (!$order->getRemoteIp()) $payment->unsAdditionalInformation();

                    /* @var $order Mage_Sales_Model_Order */

                    if (!empty($result['shopperId'])) {
                        $bsShopperId = $result['shopperId'];

                        // update customer with BS shopper Id
                        if ($order->getCustomerId()) {
                            $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
                            $customer->setBsAccountId($result['shopperId']);
                            $customer->save();
                        }
                    }


                    $payment->setAdditionalInformation('transactionAmount', $result['transaction']['amount']);
                    $payment->setAdditionalInformation('transactionCurrency', $result['transaction']['currency']);
                    $payment->setAdditionalInformation('softDescriptor', $result['transaction']['soft-descriptor']);
                    $payment->setAdditionalInformation('transactionId', $bsTransactionId);
                    //Add column with trans id
                    $order->setBluesnapReferenceNumber($bsTransactionId);


                    //Add cc info
                    $payment->setCcExpMonth($result['transaction']['credit-card']['expiration-month']);
                    $payment->setCcExpYear($result['transaction']['credit-card']['expiration-year']);


                    if ($status == Bluesnap_Payment_Helper_Config::BLUESNAP_APPROVED_INVOICE_STATUS) {
                        //   $invoice->setIsTransactionClosed(true);
                        //   $invoice->register();

                        $payment->setTransactionId($bsTransactionId);
                        $payment->setIsTransactionClosed(true);

                        $order->setBluesnapTotalInvoiced($result['transaction']['amount']);
                        $order->setBluesnapTotalPaid($result['transaction']['amount']);


                        //really captured amount
                        $order->addStatusHistoryComment(
                            sprintf("BlueSnap Credit/Debit card captured amount of %s%s online. Transaction ID: \"%s\".",
                                $result['transaction']['amount'], $result['transaction']['currency'], $bsTransactionId));


                    } elseif (($status == Bluesnap_Payment_Helper_Config::BLUESNAP_DEFAULT_INVOICE_STATUS)
                        || ($status == Bluesnap_Payment_Helper_Config::BLUESNAP_REVIEW_INVOICE_STATUS)
                    ) {


                        $payment->setTransactionId($bsTransactionId);
                        $payment->setIsTransactionClosed(false);
                        $payment->setIsTransactionPending(true);

                        $order->setBluesnapTotalInvoiced($result['transaction']['amount']);

                        $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, true);// ->save();
                    } else {
                        $message = Mage::helper('bluesnap')->__('Unknown transaction status: "%s"', $status);

                        Mage::log($message, Zend_log::ERR);
                        $this->getLogger()->logError("", "", "", $message, "cse::capture", $order->getIncrementId());
                        Mage::throwException($message);

                    }

                } else {
                    $this->processError($result);
                }
            } else {
                $message = 'Unfortunately the transaction cannot be processed at this time due to an unspecified error, please try a different card or try again at a later time';
				
                Mage::log($message, Zend_Log::ERR);
                $this->getLogger()->logError("", "", 0, $message, "cse::capture", $payment->getOrder()->getIncrementId());

                Mage::throwException(Mage::helper('bluesnap')->__($message));

            }
        } else {
            //first we retrieve shopper info from Shopping Context
            $result = $api->retriveShoppingContext($payment, $bsShopperId);

            //we test that we get back the shpper Id
            if ($bsShopperId = (string)$result->{'order-details'}->{'order'}->{'ordering-shopper'}->{'shopper-id'}) {

                //we place a order using the saved cc methods
                $api = $this->getApi('saved');
                 if ($result = $api->placeOrder($payment, $bsShopperId, 'invoice')) {
                    if (!empty($result['invoiceId'])) {
                        $bsTransactionId = $result['invoiceId'];
                        $status = $result['status'];

                        $order = $payment->getOrder();
						
						 if ($order->getRemoteIp()) $payment->unsAdditionalInformation();

                        $payment->setAdditionalInformation('transactionAmount', $result['transaction']['amount']);
                        $payment->setAdditionalInformation('transactionCurrency', $result['transaction']['currency']);
                        $payment->setAdditionalInformation('softDescriptor', $result['transaction']['soft-descriptor']);
                        $payment->setAdditionalInformation('transactionId', $bsTransactionId);

                        $order->setBluesnapReferenceNumber($bsTransactionId);

                        $payment->setCcExpMonth($result['transaction']['credit-card']['expiration-month']);
                        $payment->setCcExpYear($result['transaction']['credit-card']['expiration-year']);

                        if ($status == Bluesnap_Payment_Helper_Config::BLUESNAP_APPROVED_INVOICE_STATUS) {
                            //   $invoice->setIsTransactionClosed(true);
                            //   $invoice->register();

                            $payment->setTransactionId($bsTransactionId);
                            $payment->setIsTransactionClosed(true);

                            $order->setBluesnapTotalInvoiced($result['transaction']['amount']);
                            $order->setBluesnapTotalPaid($result['transaction']['amount']);


                            //really captured amount
                            $order->addStatusHistoryComment(
                                sprintf("BlueSnap Credit/Debit card captured amount of %s%s online. Transaction ID: \"%s\".",
                                    $result['transaction']['amount'], $result['transaction']['currency'], $bsTransactionId));


                        } elseif (($status == Bluesnap_Payment_Helper_Config::BLUESNAP_DEFAULT_INVOICE_STATUS)
                            || ($status == Bluesnap_Payment_Helper_Config::BLUESNAP_REVIEW_INVOICE_STATUS)
                        ) {

                            $payment->setTransactionId($bsTransactionId);
                            $payment->setIsTransactionClosed(false);
                            $payment->setIsTransactionPending(true);


                            $order->setBluesnapTotalInvoiced($result['transaction']['amount']);


                            $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, true);// ->save();
                        } else {
                            $message = Mage::helper('bluesnap')->__('Unknown transaction status: "%s"', $status);

                            Mage::log($message, Zend_log::ERR);
                            $this->getLogger()->logError("", "", "", $message, "cse::capture", $order->getIncrementId());
                            Mage::throwException($message);

                        }

                    } else {
                        $this->processError($result);
                    }
                } else {
                    $message = 'Unfortunately the transaction cannot be processed at this time due to an unspecified error, please try a different card or try again at a later time';

                    Mage::log($message, Zend_Log::ERR);
                    $this->getLogger()->logError("", "", 0, $message, "cse::capture", $payment->getOrder()->getIncrementId());

                    Mage::throwException(Mage::helper('bluesnap')->__($message));

                }

            }
        }
    }

    /**
     * @param $api
     * @param $payment
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function captureInvoice($api, $payment)
    {
        if (!$this->_allowManualCapture)
            Mage::throwException(Mage::helper('bluesnap')->__('This payment can not be captured manually'));
        $order = $payment->getOrder();
        $bsTransactionId = $order->getBluesnapReferenceNumber();
        $status = $api->getInvoiceStatus($bsTransactionId);

        //need to check that the payment is not in review status
        if ($status == 'Pending Vendor Review')
            Mage::throwException(Mage::helper('bluesnap')->__('This payment can not be captured'));

        return $this;

    }


}
