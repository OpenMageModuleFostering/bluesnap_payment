<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2015 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml Grid Renderer
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Bluesnap_Payment_Block_Adminhtml_Widget_Grid_Column_Renderer_Xml
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Render contents as a long text
     *
     * Text will be truncated as specified in string_limit, truncate or 250 by default
     * Also it can be html-escaped and nl2br()
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $value = parent::_getValue($row);


        $xml = @simplexml_load_string($value);

        if ($xml) {
            $dom = dom_import_simplexml($xml)->ownerDocument;
            $dom->formatOutput = true;

            //  $value=htmlentities($xml->asXML());

            //PEAR
            if (include_once("Text/Highlighter.php")) {
                $hlXml =& Text_Highlighter::factory("XML");
                $value = $hlXml->highlight($dom->saveXML());
            } else {

                $value = htmlentities($dom->saveXML());
                $value = nl2br($value);


            }

            $value = "<div style='max-height:200px;max-width:300px;overflow:auto'>$value</div>";

        } else {
            //$value = nl2br($value); 
            $value = "<div style='max-height:200px;max-width:300px;overflow:auto'><pre>$value</pre></div>";
        }

        return $value;
    }
}
