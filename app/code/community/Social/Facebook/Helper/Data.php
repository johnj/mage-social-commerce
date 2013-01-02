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


/**
 * Facebook helper
 *
 * @category   Social
 * @package    Social_Facebook
 */
class Social_Facebook_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
      * Checks whether Facebook module is enabled for frontend in system config
      *
      * @return bool
      */
    public function isEnabled()
    {
        return Mage::getStoreConfigFlag(Social_Facebook_Model_Facebook::XML_PATH_ENABLED);
    }

    /**
      * Get Facebook App Id
      *
      * @return string
      */
    public function getAppId()
    {
        return Mage::getStoreConfig(Social_Facebook_Model_Facebook::XML_PATH_APP_ID);
    }

     /**
      * Get Facebook App Secret
      *
      * @return string
      */
    public function getAppSecret()
    {
        return Mage::getStoreConfig(Social_Facebook_Model_Facebook::XML_PATH_APP_SECRET);
    }

     /**
      * Get Facebook App Name
      *
      * @return string
      */
    public function getAppName()
    {
        return Mage::getStoreConfig(Social_Facebook_Model_Facebook::XML_PATH_APP_NAME);
    }

     /**
      * Get Facebook Object Type (usually product)
      *
      * @return string
      */
    public function getObjectType()
    {
        return Mage::getStoreConfig(Social_Facebook_Model_Facebook::XML_PATH_APP_OBJECT_TYPE);
    }

     /**
      * Get XFabric URL
      *
      * @return string
      */
    public function getXcomFabricURL($sync=false)
    {
        if ($sync) {
            return str_replace('/fabric', '/xbridge/invoke',
                Mage::getStoreConfig(Social_Facebook_Model_Facebook::XML_PATH_FABRIC_URL));
        }
        return Mage::getStoreConfig(Social_Facebook_Model_Facebook::XML_PATH_FABRIC_URL);
    }

     /**
      * Get the capability token
      *
      * @return string
      */
    public function getXcomCapToken()
    {
        return Mage::getStoreConfig(Social_Facebook_Model_Facebook::XML_PATH_CAP_TOKEN);
    }

     /**
      * Get the bearer token
      *
      * @return string
      */
    public function getSCBearerToken()
    {
        return Mage::getStoreConfig(Social_Facebook_Model_Facebook::XML_PATH_BEARER_TOKEN);
    }

     /**
      * Get Facebook Actions
      *
      * @return string
      */
    public function getAllActions()
    {
        static $actions = NULL;

        if($actions) {
            return $actions;
        }

        $actions = Mage::getStoreConfig(Social_Facebook_Model_Facebook::XML_PATH_APP_ACTIONS);
        $actions = unserialize($actions);
        return $actions;
    }

     /**
      * Get Facebook App Friend Count in FriendBox
      *
      * @param string $action
      * @return string
      */
    public function getAppFriendCount($action)
    {
        $count = 0;
        $actions = $this->getAllActions();
        if (!empty($actions)) {
            foreach ($actions as $act) {
                if ($act['action'] == $action) {
                    $count = $act['count'];
                    break;
                }
            }
        }

        if (empty($count)) {
            $count = Social_Facebook_Model_Facebook::XML_PATH_APP_USER_COUNT;
        }
        return $count;
    }

    /**
      * Get Redirect Url fo Facebook Authorization
      *
      * @param Mage_Catalog_Model_Product $product
      * @return string
      */
    public function getRedirectUrl($product)
    {
        return Social_Facebook_Model_Api::URL_GRAPH_DIALOG_OAUTH
            . '?client_id=' . $this->getAppId()
            . '&redirect_uri=' . urlencode($product->getUrlModel()->getUrlInStore($product))
            . '&scope=publish_actions'
            . '&response_type=code';
    }
}
