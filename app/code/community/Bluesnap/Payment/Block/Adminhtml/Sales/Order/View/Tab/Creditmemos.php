<?php

class Bluesnap_Payment_Block_Adminhtml_Sales_Order_View_Tab_Creditmemos
    extends Mage_Adminhtml_Block_Sales_Order_View_Tab_Creditmemos
{

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel($this->_getCollectionClass())
            ->addFieldToSelect('entity_id')
            ->addFieldToSelect('created_at')
            ->addFieldToSelect('increment_id')
            ->addFieldToSelect('order_currency_code')
            ->addFieldToSelect('store_currency_code')
            ->addFieldToSelect('base_currency_code')
            ->addFieldToSelect('state')
            ->addFieldToSelect('grand_total')
            ->addFieldToSelect('base_grand_total')
            //      ->addFieldToSelect('billing_name')
            ->setOrderFilter($this->getOrder())
            ->addFieldToSelect('bluesnap_currency_code')
            ->addFieldToSelect('bluesnap_grand_total');

        $this->setCollection($collection);
        return $this;
    }

    protected function _getCollectionClass()
    {
        return 'sales/order_creditmemo_collection';
    }

    protected function _prepareColumns()
    {
        $this->addColumn('increment_id', array(
            'header' => Mage::helper('sales')->__('Invoice #'),
            'index' => 'increment_id',
            'width' => '120px',
        ));

        //    $this->addColumn('billing_name', array(
        //        'header' => Mage::helper('sales')->__('Bill to Name'),
        //        'index' => 'billing_name',
        //    ));

        $this->addColumn('created_at', array(
            'header' => Mage::helper('sales')->__('Invoice Date'),
            'index' => 'created_at',
            'type' => 'datetime',
        ));

        $this->addColumn('state', array(
            'header' => Mage::helper('sales')->__('Status'),
            'index' => 'state',
            'type' => 'options',
            'options' => Mage::getModel('sales/order_invoice')->getStates(),
        ));


        $this->addColumn('bluesnap_grand_total', array(
            'header' => Mage::helper('customer')->__('Bluesnap Amount Refunded'),
            'index' => 'bluesnap_grand_total',
            'type' => 'currency',
            'currency' => 'bluesnap_currency_code',
        ));

        $this->addColumn('base_grand_total', array(
            'header' => Mage::helper('customer')->__('Base Amount'),
            'index' => 'base_grand_total',
            'type' => 'currency',
            'currency' => 'base_currency_code',
        ));


        $this->addColumn('grand_total', array(
            'header' => Mage::helper('customer')->__('Amount'),
            'index' => 'grand_total',
            'type' => 'currency',
            'currency' => 'order_currency_code',
        ));

        //  return parent::_prepareColumns();
        $this->sortColumnsByOrder();
        return $this;
    }
}

