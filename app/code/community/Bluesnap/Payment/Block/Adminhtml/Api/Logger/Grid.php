<?php

/**
 * return details grid
 */
class Bluesnap_Payment_Block_Adminhtml_Api_Logger_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    protected $_defaultSort = 'event_id';
    protected $_defaultDir = 'desc';

    public function getRowUrl($row)
    {
        return false;
    }

    public function decorateSeverity($value, $row)
    {
        $class = '';
        switch ($row->getPriority()) {
            case Zend_Log::EMERG:
            case Zend_Log::ALERT:
            case Zend_Log::CRIT:
            case Zend_Log::ERR:
                $class = 'grid-severity-critical';
                break;
            case Zend_Log::WARN:
            case Zend_Log::NOTICE:
            case Zend_Log::INFO:
            case Zend_Log::DEBUG:
                $class = 'grid-severity-minor';
                break;
            default:
                $class = 'grid-severity-critical';
        }

        return '<span class="' . $class . '"><span>' . $value . '</span></span>';
    }

    protected function _prepareCollection()
    {
        /**
         * Tell Magento which collection to use to display in the grid.
         */
        $collection = Mage::getResourceModel('bluesnap/logger_db_entry_collection');


        //$collection->addFieldToFilter('return_id',Mage::app()->getRequest()->getParam('id'));
        $this->setCollection($collection);

        if ($this->getCollection()) {

            $this->_preparePage();

            $columnId = $this->getParam($this->getVarNameSort(), $this->_defaultSort);
            $dir = $this->getParam($this->getVarNameDir(), $this->_defaultDir);
            $filter = $this->getParam($this->getVarNameFilter(), null);

            if (is_null($filter)) {
                $filter = $this->_defaultFilter;
            }

            if (is_string($filter)) {
                $data = $this->helper('adminhtml')->prepareFilterString($filter);
                $this->_setFilterValues($data);
            } else if ($filter && is_array($filter)) {
                $this->_setFilterValues($filter);
            } else if (0 !== sizeof($this->_defaultFilter)) {
                $this->_setFilterValues($this->_defaultFilter);
            }

            if (isset($this->_columns[$columnId]) && $this->_columns[$columnId]->getIndex()) {
                $dir = (strtolower($dir) == 'desc') ? 'desc' : 'asc';
                $this->_columns[$columnId]->setDir($dir);
                $this->_setCollectionOrder($this->_columns[$columnId]);
            }

            if (!$this->_isExport) {
                $this->getCollection()->load();
                $this->_afterLoadCollection();
            }
        }
        return $this;
    }

    protected function _prepareColumns()
    {
        /**
         * Here, we'll define which columns to display in the grid.
         */
        $this->addColumn('event_id', array(
            'header' => $this->_getHelper()->__('ID'),
            'type' => 'number',
            'index' => 'event_id',
        ));


        $this->addColumn('method', array(
            'header' => $this->_getHelper()->__('Method'),
            'type' => 'text',
            'index' => 'method',
            'column_css_class' => 'grid-column-width-100',
        ));

        $this->addColumn('request_url', array(
            'header' => $this->_getHelper()->__('Request URL'),
            'type' => 'text',
            'index' => 'request_url',
            'column_css_class' => 'grid-column-long-text-overflow',
        ));

        $this->addColumn('request', array(
            'header' => $this->_getHelper()->__('Request'),
            'type' => 'html',
            //     'width'=>'300px',
            'column_css_class' => 'grid-column-long-text-overflow',
            'index' => 'request',
            'renderer' => 'Bluesnap_Payment_Block_Adminhtml_Widget_Grid_Column_Renderer_Xml',
        ));

        $this->addColumn('response', array(
            'header' => $this->_getHelper()->__('Response'),
            'type' => 'html',
            'column_css_class' => 'grid-column-long-text-overflow',
            'renderer' => 'Bluesnap_Payment_Block_Adminhtml_Widget_Grid_Column_Renderer_Xml',
            'index' => 'response',
        ));


        $this->addColumn('ip', array(
            'header' => $this->_getHelper()->__('IP'),
            'type' => 'text',
            'column_css_class' => 'grid-column-width-100',

            'index' => 'ip',
        ));


        $this->addColumn('user_agent', array(
            'header' => $this->_getHelper()->__('User Agent'),
            'type' => 'text',
            'column_css_class' => 'grid-column-long-text-overflow',

            'index' => 'user_agent',
        ));


        $this->addColumn('message', array(
            'header' => $this->_getHelper()->__('Message'),
            'type' => 'text',
            'column_css_class' => 'grid-column-width-100',
            // 'column_css'=>'width:200px',
            //   'css'=>'width:200px',
            //   'style'=>'width:200px',

            'index' => 'message',
        ));


        $this->addColumn('increment_id', array(
            'header' => $this->_getHelper()->__('Increment Id'),
            'type' => 'number',
            'index' => 'increment_id',
        ));

        //    $this->addColumn('return_id', array(
        //       'header' => $this->_getHelper()->__('Return Id'),
        //       'type' => 'number',
        //       'index' => 'return_id',
        //   ));


        $this->addColumn('created_at', array(
            'header' => $this->_getHelper()->__('Created At'),
            'type' => 'dateTime',
            'index' => 'created_at',
        ));


        $this->addColumn('priority', array(
            'header' => $this->_getHelper()->__('Log Level'),
            'align' => 'left',
            'index' => 'priority',
            'type' => 'options',
            'options' => $this->getSeverityOptions(),
            'frame_callback' => array($this, 'decorateSeverity')
        ));


        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel XML'));

        return $this;
        //return parent::_prepareColumns();
    }

    protected function _getHelper()
    {
        return Mage::helper('bluesnap');
    }

    /**
     * Retrieve the severity options
     *
     * @return array
     */
    public function getSeverityOptions()
    {
        return array(
            Zend_Log::EMERG => $this->_getHelper()->__('Emergency'),
            Zend_Log::ALERT => $this->_getHelper()->__('Alert'),
            Zend_Log::CRIT => $this->_getHelper()->__('Critical'),
            Zend_Log::ERR => $this->_getHelper()->__('Error'),
            Zend_Log::WARN => $this->_getHelper()->__('Warning'),
            Zend_Log::NOTICE => $this->_getHelper()->__('Notice'),
            Zend_Log::INFO => $this->_getHelper()->__('Info'),
            Zend_Log::DEBUG => $this->_getHelper()->__('Debug'),
        );
    }
}
