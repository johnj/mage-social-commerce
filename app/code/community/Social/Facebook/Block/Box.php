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
 * @category    Social
 * @package     Social_Facebook
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Social_Facebook_Block_Box extends Mage_Core_Block_Template
{
    /**
     * Block Initialization
     *
     * @return Social_Facebook_Block_Box
     */
    protected function _construct()
    {
        $model = Mage::getSingleton('social_facebook/api');
        $json = $model->getSocialData();

        if (!Mage::helper('social_facebook')->isEnabled()) {
            return;
        }

        if (!is_object($json) || !sizeof(get_object_vars($json->actions))) {
            Mage::helper('social_facebook')->__('The Social Commerce
                extension is enabled but no actions have been setup');
            return;
        }

        parent::_construct();

        $product = Mage::registry('product');

        if ($product) {
            $this->setProductId($product->getId());

            $this->setAllActions(Mage::helper('social_facebook')->getAllActions());

            $this->setFacebookId(Mage::getSingleton('core/session')->getData('facebook_id'));

            $this->setSocialData(Mage::getSingleton('social_facebook/api')->getSocialData());
        }

        $this->setTemplate('social/facebook/box.phtml');

        return $this;
    }

    /**
     * Get Facebook Friend Box By Action
     *
     * @param string $action
     * @return array
     */
    public function getFriendBox($action)
    {
        static $friendInfo = array();

        $currFbId = $this->getFacebookId();
        $json = $this->getSocialData();
        $friends = array();
        $api = Mage::getSingleton('social_facebook/api');
        $accessToken = Mage::getSingleton('core/session')->getAccessToken();

        if (empty($json->actions->$action)) {
            return $friends;
        }

        $friends = $json->actions->$action;

        $idx = 0;
        foreach ($friends as $fbId) {
            unset($friends[$idx++]);
            if (empty($friendInfo[$fbId])) {
                $response = $api->makeFacebookRequest(
                    array('access_token' => $accessToken),
                    Social_Facebook_Model_Api::URL_GRAPH_FACEBOOK_OBJECT_ID . $fbId, Zend_Http_Client::GET);

                $friendInfo[$fbId] = $result = $response[1];
            } else {
                $result = $friendInfo[$fbId];
            }
            $friends[$fbId] = $result->first_name;
        }

        foreach (array_keys($friends) as $fbId) {
            if ($fbId==$currFbId) {
                $friends[$fbId] = 'you';break;
            }
        }
        return $friends;
    }

    /**
     * Get Count of Facebook User
     *
     * @param string $action
     * @return int
     */
    public function getCountOfUsers($action)
    {
        $json = $this->getSocialData();
        if (!empty($json->actions->$action)) {
            return sizeof($json->actions->$action);
        }
    }
}
