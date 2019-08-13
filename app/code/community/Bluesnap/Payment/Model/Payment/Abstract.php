<?php

class Bluesnap_Payment_Model_Payment_Abstract extends Mage_Payment_Model_Method_Cc
{

    public function processCreditmemo($creditmemo, $payment)
    {
        $creditmemo->setTransactionId($payment->getLastTransId());
        $creditmemo->setBluesnapCurrencyCode($payment->getOrder()->getBluesnapCurrencyCode());
        $creditmemo->setBluesnapGrandTotal($payment->getBluesnapTotalRefunded());

        return $this;
    }

    /**
     * Refund specified amount for payment
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $baseAmountToRefund
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function refund(Varien_Object $payment, $baseAmountToRefund)
    {
        //don't refund here if we're in ipn refund
        if (Mage::registry('ipn_transaction_refund'))
            return $this;

        $order = $payment->getOrder();

        try {

            $isFull = false;
            if ($baseAmountToRefund == $payment->getOrder()->getBaseGrandTotal()) {
                $isFull = true;
            }


            /* @var $currencyModel Bluesnap_Payment_Model_Currencies */

            //saved bluesnap 'historical' amount. itzik
            //    $amount=$payment->getOrder()->getBluesnapCurrencyAmount();

            //using original amount
            //calculate amount as percent of grand total
            $chargedAmount = $order->getBluesnapTotalPaid();
            if (!(double)$chargedAmount) {
                $chargedAmount = $order->getBluesnapGrandTotal();
            }
            if (!(double)$chargedAmount) {
                throw new Exception("Bluesnap Total Paid/Grand Total is null!");
            }
            $bluesnapRefundAmount = $baseAmountToRefund / $order->getBaseGrandTotal() * $chargedAmount;

            $bluesnapRefundAmount = round($bluesnapRefundAmount, 2);
            //set real amount in payment
            $payment->setBluesnapTotalRefunded($bluesnapRefundAmount);

            if (!$this->getConfig()->getConfigData('general/is_dry_run'))
                $this->getApi('refund')->process($payment->getLastTransId(), $bluesnapRefundAmount, $isFull);

            $order = $payment->getOrder();
            //BSNPMG-128
            $order->setBluesnapTotalRefunded($bluesnapRefundAmount);
            $order->addStatusHistoryComment("Amount refunded online: $bluesnapRefundAmount")
                //  ->setIsCustomerNotified(1)
                ->save();

            try {
                //  $this->_emailHelper()->sendPaymentRefundedEmail($order);
            } catch (Exception $e) {
                Mage::logException($e);
                $this->getLogger()->logError("", "", 0, $e->getMessage(), "cse::refund", $payment->getOrder()->getIncrementId());

            }


        } catch (Bluesnap_Payment_Exception $e) {
            Mage::logException($e);
            $this->getLogger()->logError("", "", 0, $e->getMessage(), "cse::refund", $payment->getOrder()->getIncrementId());
            $this->_emailHelper()->sendPaymentRefundFailedEmail($order, $e->getMessage());
            throw ($e);

        }

        return $this;
    }

    /**
     * @return Bluesnap_Payment_Model_Api_Config
     *
     */
    function getConfig()
    {
        return Mage::getSingleton('Bluesnap_Payment_Model_Api_Config');
    }

    /**
     * @param $type
     * @return Mage_Core_Model_Abstract
     * @throws Exception
     */
    function getApi($type)
    {
        switch ($type) {
            case "cse":
                return Mage::getSingleton('Bluesnap_Payment_Model_Api_Cse');
            case "saved":
                return Mage::getSingleton('Bluesnap_Payment_Model_Api_Saved');
            case "refund":
                return Mage::getSingleton('Bluesnap_Payment_Model_Api_Refund');
            default:
                throw new Exception("wrong api type:" . $type);

        }
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
     * @return Bluesnap_Payment_Helper_Email
     *
     */
    protected function _emailHelper()
    {
        return Mage::getSingleton('Bluesnap_Payment_Helper_Email');
    }
}