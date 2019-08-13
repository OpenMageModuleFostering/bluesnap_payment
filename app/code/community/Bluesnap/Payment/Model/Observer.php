<?php

class Bluesnap_Payment_Model_Observer 
{

    /**
     * put currency rate to order
     *
     * @param mixed $observer
     */
    function salesOrderSaveBefore($observer)
    {

        $order = $observer->getOrder();

        $amount = $order->getGrandTotal();
        $orderCurrencyCode = $order->getOrderCurrencyCode();

        $baseAmount = $order->getBaseGrandTotal();
        $baseCurrencyCode = $order->getBaseCurrencyCode();
        $orderCurrencyCode = $order->getOrderCurrencyCode();
        $currency = Mage::getModel('directory/currency');
        $currency->load($baseCurrencyCode);
        //local supported
        if (!$currency->isBluesnapCurrencySupported($orderCurrencyCode)) {
            $bluesnapCurrencyCode = $baseCurrencyCode;
            $bluesnapCurrencyRate = 1;
            $bluesnapCurrencyAmount = $baseAmount;
        } else {
            //all other currencies
            $baseCurrency = Mage::getModel('directory/currency');
            $baseCurrency->load($order->getBaseCurrencyCode());

            $bluesnapCurrencyCode = Bluesnap_Payment_Helper_Config::BLUESNAP_DEFAULT_CURRENCY;
            $bluesnapCurrencyRate = $baseCurrency->getBluesnapRate($orderCurrencyCode, $baseCurrencyCode);

            if ($order->getBaseCurrencyCode() == 'USD') //do not convert
                $bluesnapCurrencyAmount = $order->getBaseGrandTotal();
            else {
                $bluesnapCurrencyAmount = $baseCurrency->convert($order->getBaseGrandTotal(), $bluesnapCurrencyCode);
            }
        }
        $order->setBluesnapCurrencyRate($bluesnapCurrencyRate);

        $order->setBluesnapCurrencyCode($baseCurrencyCode);
        $order->setBluesnapGrandTotal($baseAmount);


    }


    /**
     * put currency rate to quote
     *
     * @param mixed $observer
     */
    function salesQuoteSaveBefore($observer)
    {

        $quote = $observer->getQuote();

        $amount = $quote->getGrandTotal();
        $quoteCurrencyCode = $quote->getQuoteCurrencyCode();

        $baseAmount = $quote->getBaseGrandTotal();
        $baseCurrencyCode = $quote->getBaseCurrencyCode();

        $currency = Mage::getModel('directory/currency');

        $currency->load($baseCurrencyCode);
        //local supported
        if ($currency->isBluesnapCurrencySupported($baseCurrencyCode)) {

            $bluesnapCurrencyCode = $baseCurrencyCode;
            $bluesnapCurrencyRate = 1;
            $bluesnapCurrencyAmount = $baseAmount;

            //exists, not supported (rate of bsnap) //all other currencies (rate of webservicex) 

        } else {
            //all other currencies
            $baseCurrency = Mage::getModel('directory/currency');
            $baseCurrency->load($quote->getBaseCurrencyCode());

            $bluesnapCurrencyCode = Bluesnap_Payment_Helper_Config::BLUESNAP_DEFAULT_CURRENCY;

            $bluesnapCurrencyRate = $baseCurrency->getRate($bluesnapCurrencyCode);

            if ($quote->getBaseCurrencyCode() == 'USD') //do not convert
                $bluesnapCurrencyAmount = $quote->getBaseGrandTotal();
            else {
                $bluesnapCurrencyAmount = $baseCurrency->convert($quote->getBaseGrandTotal(), $bluesnapCurrencyCode);
            }


        }

        $bluesnapCurrencyAmount = Mage::app()->getStore()->roundPrice($bluesnapCurrencyAmount);

        $quote->setBluesnapCurrencyRate($bluesnapCurrencyRate);
        $quote->setBluesnapCurrencyCode($bluesnapCurrencyCode);
        $quote->setBluesnapGrandTotal($bluesnapCurrencyAmount);
    }

    //sales_order_grid_collection_load_before
    public function salesOrderGridCollectionLoadBefore($observer)
    {
        $collection = $observer->getOrderGridCollection();
        $collection->addFilterToMap('store_id', 'main_table.store_id');
        $select = $collection->getSelect();
        $select->joinLeft(array('oe' => $collection->getTable('sales/order')),
            'oe.entity_id=main_table.entity_id',
            array('oe.bluesnap_reference_number', 'oe.bluesnap_grand_total', 'oe.bluesnap_total_paid', 'oe.bluesnap_currency_code'));

    }

    //core_block_abstract_prepare_layout_before
    public function appendCustomColumn(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();
        if (!isset($block)) {
            return $this;
        }

        if ($block->getType() == 'adminhtml/sales_order_grid') {
            /* @var $block Mage_Adminhtml_Block_Customer_Grid */
            $this->_addColumnToGrid($block);
        }
        return null;
    }

    protected function _addColumnToGrid($grid)
    {

        /* @var $block Mage_Adminhtml_Block_Customer_Grid */
        $grid->addColumnAfter('bluesnap_reference_number', array(
            'header' => Mage::helper('bluesnap')->__('Bluesnap Reference Number'),
            'index' => 'bluesnap_reference_number',
            'filter_index' => 'oe.bluesnap_reference_number',
            'type' => 'text',
            //'options' => $groups,
        ), 'increment_id');


        $grid->addColumnAfter('bluesnap_grand_total', array(
            'header' => Mage::helper('bluesnap')->__('Bluesnap Grand Total'),
            'index' => 'bluesnap_grand_total',
            'filter_index' => 'oe.bluesnap_grand_total',
            'type' => 'currency',
            'currency' => 'bluesnap_currency_code',
            //'options' => $groups,
        ), 'grand_total');

        $grid->addColumnAfter('bluesnap_total_paid', array(
            'header' => Mage::helper('bluesnap')->__('Bluesnap Total Paid'),
            'index' => 'bluesnap_total_paid',
            'filter_index' => 'oe.bluesnap_total_paid',
            'type' => 'currency',
            'currency' => 'bluesnap_currency_code',

            //'options' => $groups,
        ), 'bluesnap_grand_total');
    }


    //sales_order_creditmemo_refund
    function salesOrderCreditmemoRefund($observer)
    {
        $creditmemo = $observer->getCreditmemo();
        $order = $creditmemo->getOrder();
        $payment = $creditmemo->getOrder()->getPayment();

        $baseTotalRefunded = $order->getBaseTotalRefunded();
        //get bluesnap refund by percentage
        $bluesnapTotalRefunded = $baseTotalRefunded / $order->getBaseGrandTotal() * $order->getBluesnapGrandTotal();
        $bluesnapTotalRefunded = round($bluesnapTotalRefunded, 2);

        //$creditmemo->setTransactionId($payment->getLastTransId());
        $creditmemo->setBluesnapCurrencyCode($payment->getOrder()->getBluesnapCurrencyCode());
        $creditmemo->setBluesnapGrandTotal($bluesnapTotalRefunded);

        return $this;
    }

    //sales_order_invoice_register
    function salesOrderInvoiceRegister($observer)
    {
        $invoice = $observer->getInvoice();
        $order = $observer->getOrder();

        //$order->getBaseTotalInvoiced()
        //@todo - move it to place_end event
        $invoice->setBluesnapGrandTotal($order->getBluesnapGrandTotal());
        $invoice->setBluesnapCurrencyCode($order->getBluesnapCurrencyCode());
        return $this;

    }

    //check if server is secured
    function checkSecure($observer)
    {
        $postData = $observer->getEvent()->getData();
        if ($this->getIsSandbox($postData) == '0') {
            if (!isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "on") {
                // set sandbox mode becasue admin is not secured
                $this->setSandbox($postData);

                //check if ssl is installed to show the right message
                if (!$this->is_exist_ssl(Mage::getBaseUrl())) Mage::throwException(Mage::helper('bluesnap')->__('Due to Payment Security reasons, BlueSnap’s payment can’t be set to production on an unsecured page. Please ensure you are using HTTPS.'));
                else Mage::throwException(Mage::helper('bluesnap')->__('Due to Payment Security reasons, BlueSnap’s payment can’t be set to production on an unsecured page. Please ensure you enable secure URLs in admin.'));
            } else {
                if (!Mage::app()->getStore()->isFrontUrlSecure()) {
                    $this->setSandbox($postData);
                    Mage::throwException(Mage::helper('bluesnap')->__('Due to Payment Security reasons, BlueSnap’s payment can’t be set to production on an unsecured page. Please ensure you enable secure URLs in frontend.'));

                }
                if (!Mage::app()->getStore()->isAdminUrlSecure()) {
                    $this->setSandbox($postData);
                    Mage::throwException(Mage::helper('bluesnap')->__('Due to Payment Security reasons, BlueSnap’s payment can’t be set to production on an unsecured page. Please ensure you enable secure URLs in admin.'));
                }

            }
        }
    }

    private function getIsSandbox($postData)
    {
        if (is_null($postData['store']) && $postData['website']) //check for website scope
        {
            $scopeId = Mage::getModel('core/website')->load($postData['website'])->getId();
            $isSnadBox = Mage::app()->getWebsite($scopeId)->getConfig('bluesnap/general/is_sandbox_mode');
        } elseif ($postData['store']) //check for store scope
        {
            $scopeId = Mage::getModel('core/store')->load($postData['store'])->getId();
            $isSnadBox = Mage::app()->getWebsite($scopeId)->getConfig('bluesnap/general/is_sandbox_mode');
        } else //for default scope
        {
            $scopeId = 0;
            $isSnadBox = Mage::getStoreConfig('bluesnap/general/is_sandbox_mode');
        }

        return $isSnadBox;
    }

    private function setSandbox($postData)
    {
        if (is_null($postData['store']) && $postData['website']) //check for website scope
        {
            $scopeId = Mage::getModel('core/website')->load($postData['website'])->getId();
            $currentScope = 'websites';
        } elseif ($postData['store']) //check for store scope
        {
            $scopeId = Mage::getModel('core/store')->load($postData['store'])->getId();
            $currentScope = 'stores';
        } else //for default scope
        {
            $scopeId = 0;
            $currentScope = 'default';
        }

        if ($currentScop == 'default') Mage::getConfig()->saveConfig('bluesnap/general/is_sandbox_mode', '1');
        else Mage::getConfig()->saveConfig('bluesnap/general/is_sandbox_mode', '1', $currentScope, $scopeId);
    }

    //we check if ssl is installed on server and user is working on non secure page

    private function is_exist_ssl($baseurl)
    {
		$domain=parse_url($baseurl, PHP_URL_HOST);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://" . $domain);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);

        if (!curl_error($ch)) {
            $info = curl_getinfo($ch);
            if ($info['http_code'] == 200) {
                return true;
            }
            return false;
        } else {
            return false;
        }
    }
    public function sendCron() {
	$days = Mage::getStoreConfig('bluesnap/logger/collect_data_for');

	$collection = Mage::getResourceModel('bluesnap/logger_db_entry_collection')
		->addFieldToFilter('created_at', array('lt' => date("Y-m-d H:i:s", strtotime('-' . $days . 'day'))));
	foreach($collection as $logger) {
		$logger->delete();
	}
	$collection->save();
    }
}
