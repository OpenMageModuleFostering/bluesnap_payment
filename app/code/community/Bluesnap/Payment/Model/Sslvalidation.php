<?php
class Bluesnap_Payment_Model_Sslvalidation extends Mage_Core_Model_Config_Data
{
    public function save()
    {
        $isSandbox = $this->getValue(); 
        
        if($isSandbox == 0)   //exit if we're less than 10 digits long
        { 
        	$canProceed = $this->checkSecure($isSandbox);
        	
        	switch ($canProceed) {
 			   case 0:
      			  Mage::throwException(Mage::helper('bluesnap')->__('Due to Payment Security reasons, BlueSnap’s payment can’t be set to production on an unsecured page. Please ensure you are using HTTPS.'));
           		  break;
    		   case 2:
        		 Mage::throwException(Mage::helper('bluesnap')->__('Due to Payment Security reasons, BlueSnap’s payment can’t be set to production on an unsecured page. Please ensure you enable secure URLs in admin.'));
            	 break;
   			   case 3:
        		 Mage::throwException(Mage::helper('bluesnap')->__('Due to Payment Security reasons, BlueSnap’s payment can’t be set to production on an unsecured page. Please ensure you enable secure URLs in frontend.'));
				 break;
			   case 4:
        		 Mage::throwException(Mage::helper('bluesnap')->__('Due to Payment Security reasons, BlueSnap’s payment can’t be set to production on an unsecured page. Please ensure you enable secure URLs in admin.'));
                 break;
			}
        	if($canProceed!=1) {
            	exit();
            }
        }
 
        return parent::save();  //call original save method so whatever happened 
                                //before still happens (the value saves)
    }
    
      //check if server is secured
    function checkSecure($isSandbox)
    {
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "on") {
                // set sandbox mode becasue admin is not secured
                $this->setSandbox($postData);
                
                //check if ssl is installed to show the right message
                if (!$this->is_exist_ssl(Mage::getBaseUrl())) return 0;
                else return 2;
            } else {
                if (!$this->checkAllwebsites()) {
                   return 3;
                    }
                if (!Mage::app()->getStore()->isAdminUrlSecure()) {
                return 4;
                    }
            }
            return 1;     
    }
	
	
	private function checkAllwebsites(){
	   	foreach (Mage::app()->getWebsites() as $website) {
	   			if(Mage::app()->getWebsite($website->getId())->getConfig('web/secure/use_in_frontend')!=1) return false;
	   	}
	   	return true;
	}
	
    private function getIsSandbox($postData)
    {
        if (is_null($postData['store']) && $postData['website']) //check for website scope
        {
            $scopeId = Mage::getModel('core/website')->load($postData['website'])->getId();
            $isSnadBox = Mage::app()->getWebsite($scopeId)->getConfig('bluesnap/general/is_sandbox_mode');
        } elseif ($postData['store']) //check for store scope
        {
            $scopeId = Mage::getModel('core/store')->load($postData['store'])->getId();
            $isSnadBox = Mage::app()->getWebsite($scopeId)->getConfig('bluesnap/general/is_sandbox_mode');
        } else //for default scope
        {
            $scopeId = 0;
            $isSnadBox = Mage::getStoreConfig('bluesnap/general/is_sandbox_mode');
        }

        return $isSnadBox;
    }


    private function setSandbox($postData)
    {
        if (is_null($postData['store']) && $postData['website']) //check for website scope
        {
            $scopeId = Mage::getModel('core/website')->load($postData['website'])->getId();
            $currentScope = 'websites';
            
        } elseif ($postData['store']) //check for store scope
        {       
        	
            $scopeId = Mage::getModel('core/store')->load($postData['store'])->getId();
            $currentScope = 'stores';
            
        } else //for default scope
        {
            $scopeId = 0;
            $currentScope = 'default';
        }

        if ($currentScop == 'default') {
         Mage::getConfig()->saveConfig('bluesnap/general/is_sandbox_mode', '1');
         
        }
        else Mage::getConfig()->saveConfig('bluesnap/general/is_sandbox_mode', '1', $currentScope, $scopeId);
    }

    //we check if ssl is installed on server and user is working on non secure page

    private function is_exist_ssl($baseurl)
    {
		$domain=parse_url($baseurl, PHP_URL_HOST);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://" . $domain);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);

        if (!curl_error($ch)) {
            $info = curl_getinfo($ch);
            if ($info['http_code'] == 200) {
                return true;
            }
            return false;
        } else {
            return false;
        }
    }
}