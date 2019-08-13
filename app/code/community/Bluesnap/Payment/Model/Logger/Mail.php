<?php

class Bluesnap_Payment_Model_Logger_Mail extends Zend_Log_Writer_Mail
{


    /**
     * @var Zend_Mail_Transport_Smtp|null
     */
    protected $_transport = null;

    /**
     * Instantiate the mail object
     *
     * @param string $filename Filename
     */
    public function __construct($filename)
    {
        parent::__construct($this->getMail());
        $this->setFormatter(new Zend_Log_Formatter_Simple($this->getConfig()->getConfigData('logger/mail_template')));
        $this->addFilter(new Zend_Log_Filter_Priority((int)$this->getConfig()->getConfigData('logger/mail_priority')));
    }

    /**
     * Get the mail object
     *
     * @return Zend_Mail
     */
    public function getMail()
    {
        if ($this->_mail === null) {
            $this->_mail = new Zend_Mail();

            /** @var $helper FireGento_Logger_Helper_Data */
            $helper = Mage::helper('bluesnap');

            $storeName = Mage::app()->getStore()->getName();
            // $subject = $storeName .' - Api Alert';


            $this->_mail->setFrom(Mage::getStoreConfig('trans_email/ident_general/email'), Mage::getStoreConfig('trans_email/ident_general/name'));

            //$this->_mail->setFrom($helper->getLoggerConfig('mailconfig/from'), $storeName);
            //$this->_mail->setSubject($subject);

            $recipients = explode("\n", trim($this->getConfig()->getConfigData('logger/mail_recipient_list')));
            foreach ($recipients as $recipient) {
                $this->_mail->addTo(trim($recipient));
            }


            if (Mage::helper('core')->isModuleEnabled('Aschroder_SMTPPro')) {
                $this->_mail->setDefaultTransport(Mage::helper('smtppro')->getTransport());
            }

            // $this->_mail->setDefaultTransport($this->getTransport());
        }

        return $this->_mail;
    }

    /**
     * @return Bluesnap_Payment_Model_Api_Config
     *
     */
    function getConfig()
    {
        return Mage::getSingleton('bluesnap/api_config');
    }

    /**
     * Satisfy newer Zend Framework
     *
     * @param  array|Zend_Config $config Configuration
     * @return void|Zend_Log_FactoryInterface
     */
    public static function factory($config)
    {

    }

    function resetFilters()
    {
        $this->_filters = array();
    }

    /**
     * Send the log mails
     *
     * @param array $event Event data
     */
    public function _write($event)
    {
        //Lazy intatiation of underlying mailer
        if ($this->_mail === null) {
            $this->_mail = $this->getMail();
        }
        $this->_mail->clearSubject();

        $this->_mail->setSubject($this->getConfig()->getConfigData('logger/mail_subject'));


        parent::_write($event);
        parent::shutdown();
        $this->_mail->clearSubject();
        //$this->_mail->clearContent();
    }

    /**
     * Retreive the transport object
     *
     * @return Zend_Mail_Transport_Abstract Transport Object
     */
    public function getTransport()
    {
        if ($this->_transport === null) {
            /** @var $helper FireGento_Logger_Helper_Data */
            $helper = Mage::helper('bluesnap');

            $config = array(
                'auth' => 'login',
                'username' => $helper->getLoggerConfig('mailconfig/username'),
                'password' => $helper->getLoggerConfig('mailconfig/password')
            );

            // Reset config array if username is empty
            if (!isset($config['username']) || empty($config['username'])) {
                $config = array();
            }

            // Instantiate the transport class
            $this->_transport = new Zend_Mail_Transport_Smtp($helper->getLoggerConfig('mailconfig/hostname'), $config);

        }
        return $this->_transport;
    }
}
