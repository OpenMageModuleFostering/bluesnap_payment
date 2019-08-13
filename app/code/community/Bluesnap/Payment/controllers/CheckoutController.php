<?php

/**
 * BlueSnap Checkout controller
 * Class Bluesnap_Payment_CheckoutController
 */
class Bluesnap_Payment_CheckoutController extends Mage_Core_Controller_Front_Action
{

    /**
     * Find last Customer's Order,
     * redirect to Bluesnap Checkout URL
     */
    public function redirectAction()
    {
        try {
            // load order
            $orderIncrementId = $this->_getCheckoutSession()->getLastRealOrderId();
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
            /* @var $order Mage_Sales_Model_Order */

            if (!$order->getId()) {
                Mage::throwException($this->__('Order not found.'));
            }
            Mage::register('current_order', $order);

            $this->updateCart($order);

            // create redirect url
            $url = $this->_api()->getOrderBuynowRedirectUrl($order);

            $this->loadLayout();
            // set page title
            $this->getLayout()->getBlock('head')->setTitle($this->__('BlueSnap Checkout'));

            // set BlueSnap Checkout iframe URL
            $this->getLayout()->getBlock('bluesnap_buynow.checkout.iframe')->setSrc($url);

            $this->renderLayout();

        } catch (Mage_Core_Exception $exc) {
            //BSNPMG-114
            $this->_getCheckoutSession()->addError($this->__("Gateway error"));
            $this->_redirect('checkout/cart');

        } catch (Exception $exc) {
            $this->_getCheckoutSession()->addError($this->__('Unable to check out.'));
            Mage::logException($exc);
            $this->_redirect('checkout/cart');
        }
    }

    /**
     * Get Customer's Checkout session
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Add products to the shopping cart from the current order
     * @param Mage_Sales_Model_Order $order
     * @return $this
     */
    protected function updateCart(Mage_Sales_Model_Order $order)
    {
        if (Mage::helper('checkout/cart')->getItemsCount()) {
            return $this;
        }

        $cart = Mage::getSingleton('checkout/cart');
        /* @var $cart Mage_Checkout_Model_Cart */

        $cart->truncate();

        $items = $order->getItemsCollection();
        foreach ($items as $item) {
            try {
                $cart->addOrderItem($item);
            } catch (Mage_Core_Exception $e) {
                if ($this->_getCheckoutSession()->getUseNotice(true)) {
                    $this->_getCheckoutSession()->addNotice($e->getMessage());
                } else {
                    $this->_getCheckoutSession()->addError($e->getMessage());
                }
                $this->_redirect('*/*/history');
            } catch (Exception $e) {
                $this->_getCheckoutSession()->addException($e,
                    Mage::helper('checkout')->__('Cannot add the item to shopping cart.')
                );
                $this->_redirect('checkout/cart');
            }
        }

        $cart->save();
        return $this;
    }

    /**
     * @return Bluesnap_Payment_Model_Api_Buynow
     *
     */
    protected function _api()
    {
        return Mage::getSingleton('Bluesnap_Payment_Model_Api_Buynow');
    }

    /**
     * Redirect customer to the success page
     */
    public function callbackAction()
    {
        $this->loadLayout()
            ->emptyCart()
            ->renderLayout();
    }

    /**
     * Empty shopping cart
     * @return $this
     */
    protected function emptyCart()
    {
        $cart = Mage::getSingleton('checkout/cart');
        /* @var $cart Mage_Checkout_Model_Cart */

        $cart->truncate()->save();
        return $this;
    }
}