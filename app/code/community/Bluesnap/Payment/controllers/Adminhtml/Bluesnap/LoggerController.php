<?php

class Bluesnap_Payment_Adminhtml_Bluesnap_LoggerController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Returns details grid
     */
    function indexAction()
    {
        $this->loadLayout()
            // ->_addContent($returnBlock)
            ->renderLayout();
    }
}

