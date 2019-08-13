<?php

/**
 * BuyNow Checkout Helper
 * Class Bluesnap_Payment_Helper_Checkout
 * refactored version
 */
class Bluesnap_Payment_Helper_Checkout extends Mage_Core_Helper_Abstract
{

    const PARAM_VALUE_YES = 'Y';
    const PARAM_VALUE_NO = 'N';

    /**
     * @var bool Whether module is in sandbox mode
     */
    protected $_isSandboxMode;

    /**
     * Constructor
     * Set Sandbox Mode flag
     */
    public function __construct()
    {
        $this->_isSandboxMode = Mage::helper('bluesnap')->isSandBoxMode();
    }

    /**
     * Get template for "Place Order" button
     * @param string $name template name
     * @param string $blockName button block name
     * @return string
     */
    public function getReviewButtonTemplate($name, $blockName)
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if ($quote) {
            $payment = $quote->getPayment();
            $bluesnapMethodCode = Mage::getSingleton('bluesnap/payment_buynow')->getCode();
            if ($payment && ($payment->getMethod() == $bluesnapMethodCode)) {
                return $name;
            }
        }

        if ($block = Mage::getSingleton('core/layout')->getBlock($blockName)) {
            return $block->getTemplate();
        }

        return '';
    }


    /**
     * Update Order Billing Address using IPN request data
     * @param Mage_Sales_Model_Order_Address $address ,
     * @param array $params
     */
    public function updateOrderBillingAddress(Mage_Sales_Model_Order_Address $address, array $params)
    {
        if (isset($params['email'])) {
            $address->setEmail($params['email']);
        }
        /*
        if (isset($params['companyName']))
        $address->setCompany($params['companyName']);
        */
        if (isset($params['firstName'])) {
            $address->setFirstname($params['firstName']);
        }
        if (isset($params['lastName'])) {
            $address->setLastname($params['lastName']);
        }

        if (isset($params['country'])) {
            $address->setCountryId(
                $this->_countryCodeBluesnapToIso2($params['country']));
        }
        if (isset($params['state'])) {
            $region = Mage::getModel('directory/region')->loadByCode($params['state'], $address->getCountryId());
            if (!$region->isObjectNew()) {
                $address->setRegionId($region->getId());
                $address->setRegion($region->getName());
            }
        }
    }

    /**
     * Update Order Shipping Address using IPN request data
     * @param Mage_Sales_Model_Order_Address $address ,
     * @param array $params
     */
    public function updateOrderShippingAddress(Mage_Sales_Model_Order_Address $address, array $params)
    {
        if (isset($params['shippingFirstName'])) {
            $address->setFirstname($params['shippingFirstName']);
        }
        if (isset($params['shippingLastName'])) {
            $address->setLastname($params['shippingLastName']);
        }
        $street = array();
        if (isset($params['shippingAddress1'])) {
            $street[] = $params['shippingAddress1'];
        }
        if (isset($params['shippingAddress2'])) {
            $street[] = $params['shippingAddress2'];
        }
        if (!empty($street)) {
            $address->setStreet($street);
        }
        if (isset($params['shippingCity'])) {
            $address->setCity($params['shippingCity']);
        }
        if (isset($params['shippingZipCode'])) {
            $address->setPostcode($params['shippingZipCode']);
        }
        if (isset($params['shippingCountry'])) {
            $address->setCountryId($this->_countryCodeBluesnapToIso2($params['shippingCountry']));
        }
        if (isset($params['shippingState'])) {
            $region = Mage::getModel('directory/region')->loadByCode(
                $params['shippingState'], $address->getCountryId()
            );
            if (!$region->isObjectNew()) {
                $address->setRegionId($region->getId());
                $address->setRegion($region->getName());
            }
        }
        if (isset($params['shippingWorkPhone'])) {
            $address->setTelephone($params['shippingWorkPhone']);
        }
        if (isset($params['shippingFaxNumber'])) {
            $address->setFax($params['shippingFaxNumber']);
        }
    }


}
