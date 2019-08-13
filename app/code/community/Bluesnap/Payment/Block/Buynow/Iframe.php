<?php

/**
 * BuyNow Checkout iframe block
 * Class Bluesnap_Payment_Block_Buynow_Iframe
 */
class Bluesnap_Payment_Block_Buynow_Iframe extends Mage_Core_Block_Abstract
{
    /**
     * Get iframe HTML attributes
     * @return string
     */
    public function getHtmlAttributes()
    {
        return array('src', 'width', 'height', 'style');
    }

    /**
     * Get block HTML
     * @return string
     */
    protected function _toHtml()
    {
        $attributeHtml = $this->serialize($this->getHtmlAttributes());
        $html = "<iframe {$attributeHtml}></iframe>";
        return $html;
    }

}