<?php

class Bluesnap_Payment_Helper_Sales extends Mage_Adminhtml_Helper_Sales
{

    /**
     * Get "double" prices html (block with base and place currency)
     *
     * @param   Varien_Object $dataObject
     * @param   float $basePrice
     * @param   float $price
     * @param   bool $strong
     * @param   string $separator
     * @return  string
     */
    public function displayPricesBluesnap($dataObject, $basePrice, $price, $bluesnapPrice = null, $strong = false, $separator = '<br/>')
    {
        $order = false;
        if ($dataObject instanceof Mage_Sales_Model_Order) {
            $order = $dataObject;
        } else {
            $order = $dataObject->getOrder();
        }

        if ($order && $order->isCurrencyDifferent()) {
            $res = '<strong>';
            $res .= $order->formatBasePrice($basePrice);
            $res .= '</strong>' . $separator;
            $res .= '[' . $order->formatPrice($price) . ']';
            if ((double)$bluesnapPrice && $order->getBluesnapCurrencyCode() != $order->getBaseCurrencyCode())
                $res .= '[' . $order->formatBluesnapPrice($bluesnapPrice) . ']';

        } elseif ($order) {
            $res = $order->formatPrice($price);
            if ($strong) {
                $res = '<strong>' . $res . '</strong>';
            }

            if ((double)$bluesnapPrice && $order->getBluesnapCurrencyCode() != $order->getBaseCurrencyCode())
                $res .= '[' . $order->formatBluesnapPrice($bluesnapPrice) . ']';

        } else {
            $res = Mage::app()->getStore()->formatPrice($price);
            if ($strong) {
                $res = '<strong>' . $res . '</strong>';
            }
        }
        return $res;
    }
}

?>
