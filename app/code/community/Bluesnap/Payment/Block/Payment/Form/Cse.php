<?php

/**
 * Prepare form for the CSE method
 * Class Bluesnap_Payment_Block_Payment_Form_Cse
 */
class Bluesnap_Payment_Block_Payment_Form_Cse extends Mage_Payment_Block_Form_Cc
{
    /**
     * Get notice message
     * @param $message
     * @return string
     */
    public function showNoticeMessage($message)
    {
        return $this->getLayout()->getMessagesBlock()
            ->addNotice($this->__($message))
            ->getGroupedHtml();
    }

    /**
     * Get merchant's public key
     * @return string
     */
    public function getPublicKey()
    {
        return $this->getMethod()->getPublicKey();
    }

    /**
     * Get list of saved cards
     * @return array
     */
    public function getCards()
    {
        $result = array();

        if ($cards = Mage::registry('bs_shopper_cards')) {

            foreach ($cards as $card => $type) {
                $cc_data = $card . '/' . $type;
                $result[md5($card)] = array(
                    'enc_type' => Mage::helper('core')->encrypt($cc_data),
                    'type' => $type,
                    'card' => $card
                );
            }
        }

        return $result;
    }

    /**
     * Set block template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bluesnap/payment/form/cse.phtml');
    }

    /**
     * Render block HTML
     * @return string
     */
    protected function _toHtml()
    {
        $this->setChild('cards', $this->getCardsBlock());
        $this->setChild('method_form_block', $this->getMethodFormBlock());
        return parent::_toHtml();
    }
}
