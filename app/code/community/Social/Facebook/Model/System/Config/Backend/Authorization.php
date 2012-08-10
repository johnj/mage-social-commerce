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
class Social_Facebook_Model_System_Config_Backend_Authorization extends Mage_Core_Model_Config_Data
{

    /**
     * Workaround to disable "delete" checkbox near uploaded file
     * @todo going to be removed after adding functionality to revoke authorization data
     *
     * @return bool
     */
    public function getValue()
    {
        return false;
    }

    /**
     * Take actions to process and save submitted authorization file
     *
     * @return Social_Facebook_Model_System_Config_Backend_Authorization
     */
    protected function _beforeSave()
    {
        if (!isset($_FILES['groups']['tmp_name'][$this->getGroupId()]['fields'][$this->getField()]['value'])) {
            return $this;
        }

        $tmpPath = $_FILES['groups']['tmp_name'][$this->getGroupId()]['fields'][$this->getField()]['value'];

        if ($tmpPath && file_exists($tmpPath)) {
            if (!filesize($tmpPath)) {
                Mage::throwException(Mage::helper('xcom_xfabric')->__('XFabric authorization file is empty.'));
            }

            try {
                $json = file_get_contents($tmpPath);
                $auth = Mage::helper('core')->jsonDecode($json, Zend_Json::TYPE_OBJECT);
                if (empty($auth->authorizations)) {
                    Mage::throwException('Invalid authorization file!');
                }
                foreach ($auth->authorizations as $tokenInfo) {
                    if (strtolower($tokenInfo->type)!='tenant') {
                        continue;
                    }
                    Mage::getConfig()->saveConfig(Social_Facebook_Model_Facebook::XML_PATH_FABRIC_URL,
                        $auth->fabricUrl, 'default', 0);
                    Mage::getConfig()->saveConfig(Social_Facebook_Model_Facebook::XML_PATH_CAP_TOKEN,
                        $tokenInfo->bearerToken, 'default', 0);
                    Mage::getConfig()->saveConfig(Social_Facebook_Model_Facebook::XML_PATH_TENANT_NAME,
                        $tokenInfo->tenantName, 'default', 0);
                    break;
                }
                Mage::getConfig()->saveConfig(Social_Facebook_Model_Facebook::XML_PATH_RAW_AUTH_JSON, $json, 'default', 0);
                Mage::getConfig()->cleanCache();
            } catch (Mage_Core_Exception $e) {
                Mage::throwException($e->getMessage());
            }

            unlink($tmpPath);
        }

        return $this;
    }
}
