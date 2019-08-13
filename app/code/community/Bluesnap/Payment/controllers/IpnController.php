<?php

/**
 * IPN request processing
 * Class Bluesnap_Payment_IpnController
 */
class Bluesnap_Payment_IpnController extends Mage_Core_Controller_Front_Action
{
    /**
     * Process IPN request
     */
    public function callbackAction()
    {
        try {
            $ipn = Mage::getSingleton('bluesnap/ipn');
            /* @var $ipn Bluesnap_Payment_Model_Ipn */

            $processed = $ipn->processTransactionRequest($this->getRequest());

            if ($processed) {
                $this->getResponse()->setHeader(
                    'Content-Type',
                    'text/plain',
                    true
                );
                $this->getResponse()->setBody($ipn->getOkResponseString());
            }

        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}
