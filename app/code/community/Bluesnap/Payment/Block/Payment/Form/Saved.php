<?php

/**
 * Prepare form for the CSE-saved method
 * Class Bluesnap_Payment_Block_Payment_Form_Saved
 */
class Bluesnap_Payment_Block_Payment_Form_Saved extends Bluesnap_Payment_Block_Payment_Form_Cse
{
    /**
     * Get list of saved cards
     * @return array
     */
    public function getCards()
    {
        $cards = Mage::registry('bs_shopper_cards');
        $result = array();
        foreach ($cards as $card => $type) {
            $cc_data = $card . '/' . $type;
            $result[md5($card)] = array(
                'enc_type' => Mage::helper('core')->encrypt($cc_data),
                'type' => $type,
                'card' => $card
            );
        }

        return $result;
    }

    /**
     * Set block template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bluesnap/payment/form/saved.phtml');
    }

}
