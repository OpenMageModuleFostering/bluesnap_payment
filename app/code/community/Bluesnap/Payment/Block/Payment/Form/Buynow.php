<?php

/**
 * Prepare form for the BuyNow method
 * Class Bluesnap_Payment_Block_Payment_Form_Buynow
 */
class Bluesnap_Payment_Block_Payment_Form_Buynow extends Mage_Payment_Block_Form
{
    /**
     * Get method comment
     * @return string
     */
    public function getComment()
    {
        return $this->getMethod()->getComment();
    }

    /**
     * Show image in method comment
     * @return string
     */
    public function getMethodLabelAfterHtml()
    {
        return sprintf('<img src="%s" aligh="right" />', $this->getSkinUrl('images/bluesnap/buynow/cards.png'));
    }

    /**
     * Initialize, set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bluesnap/payment/form/buynow.phtml');
    }
}