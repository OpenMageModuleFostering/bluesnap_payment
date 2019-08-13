<?php

/**
 * BuyNow Payment Method
 */
class Bluesnap_Payment_Model_Payment_Buynow extends Bluesnap_Payment_Model_Payment_Abstract
{
    /**
     * @var string
     */
    protected $_code = 'buynow';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canUseForMultishipping = false;

    protected $_canSaveCc = false;

    //   protected $_allowManualCapture=1;

    protected $_formBlockType = 'bluesnap/payment_form_buynow';
    protected $_infoBlockType = 'bluesnap/payment_info_buynow';


    public function assignData($data)
    {
        return $this;
    }

    public function validate()
    {
        return $this;
    }

    /**
     * Check whether payment method can be used
     * @param Mage_Sales_Model_Quote|null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        $helper = Mage::helper('bluesnap');
        /* @var $helper Bluesnap_Payment_Helper_Data */

        if (!$helper->isBuynowActive()) {
            return false;
        }

        if ($quote) {
            $isApplicableMasks = array(
                self::CHECK_USE_CHECKOUT,
                self::CHECK_USE_FOR_COUNTRY,
                self::CHECK_ORDER_TOTAL_MIN_MAX
            );
            foreach ($isApplicableMasks as $mask) {
                if (!$this->isApplicableToQuote($quote, $mask)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get payment method description
     * @return string
     */
    public function getComment()
    {
        return Mage::helper('bluesnap')->getBuynowComment();
    }

    /**
     * Get BuyNow Checkout Page URL
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('bluesnap/checkout/redirect');
    }


    /**
     * To check billing country is allowed for the payment method
     * @param array $country
     * @return bool
     */
    public function canUseForCountry_($country)
    {
        //for specific country, the flag will set up as 1
        if (Mage::getStoreConfig('bluesnap/buynow/allowspecific') == 1) {
            $availableCountries = explode(',', Mage::getStoreConfig('bluesnap/buynow/specificcountry'));
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
            $minTotal = Mage::getStoreConfig('bluesnap/buynow/min_order_total');
            $maxTotal = Mage::getStoreConfig('bluesnap/buynow/max_order_total');
            if (!empty($minTotal) && $total < $minTotal || !empty($maxTotal) && $total > $maxTotal) {
                return false;
            }
        } else {
            return parent::isApplicableToQuote($quote, $checksBitMask);
        }
        return true;
    }

    /**
     * Retrieve payment method title
     * @return string
     */
    public function getTitle()
    {
        return Mage::helper('bluesnap')->getBuynowTitle();
    }

    /**
     * Capture payment
     *
     * not really captures anything, only creates invoice in status pending
     * because this is iframe
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @throws Exception
     * @throws Mage_Core_Exception
     * @return void
     */
    public function capture(Varien_Object $payment, $amount)
    {
        if (Mage::app()->getStore()->isAdmin()) {
            //disallow capture from admin
            if (!$this->getConfig()->getConfigData('general/is_dry_run'))
                Mage::throwException(Mage::helper('bluesnap')->__('This payment can not be captured manually'));


        }

        $order = $payment->getOrder();
        /* @var $order Mage_Sales_Model_Order */

        $payment->setIsTransactionClosed(false);
        $payment->setIsTransactionPending(true);
        //BSNPMG-80 Email issues
        //  Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_ENABLED, 0);
        $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, true); // ->save();
        return null;
    }
}
