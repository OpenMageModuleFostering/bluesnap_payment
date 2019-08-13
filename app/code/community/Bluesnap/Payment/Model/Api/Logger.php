<?php

/**
 * detailed import log
 */
class Bluesnap_Payment_Model_Api_Logger
{


    protected $_loggers = array();

    function __construct()
    {
        $this->_loggers['db'] = Mage::getsingleton('Bluesnap_Payment_Model_Logger_Db');
        $this->_loggers['file'] = Mage::getsingleton('Bluesnap_Payment_Model_Logger_File');
        $this->_loggers['mail'] = Mage::getsingleton('Bluesnap_Payment_Model_Logger_Mail');
    }

    function logSuccess($request, $response, $responseCode, $message, $method = null, $incrementId = null, $url = " ")
    {
        $event = new Varien_Object();
        $event->setPriority(Zend_Log::INFO);
        $event->setPriorityName("Info");

        $event->setCreatedAt(Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s'));
        $event->setTimestamp(Mage::getModel('core/date')->date('Y-m-d H:i:s'));

        $event->setMessage($message);
        $event->setMethod($method);
        $event->setRequestUrl($url);
        $event->setReturnCode($responseCode);

        if (is_array($request))
            $event->setRequest(print_r($request, 1));
        else
            $event->setRequest($request);

        $event->setResponse($response);
        $event->setIncrementId($incrementId);
        $event->setIp($_SERVER['REMOTE_ADDR']);
        $event->setUserAgent($_SERVER['HTTP_USER_AGENT']);

        $this->write($event);
        Mage::log($message, Zend_Log::INFO);

    }

    function write($event)
    {
        foreach ($this->_loggers as $logger) {
            if (is_object($event))
                $event = $event->toArray();
            $logger->write($event);
        }
    }

    function logError($request, $response, $responseCode, $message, $method = null, $incrementId = null, $url = " ")
    {
        $event = new Varien_Object();

        $event->setCreatedAt(Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s'));
        $event->setTimestamp(Mage::getModel('core/date')->date('Y-m-d H:i:s'));

        $event->setPriority(Zend_Log::ERR);
        $event->setPriorityName("Error");
        $event->setMessage($message);
        $event->setMethod($method);
        $event->setRequestUrl($url);
        $event->setReturnCode($responseCode);
        if (is_array($request))
            $event->setRequest(print_r($request, 1));
        else
            $event->setRequest($request);

        $event->setResponse($response);
        //  $event->setOrderId($orderId);
        $event->setIncrementId($incrementId);
        $event->setIp($_SERVER['REMOTE_ADDR']);
        $event->setUserAgent($_SERVER['HTTP_USER_AGENT']);

        $this->write($event);
        Mage::log($message, Zend_Log::ERR);
    }
}

