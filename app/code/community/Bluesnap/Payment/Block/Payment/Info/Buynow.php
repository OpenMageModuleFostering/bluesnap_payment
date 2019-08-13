<?php

/**
 * Payment Information block for BlueSnap BuyNow Payment Method
 * Class Bluesnap_Payment_Block_Payment_Info_Buynow
 */
class Bluesnap_Payment_Block_Payment_Info_Buynow extends Mage_Payment_Block_Info_Cc
{
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $info = $this->getInfo();
        $transport = new Varien_Object(array(Mage::helper('payment')->__('Name on the Card') => $info->getCcOwner(),));
        $transport = parent::_prepareSpecificInformation($transport);
        //  if (!$this->getIsSecureMode()) {
        $transport->addData(array(
            Mage::helper('payment')->__('Expiration Date') => $this->_formatCardDate(
                $info->getCcExpYear(), $this->getCcExpMonth()
            ),
            // Mage::helper('payment')->__('Credit Card Type') => $info->getCcType(),
            Mage::helper('payment')->__('Payment Type') => $info->getAdditionalInformation('paymentType'),
            Mage::helper('payment')->__('Credit Card Type') => $info->getAdditionalInformation('creditCardType'),

            Mage::helper('payment')->__('Credit Card Last 4 Digits') => $info->getCcLast4(),
            Mage::helper('payment')->__('Reference Number') => $info->getAdditionalInformation('referenceNumber'),
            Mage::helper('payment')->__('Invoice Amount') => $info->getAdditionalInformation('invoiceAmount'),
            Mage::helper('payment')->__('Invoice Amount USD') => $info->getAdditionalInformation('invoiceAmountUSD'),
            Mage::helper('payment')->__('Invoice Charge Currency') => $info->getAdditionalInformation('invoiceChargeCurrency'),
            Mage::helper('payment')->__('Invoice Charge Amount') => $info->getAdditionalInformation('invoiceChargeAmount'),
            //     Mage::helper('payment')->__('Transaction Currency Rate') => "({$info->getOrder()->getBaseCurrencyCode()} => {$info->getOrder()->getBluesnapCurrencyCode()}) {$info->getOrder()->getBluesnapCurrencyRate()}",

            Mage::helper('payment')->__('Transaction Date') => $info->getAdditionalInformation('transactionDate'),


        ));

        if ($info->getOrder()) {
            $transport->addData(
                array(
                    Mage::helper('payment')->__('Transaction Currency Rate') => "({$info->getOrder()->getBaseCurrencyCode()} => {$info->getOrder()->getBluesnapCurrencyCode()}) " . number_format($info->getOrder()->getBluesnapCurrencyRate(), 4),
                )
            );

        }

        foreach ($transport->getData() as $key => $value) {
            if (!$value) {
                $transport->unsetData($key);
            }
        }
        if ($transport->getData('Expiration Date') == '00/0')
            $transport->unsetData('Expiration Date');
        if ($transport->getData('Expiration Date') == '00/')
            $transport->unsetData('Expiration Date');

        // }
        return $transport;
    }


}