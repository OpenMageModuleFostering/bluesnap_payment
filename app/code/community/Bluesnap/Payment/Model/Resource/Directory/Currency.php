<?php

class Bluesnap_Payment_Model_Resource_Directory_Currency extends Mage_Directory_Model_Resource_Currency
{
    /**
     * Saving currency rates
     * @deprec to be removed
     * @param array $rates
     */
    public function saveBluesnapRates($rates)
    {
        if (is_array($rates) && sizeof($rates) > 0) {
            $adapter = $this->_getWriteAdapter();
            // $data    = array();
            foreach ($rates as $currencyCode => $rate) {
                foreach ($rate as $currencyTo => $row) {

                    $value = $row['value'];
                    $value = abs($value);
                    if ($value == 0) {
                        //  continue;
                        $data = array(
                            'currency_from' => $currencyCode,
                            'currency_to' => $currencyTo,
                            //'rate'          => $value,
                            'result' => 'failed',
                            'date_updated' => date('Y-m-d H:i:s'),
                            'bluesnap_supported' => $row['bluesnap_supported'],
                        );

                        if ($data) {
                            try {
                                $adapter->insertOnDuplicate($this->_currencyRateTable, $data, array('rate', 'result', 'date_updated', 'bluesnap_supported'));

                            } catch (Exception $e) {
                                echo $e->getMessage() . "\n" . var_export($data, 1) . "\n";
                            }

                        }
                    } else {
                        $data = array(
                            'currency_from' => $currencyCode,
                            'currency_to' => $currencyTo,
                            'rate' => $value,
                            'result' => 'success',
                            'date_updated' => date('Y-m-d H:i:s'),
                            'bluesnap_supported' => $row['bluesnap_supported'],
                        );

                        if ($data) {
                            try {
                                $adapter->insertOnDuplicate($this->_currencyRateTable, $data, array('rate', 'result', 'date_updated', 'bluesnap_supported'));

                            } catch (Exception $e) {
                                echo $e->getMessage() . "\n" . var_export($data, 1) . "\n";
                            }

                        }

                    }

                }
            }

        } else {
            Mage::throwException(Mage::helper('directory')->__('Invalid rates received'));
        }
    }

}

