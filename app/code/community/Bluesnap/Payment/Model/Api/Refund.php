<?php

/**
 * BlueSnap CSE API Calls
 */
class Bluesnap_Payment_Model_Api_Refund extends Bluesnap_Payment_Model_Api_Abstract
{
    //    INSUFFICIENT_FUNDS_FOR_REFUND    There are no sufficient funds to perform the requested refund.
    //INVOICE_ALREADY_REFUNDED  = 14009  Refund failed because the payment was already refunded.
    //INVOICE_ID_NOT_FOUND    The invoice ID passed in the request was not found.
    //NON_POSITIVE_AMOUNT_FAILURE    The amount passed in the request is 0 or negative.
    //PARTIAL_REFUND_AMOUNT_REQUIRED    The partial refund amount is required.
    //PARTIAL_REFUND_CREATED_LESS_THAN_24_HOURS_AGO =14020   Partial refund is not possible since the order was created less than 24 hours ago.
    //PARTIAL_REFUND_MORE_THAN_ONE_SKU    Only transactions which contain exactly one SKU can be partially refunded.
    //REFUND_GENERAL_FAILURE    A refund general failure has occurred.
    //REFUND_MAX_AMOUNT_FAILURE  = 14006  The refund amount passed in the request exceedes the maximal amount allowed.
    //REFUND_MIN_AMOUNT_FAILURE    The refund amount passed in the request is smaller than the minimal amount allowed.
    //REFUND_ORDER_WITH_ZERO_TOTAL_AMOUNT    Refund is not possible since the order total amount is 0.
    //REFUND_PERIOD_EXPIRED    Refund failed because the allowed refund period has ended.
    //REFUND_WITHOUT_REFUNDABLE_PAYMENTS    Refund is not possible since the order is not refundable.

    /**
     * Process refunf
     * @param $invoiceId (bluesnap refid)
     * @param $amount
     * @param $isFull
     * @throws Mage_Core_Exception
     * @link http://docs.bluesnap.com/api/services/orders/refund-invoice
     */
    public function process($invoiceId, $amount, $isFull = false, $incrementId = null)
    {
        $data = array(
            'invoiceId' => $invoiceId,
            'amount' => $amount
        );

        if ($isFull) {
            unset($data['amount']);
        }

        $url = $this->getServiceUrl('orders/refund');
        $query = http_build_query($data);

        $requestUrl = $url . '?' . $query;
        $response = $this->_request($requestUrl, null, self::HTTP_METHOD_PUT);
        if ($this->_curlInfo['http_code'] != 204 || !empty($response)) {
            // error
            try {
                $xml = $this->_parseXmlResponse($response);
                $message = (is_null($xml->message) || is_null($xml->message->description))
                    ? 'API error'
                    : $xml->message->description;
            } catch (Exception $e) {
                //not xml message
                $xml = $response;
                $message = $response;
            }

            $e = Mage::exception('Bluesnap_Payment', $message,
                (int)$xml->message->code);

            Mage::logException($e);
            $this->getLogger()->logError($requestUrl, $this->_responseXml, 0, $message, "refund", $incrementId, $url);

            throw $e;

        } else {
            $this->getLogger()->logSuccess($requestUrl, '204/successful', 0, "refunded", "refund", $incrementId, $url);
        }
    }
}