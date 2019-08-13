<?php

/**
 * transactional emails helper
 */
class Bluesnap_Payment_Helper_Email extends Mage_Checkout_Helper_Data
{
    /**
     * defined in config.xml
     */
    const PAYMENT_REFUND_FAILED_TEMPLATE = 'transaction_refund_failed';
    const PAYMENT_REFUNDED_TEMPLATE = 'transaction_refunded';
    const PAYMENT_CHARGEBACK_TEMPLATE = 'transaction_chargeback';
    const PAYMENT_DECLINED_TEMPLATE = 'transaction_declined';

    public function sendPaymentDeclinedEmail($checkout, $message = '', $checkoutType = 'onepage')
    {
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        $mailTemplate = Mage::getModel('core/email_template');
        /* @var $mailTemplate Mage_Core_Model_Email_Template */

        $template = self::PAYMENT_DECLINED_TEMPLATE;

        $copyTo = $this->_getEmails('checkout/payment_failed/copy_to', $checkout->getStoreId());
        $copyMethod = Mage::getStoreConfig('checkout/payment_failed/copy_method', $checkout->getStoreId());
        if ($copyTo && $copyMethod == 'bcc') {
            $mailTemplate->addBcc($copyTo);
        }

        $_reciever = Mage::getStoreConfig('checkout/payment_failed/reciever', $checkout->getStoreId());
        $sendTo = array(
            array(
                'email' => Mage::getStoreConfig('trans_email/ident_' . $_reciever . '/email', $checkout->getStoreId()),
                'name' => Mage::getStoreConfig('trans_email/ident_' . $_reciever . '/name', $checkout->getStoreId())
            )
        );


        $sendTo[] = array(
            // array(
            'email' => $checkout->getBillingAddress()->getEmail(),
            'name' => $checkout->getCustomerName()
            //  )
        );


        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $sendTo[] = array(
                    'email' => $email,
                    'name' => null
                );
            }
        }
        $shippingMethod = '';
        if ($shippingInfo = $checkout->getShippingMethod()) {
            $data = explode('_', $shippingInfo);
            $shippingMethod = $data[0];
        }

        $paymentMethod = '';
        if ($paymentInfo = $checkout->getPayment()) {
            $paymentMethod = $paymentInfo->getMethod();
        }

        $items = '';
        foreach ($checkout->getAllVisibleItems() as $_item) {
            /* @var $_item Mage_Sales_Model_Quote_Item */
            $items .= $_item->getProduct()->getName() . '  x ' . (int)$_item->getQtyOrdered() . '  '
                //   . $checkout->getOrderCurrencyCode() . ' '
                // . $_item->getRowTotal() . "\n";
                . ' ' . $checkout->formatPrice($_item->getRowTotal()) . "\n";
        }

        //Mage_Sales_Model_Quote line 303
        //$total = $checkout->getStoreCurrencyCode() . ' ' . $checkout->getGrandTotal();

        $total = $checkout->formatPrice($checkout->getGrandTotal());

        //$baseTotal = $checkout->getStoreCurrencyCode() . ' ' . $checkout->getBaseTotalRefunded();
        $baseTotal = $checkout->formatBasePrice($checkout->getBaseGrandTotal());

        $bluesnapTotal = $checkout->formatBluesnapPrice($checkout->getBluesnapGrandTotal());


        foreach ($sendTo as $recipient) {
            $mailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $checkout->getStoreId()))
                ->sendTransactional(
                    $template,
                    Mage::getStoreConfig('checkout/payment_failed/identity', $checkout->getStoreId()),
                    $recipient['email'],
                    $recipient['name'],
                    array(
                        'reason' => $message,
                        'checkoutType' => $checkoutType,
                        'dateAndTime' => Mage::app()->getLocale()->date(),
                        'customer' => $checkout->getCustomerFirstname() . ' ' . $checkout->getCustomerLastname(),
                        'customerEmail' => $checkout->getCustomerEmail(),
                        'billingAddress' => $checkout->getBillingAddress(),
                        'shippingAddress' => $checkout->getShippingAddress(),
                        'shippingMethod' => Mage::getStoreConfig('carriers/' . $shippingMethod . '/title'),
                        'paymentMethod' => Mage::getStoreConfig('payment/' . $paymentMethod . '/title'),
                        'items' => nl2br($items),

                        'total' => $total,
                        'base_total' => $baseTotal,
                        'bluesnap_total' => $bluesnapTotal,
                        'order_id' => $checkout->getIncrementId(),
                    )
                );
        }

        $translate->setTranslateInline(true);

        return $this;
    }


    public function sendPaymentRefundedEmail($checkout, $message = null, $checkoutType = 'onepage')
    {
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        $mailTemplate = Mage::getModel('core/email_template');
        /* @var $mailTemplate Mage_Core_Model_Email_Template */

        // $template = Mage::getStoreConfig('checkout/payment_failed/template');
        $template = self::PAYMENT_REFUNDED_TEMPLATE;

        $copyTo = $this->_getEmails('checkout/payment_failed/copy_to', Mage::app()->getStore()->getId());
        $copyMethod = Mage::getStoreConfig('checkout/payment_failed/copy_method');
        if ($copyTo && $copyMethod == 'bcc') {
            $mailTemplate->addBcc($copyTo);
        }

        $_reciever = Mage::getStoreConfig('checkout/payment_failed/reciever');
        $sendTo = array(
            array(
                'email' => Mage::getStoreConfig('trans_email/ident_' . $_reciever . '/email'),
                'name' => Mage::getStoreConfig('trans_email/ident_' . $_reciever . '/name')
            )
        );

        $sendTo[] = array(
            // array(
            'email' => $checkout->getBillingAddress()->getEmail(),
            'name' => $checkout->getCustomerName()
            //  )
        );

        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $sendTo[] = array(
                    'email' => $email,
                    'name' => null
                );
            }
        }
        $shippingMethod = '';
        if ($shippingInfo = $checkout->getShippingMethod()) {
            $data = explode('_', $shippingInfo);
            $shippingMethod = $data[0];
        }

        $paymentMethod = '';
        if ($paymentInfo = $checkout->getPayment()) {
            $paymentMethod = $paymentInfo->getMethod();
        }

        $items = '';
        foreach ($checkout->getAllVisibleItems() as $_item) {
            /* @var $_item Mage_Sales_Model_Quote_Item */
            $items .= $_item->getProduct()->getName() . '  x ' . (int)$_item->getQtyOrdered() . '  '
                //  . $checkout->getOrderCurrencyCode() . ' '
                //. $_item->getRowTotal() . "\n";
                . ' ' . $checkout->formatPrice($_item->getRowTotal()) . "\n";
        }
        $total = $checkout->formatPrice($checkout->getTotalRefunded());
        $baseTotal = $checkout->formatBasePrice($checkout->getBaseTotalRefunded());
        $bluesnapTotal = $checkout->formatBluesnapPrice($checkout->getBluesnapTotalRefunded());


        foreach ($sendTo as $recipient) {
            $mailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $checkout->getStoreId()))
                ->sendTransactional(
                    $template,
                    Mage::getStoreConfig('checkout/payment_failed/identity', $checkout->getStoreId()),
                    $recipient['email'],
                    $recipient['name'],
                    array(
                        'reason' => $message,
                        'checkoutType' => $checkoutType,
                        'dateAndTime' => Mage::app()->getLocale()->date(),
                        'customer' => $checkout->getCustomerFirstname() . ' ' . $checkout->getCustomerLastname(),
                        'customerEmail' => $checkout->getCustomerEmail(),
                        'billingAddress' => $checkout->getBillingAddress(),
                        'shippingAddress' => $checkout->getShippingAddress(),
                        'shippingMethod' => Mage::getStoreConfig('carriers/' . $shippingMethod . '/title'),
                        'paymentMethod' => Mage::getStoreConfig('payment/' . $paymentMethod . '/title'),
                        'items' => nl2br($items),
                        'total' => $total,
                        'base_total' => $baseTotal,
                        'bluesnap_total' => $bluesnapTotal,
                        'order_id' => $checkout->getIncrementId(),
                        'reversal_ref_num' => $checkout->getPayment()->getAdditionalInformation('reversal_ref_num'),
                        'reversal_date' => $checkout->getPayment()->getAdditionalInformation('reversal_date'),
                        'reversal_full' => $checkout->getPayment()->getAdditionalInformation('reversal_full'),
                        'reversal_amount' => $checkout->getPayment()->getAdditionalInformation('reversal_amount'),
                        'reversal_currency' => $checkout->getPayment()->getAdditionalInformation('reversal_currency'),

                    )
                );
        }

        $translate->setTranslateInline(true);

        return $this;
    }


    /**
     * BSNPMG-92
     * send notification to merchant when refund failed
     *
     * @param mixed $checkout
     * @param mixed $message
     * @param mixed $checkoutType
     * @return Bluesnap_Payment_Helper_Email
     */
    public function sendPaymentRefundFailedEmail($checkout, $message = null, $checkoutType = 'onepage')
    {
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        $mailTemplate = Mage::getModel('core/email_template');
        /* @var $mailTemplate Mage_Core_Model_Email_Template */

        // $template = Mage::getStoreConfig('checkout/payment_failed/template');
        $template = self::PAYMENT_REFUND_FAILED_TEMPLATE;

        $copyTo = $this->_getEmails('checkout/payment_failed/copy_to', Mage::app()->getStore()->getId());
        $copyMethod = Mage::getStoreConfig('checkout/payment_failed/copy_method');
        if ($copyTo && $copyMethod == 'bcc') {
            $mailTemplate->addBcc($copyTo);
        }

        $_reciever = Mage::getStoreConfig('checkout/payment_failed/reciever');
        $sendTo = array(
            array(
                'email' => Mage::getStoreConfig('trans_email/ident_' . $_reciever . '/email'),
                'name' => Mage::getStoreConfig('trans_email/ident_' . $_reciever . '/name')
            )
        );

        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $sendTo[] = array(
                    'email' => $email,
                    'name' => null
                );
            }
        }
        $shippingMethod = '';
        if ($shippingInfo = $checkout->getShippingMethod()) {
            $data = explode('_', $shippingInfo);
            $shippingMethod = $data[0];
        }

        $paymentMethod = '';
        if ($paymentInfo = $checkout->getPayment()) {
            $paymentMethod = $paymentInfo->getMethod();
        }

        $items = '';
        foreach ($checkout->getAllVisibleItems() as $_item) {
            /* @var $_item Mage_Sales_Model_Quote_Item */
            $items .= $_item->getProduct()->getName() . '  x ' . (int)$_item->getQtyOrdered() . '  '
                //  . $checkout->getOrderCurrencyCode() . ' '
                //. $_item->getRowTotal() . "\n";
                . ' ' . $checkout->formatPrice($_item->getRowTotal()) . "\n";
        }
        //wrong currency for total BSNPMG-124
        //   $total = $checkout->getStoreCurrencyCode() . ' ' . $checkout->getGrandTotal();
        //$total = $checkout->getOrderCurrencyCode() . ' ' . $checkout->getTotalRefunded();
        $total = $checkout->formatPrice($checkout->getTotalRefunded());

        //$baseTotal = $checkout->getStoreCurrencyCode() . ' ' . $checkout->getBaseTotalRefunded();
        $baseTotal = $checkout->formatBasePrice($checkout->getBaseTotalRefunded());

        $bluesnapTotal = $checkout->formatBluesnapPrice($checkout->getBluesnapTotalRefunded());


        foreach ($sendTo as $recipient) {
            $mailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $checkout->getStoreId()))
                ->sendTransactional(
                    $template,
                    Mage::getStoreConfig('checkout/payment_failed/identity', $checkout->getStoreId()),
                    $recipient['email'],
                    $recipient['name'],
                    array(
                        'reason' => $message,
                        'checkoutType' => $checkoutType,
                        'dateAndTime' => Mage::app()->getLocale()->date(),
                        'customer' => $checkout->getCustomerFirstname() . ' ' . $checkout->getCustomerLastname(),
                        'customerEmail' => $checkout->getCustomerEmail(),
                        'billingAddress' => $checkout->getBillingAddress(),
                        'shippingAddress' => $checkout->getShippingAddress(),
                        'shippingMethod' => Mage::getStoreConfig('carriers/' . $shippingMethod . '/title'),
                        'paymentMethod' => Mage::getStoreConfig('payment/' . $paymentMethod . '/title'),
                        'items' => nl2br($items),
                        'total' => $total,
                        'base_total' => $baseTotal,
                        'bluesnap_total' => $bluesnapTotal,
                        'order_id' => $checkout->getIncrementId(),
                    )
                );
        }

        $translate->setTranslateInline(true);

        return $this;
    }


    public function sendPaymentChargebackEmail($checkout, $message, $checkoutType = 'onepage')
    {
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        $mailTemplate = Mage::getModel('core/email_template');
        /* @var $mailTemplate Mage_Core_Model_Email_Template */

        // $template = Mage::getStoreConfig('checkout/payment_failed/template');
        $template = self::PAYMENT_CHARGEBACK_TEMPLATE;

        $copyTo = $this->_getEmails('checkout/payment_failed/copy_to', Mage::app()->getStore()->getId());
        $copyMethod = Mage::getStoreConfig('checkout/payment_failed/copy_method');
        if ($copyTo && $copyMethod == 'bcc') {
            $mailTemplate->addBcc($copyTo);
        }

        $_reciever = Mage::getStoreConfig('checkout/payment_failed/reciever');
        $sendTo = array(
            array(
                'email' => Mage::getStoreConfig('trans_email/ident_' . $_reciever . '/email'),
                'name' => Mage::getStoreConfig('trans_email/ident_' . $_reciever . '/name')
            )
        );

        $sendTo[] = array(
            // array(
            'email' => $checkout->getBillingAddress()->getEmail(),
            'name' => $checkout->getCustomerName()
            //  )
        );

        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $sendTo[] = array(
                    'email' => $email,
                    'name' => null
                );
            }
        }
        $shippingMethod = '';
        if ($shippingInfo = $checkout->getShippingMethod()) {
            $data = explode('_', $shippingInfo);
            $shippingMethod = $data[0];
        }

        $paymentMethod = '';
        if ($paymentInfo = $checkout->getPayment()) {
            $paymentMethod = $paymentInfo->getMethod();
        }

        $items = '';
        foreach ($checkout->getAllVisibleItems() as $_item) {
            /* @var $_item Mage_Sales_Model_Quote_Item */
            $items .= $_item->getProduct()->getName() . '  x ' . (int)$_item->getQtyOrdered() . '  '
                // . $checkout->getOrderCurrencyCode() . ' '
                // . $_item->getRowTotal() . "\n";
                . ' ' . $checkout->formatPrice($_item->getRowTotal()) . "\n";
        }
        //wrong currency for total BSNPMG-124
        //   $total = $checkout->getStoreCurrencyCode() . ' ' . $checkout->getGrandTotal();
        //$total = $checkout->getOrderCurrencyCode() . ' ' . $checkout->getTotalRefunded();
        $total = $checkout->formatPrice($checkout->getTotalRefunded());

        //$baseTotal = $checkout->getStoreCurrencyCode() . ' ' . $checkout->getBaseTotalRefunded();
        $baseTotal = $checkout->formatBasePrice($checkout->getBaseTotalRefunded());

        $bluesnapTotal = $checkout->formatBluesnapPrice($checkout->getBluesnapTotalRefunded());


        foreach ($sendTo as $recipient) {
            $mailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $checkout->getStoreId()))
                ->sendTransactional(
                    $template,
                    Mage::getStoreConfig('checkout/payment_failed/identity', $checkout->getStoreId()),
                    $recipient['email'],
                    $recipient['name'],
                    array(
                        'reason' => $message,
                        'checkoutType' => $checkoutType,
                        'dateAndTime' => Mage::app()->getLocale()->date(),
                        'customer' => $checkout->getCustomerFirstname() . ' ' . $checkout->getCustomerLastname(),
                        'customerEmail' => $checkout->getCustomerEmail(),
                        'billingAddress' => $checkout->getBillingAddress(),
                        'shippingAddress' => $checkout->getShippingAddress(),
                        'shippingMethod' => Mage::getStoreConfig('carriers/' . $shippingMethod . '/title'),
                        'paymentMethod' => Mage::getStoreConfig('payment/' . $paymentMethod . '/title'),
                        'items' => nl2br($items),
                        'total' => $total,
                        'base_total' => $baseTotal,
                        'bluesnap_total' => $bluesnapTotal,
                        'order_id' => $checkout->getIncrementId(),

                    )
                );
        }

        $translate->setTranslateInline(true);

        return $this;
    }

}

