<?php

/**
 * new config, extends the old config version for compatibility
 */
class Bluesnap_Payment_Model_Api_Config extends Bluesnap_Payment_Helper_Data
{
    /**
     * Setter/Getter underscore transformation cache
     *
     * @var array
     */
    protected static $_underscoreCache = array();
    protected $_configPath = 'bluesnap';

    public function __call($method, $args)
    {
        switch (substr($method, 0, 3)) {
            case 'get' :
                $key = $this->_underscore(substr($method, 3));
                return $this->getConfigData($key);
        }
        throw new Varien_Exception("Invalid method " . get_class($this) . "::" . $method . "(" . print_r($args, 1) . ")");
    }

    protected function _underscore($name)
    {
        if (isset(self::$_underscoreCache[$name])) {
            return self::$_underscoreCache[$name];
        }
        $result = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $name));
        self::$_underscoreCache[$name] = $result;
        return $result;
    }

    public function getConfigData($field = null, $storeId = null)
    {
        if ($field) {
            $path = $this->_configPath . '/' . $field;
            $value = Mage::getStoreConfig($path, $storeId);
            return $value;

        } else
            return Mage::getStoreConfig($this->_configPath, $storeId);

    }
}

