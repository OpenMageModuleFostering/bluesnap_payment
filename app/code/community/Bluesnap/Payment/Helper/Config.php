<?php

/**
 * Get configuration values
 * Class Bluesnap_Payment_Helper_Config
 */
class Bluesnap_Payment_Helper_Config extends Mage_Core_Helper_Abstract
{
    const XML_PATH_BS_CSE_ACTIVE = 'payment/cse/active';
    const XML_PATH_BS_CSE_COMMENT = 'payment/cse/comment';
    const XML_PATH_BS_CSE_TITLE = 'payment/cse/title';
    const XML_PATH_BS_CSE_KEY = 'bluesnap/cse/cse_public_key';

    const XML_PATH_BS_SAVED_TITLE = 'payment/bluesnap_saved/title';

    const XML_PATH_BS_BUYNOW_ACTIVE = 'payment/buynow/active';
    const XML_PATH_BS_BUYNOW_TITLE = 'payment/buynow/title';
    const XML_PATH_BS_BUYNOW_COMMENT = 'payment/buynow/comment';

    const XML_PATH_BS_BUYNOW_PROTECTION_KEY = 'bluesnap/general/protection_key';

    const XML_PATH_BS_IS_SANDBOX = 'bluesnap/general/is_sandbox_mode';
    const XML_PATH_BS_STORE_ID = 'bluesnap/general/store_id';
    const XML_PATH_BS_CONTRACT_ID = 'bluesnap/general/order_contract_id';
    const XML_PATH_BS_DEBUG_MODE = 'bluesnap/general/is_debug_mode';
    const XML_PATH_BS_API_USERNAME = 'bluesnap/general/api_username';
    const XML_PATH_BS_API_PASSWORD = 'bluesnap/general/api_password';

    const BLUESNAP_DEFAULT_CURRENCY = 'USD';

    const BLUESNAP_DEFAULT_INVOICE_STATUS = 'Pending';
    const BLUESNAP_REVIEW_INVOICE_STATUS = 'Pending Vendor Review';
    const BLUESNAP_APPROVED_INVOICE_STATUS = 'Approved';
    /**
     * @var array locale - language mapper
     */
    public $locales = array(
        'af_ZA' => 'Afrikaans', 'ar_DZ' => 'Arabic', 'ar_EG' => 'Arabic',
        'ar_KW' => 'Arabic', 'ar_MA' => 'Arabic', 'ar_SA' => 'Arabic',
        'az_AZ' => 'Azerbaijani', 'be_BY' => 'Belarusian', 'bg_BG' => 'Bulgarian',
        'bn_BD' => 'Bengali', 'bs_BA' => 'Bosnian', 'ca_ES' => 'Catalan',
        'cs_CZ' => 'Czech', 'cy_GB' => 'Welsh', 'da_DK' => 'Danish',
        'de_AT' => 'German', 'de_CH' => 'German', 'de_DE' => 'German',
        'el_GR' => 'Greek', 'en_AU' => 'English', 'en_CA' => 'English',
        'en_GB' => 'English', 'en_NZ' => 'English', 'en_US' => 'English',
        'es_AR' => 'Spanish', 'es_CO' => 'Spanish', 'es_PA' => 'Spanish',
        'gl_ES' => 'Galician', 'es_CR' => 'Spanish', 'es_ES' => 'Spanish',
        'es_MX' => 'Spanish', 'es_EU' => 'Basque', 'es_PE' => 'Spanish',
        'et_EE' => 'Estonian', 'fa_IR' => 'Persian', 'fi_FI' => 'Finnish',
        'fil_PH' => 'Filipino', 'fr_CA' => 'French', 'fr_FR' => 'French',
        'gu_IN' => 'Gujarati', 'he_IL' => 'Hebrew', 'hi_IN' => 'Hindi',
        'hr_HR' => 'Croatian', 'hu_HU' => 'Hungarian', 'id_ID' => 'Indonesian',
        'is_IS' => 'Icelandic', 'it_CH' => 'Italian', 'it_IT' => 'Italian',
        'ja_JP' => 'Japanese', 'ka_GE' => 'Georgian', 'km_KH' => 'Khmer',
        'ko_KR' => 'Korean', 'lo_LA' => 'Lao', 'lt_LT' => 'Lithuanian',
        'lv_LV' => 'Latvian', 'mk_MK' => 'Macedonian', 'mn_MN' => 'Mongolian',
        'ms_MY' => 'Malaysian', 'nl_NL' => 'Dutch', 'nb_NO' => 'Norwegian',
        'nn_NO' => 'Norwegian Nynorsk', 'pl_PL' => 'Polish', 'pt_BR' => 'Portuguese',
        'pt_PT' => 'Portuguese', 'ro_RO' => 'Romanian', 'ru_RU' => 'Russian',
        'sk_SK' => 'Slovak', 'sl_SI' => 'Slovenian', 'sq_AL' => 'Albanian',
        'sr_RS' => 'Serbian', 'sv_SE' => 'Swedish', 'sw_KE' => 'Swahili',
        'th_TH' => 'Thai', 'tr_TR' => 'Turkish', 'uk_UA' => 'Ukrainian',
        'vi_VN' => 'Vietnamese', 'zh_CN' => 'Chinese', 'zh_HK' => 'Chinese',
        'zh_TW' => 'Chsinese', 'es_CL' => 'Spanich',
        'es_VE' => 'Spanish', 'en_IE' => 'English',
    );
    protected $allowedMethods = array('buynow', 'cse', 'bluesnap_saved');

    /**
     * If BuyNow method is active
     * @param mixed $storeId
     * @return bool
     */
    public function isBuynowActive($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_BS_BUYNOW_ACTIVE, $storeId);
    }

    /**
     * Get Comment for the BuyNow method
     * @param mixed $storeId
     * @return string
     */
    public function getBuynowComment($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_BS_BUYNOW_COMMENT, $storeId);
    }

    /**
     * Get Title for the BuyNow method
     * @param mixed $storeId
     * @return string
     */
    public function getBuynowTitle($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_BS_BUYNOW_TITLE, $storeId);
    }

    /**
     * If sandbox is enabled
     * @param mixed $storeId
     * @return bool
     */
    public function isSandBoxMode($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_BS_IS_SANDBOX, $storeId);
    }

    /**
     * Get BlueSnap Store ID
     *
     * @param int $storeId
     * @return string
     */
    public function getBluesnapStoreId($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_BS_STORE_ID, $storeId);
    }

    /**
     * Get BlueSnap Contract ID to be used as Magento Order product
     *
     * @param int $storeId
     * @return string
     */
    public function getBluesnapBuynowOrderContractId($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_BS_CONTRACT_ID, $storeId);
    }

    /**
     * Get API Debug Mode flag
     *
     * @param int $storeId
     * @return bool
     */
    public function isApiDebugMode($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_BS_DEBUG_MODE, $storeId);
    }

    /**
     * Get BlueSnap API username
     *
     * @param int $storeId
     * @return string
     */
    public function getBluesnapApiUsername($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_BS_API_USERNAME, $storeId);
    }

    /**
     * Get BlueSnap API password
     *
     * @param int $storeId
     * @return string
     */
    public function getBluesnapApiPassword($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_BS_API_PASSWORD, $storeId);
    }

    /**
     * Get BlueSnap BuyNow Data Protection Key
     *
     * @param int $storeId
     * @return string
     */
    public function getDataProtectionKey($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_BS_BUYNOW_PROTECTION_KEY, $storeId);
    }

    /**
     * If CSE method is active
     * @param mixed $storeId
     * @return bool
     */
    public function isCseActive($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_BS_CSE_ACTIVE, $storeId);
    }

    /**
     * Get CSE method Comment
     * @param mixed $storeId
     * @return string
     */
    public function getCseComment($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_BS_CSE_COMMENT, $storeId);
    }

    /**
     * Get CSE method Title
     * @param mixed $storeId
     * @return string
     */
    public function getCseTitle($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_BS_CSE_TITLE, $storeId);
    }

    /**
     * Get CSE saved method Title
     * @param mixed $storeId
     * @return string
     */

    public function getSavedTitle($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_BS_SAVED_TITLE, $storeId);
    }

    /**
     * Get merchants's public key
     * @param mixed $storeId
     * @return string
     */
    public function getPublicKey($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_BS_CSE_KEY, $storeId);
    }

    /**
     * Get list of module payment methods codes
     * @return array
     */
    public function getCodes()
    {
        return $this->allowedMethods;
    }

    /**
     * Get array of suported currencies
     * @return array
     */
    public function getStoreCurrencies()
    {
        $currencies = Mage::getStoreConfig('currency/options/allow');
        return explode(',', $currencies);
    }
}
