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
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Social
 * @package     Social_Facebook
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Social_Facebook_Block_Adminhtml_System_Form_Renderer_Config_RegisterButton
    extends Mage_Adminhtml_Block_System_Config_Form_Field
    implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_template = 'social/facebook/system/config/button.phtml';

    /**
     * Unset scope label and pass further to parent render()
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        // Unset the scope label near the button
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $store = Mage::app()->getStore();
        //
        $postData = array(
            'store_instance_name' => $store->getName(),
            'is_registered'          => false,
        );

        $postData = urlencode(Mage::helper('core')->jsonEncode($postData));

        $client = new Zend_Http_Client(Social_Facebook_Model_Facebook::ONBOARDING_URL_DOMAIN .
            Social_Facebook_Model_Facebook::ONBOARDING_URL, array(
                'maxredirects' => 0,
                'timeout'      => 30));

        $client->setParameterPost('fabric_config_info', $postData);

        $client->request('POST');

        $headers = Zend_Http_Response::extractHeaders($client->getLastResponse());

        $buttonUrl = Social_Facebook_Model_Facebook::ONBOARDING_URL_DOMAIN .
            Social_Facebook_Model_Facebook::ONBOARDING_URL;

        if (!empty($headers['location'])) {
            $buttonUrl = Social_Facebook_Model_Facebook::ONBOARDING_URL_DOMAIN . $headers['location'];
        }

        $originalData = $element->getOriginalData();

        $this->addData(array(
            'button_label' => $originalData['button_label'],
            'html_id' => $element->getHtmlId(),
            'button_url' => trim($buttonUrl),
        ));

        $token = Mage::getStoreConfig(Social_Facebook_Model_Facebook::XML_PATH_CAP_TOKEN);
        if (!empty($token)) {
            $this->setToken($token);
            $this->setFabricUrl(Mage::getStoreConfig(Social_Facebook_Model_Facebook::XML_PATH_FABRIC_URL));
            $this->setTenantName(Mage::getStoreConfig(Social_Facebook_Model_Facebook::XML_PATH_TENANT_NAME));
        }

        return $this->_toHtml();
    }
}
