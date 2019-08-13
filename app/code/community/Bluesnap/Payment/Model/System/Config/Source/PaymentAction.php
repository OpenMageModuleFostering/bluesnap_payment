<?php

/**
 *
 * Bluesnap Payment Action Dropdown source
 *
 * @author      Fisha Team <core@magentocommerce.com>
 */
class Bluesnap_Payment_Model_System_Config_Source_PaymentAction
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Bluesnap_Payment_Model_Payment_Cse::ACTION_AUTHORIZE,
                'label' => Mage::helper('paygate')->__('Authorize Only')
            ),
            array(
                'value' => Bluesnap_Payment_Model_Payment_Cse::ACTION_AUTHORIZE_CAPTURE,
                'label' => Mage::helper('paygate')->__('Authorize and Capture')
            ),
        );
    }
}
