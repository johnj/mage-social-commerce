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
 * Facebook model
 *
 * @category   Social
 * @package    Social_Facebook
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Social_Facebook_Model_Facebook extends Mage_Core_Model_Abstract
{
    /**
     * XML configuration paths
     */
    const XML_PATH_SECTION_FACEBOOK     = 'facebook/config';
    const XML_PATH_ENABLED              = 'facebook/config/enabled';
    const XML_PATH_APP_ID               = 'facebook/config/id';
    const XML_PATH_APP_SECRET           = 'facebook/config/secret';
    const XML_PATH_APP_NAME             = 'facebook/config/name';
    const XML_PATH_APP_OBJECT_TYPE      = 'facebook/config/otype';
    const XML_PATH_APP_USER_COUNT       = 3;
    const XML_PATH_APP_ACTIONS          = 'facebook/config/action';
    const XML_PATH_FABRIC_URL           = 'facebook/config/fabric_url';
    const XML_PATH_CAP_TOKEN            = 'facebook/config/cap_token';
    const XML_PATH_BEARER_TOKEN         = 'facebook/config/bearer_token';
    const XML_PATH_TENANT_NAME          = 'facebook/config/tenant_name';
    const XML_PATH_RAW_AUTH_JSON        = 'facebook/config/raw_json_auth';
    const XML_PATH_REGISTER_BUTTON      = 'facebook/config/registration_extension_button';
    const ONBOARDING_URL                = '/merchant_onboarding';
    const ONBOARDING_URL_DOMAIN         = 'https://devportal.x.com';
    const NEW_EVENT_TOPIC               = '/social/events/product/new';

    protected $_accessToken     = false;

    /**
     * Init resource model
     */
    protected function _construct()
    {
        $this->_init('social_facebook/facebook');
        parent::_construct();
    }

    /**
     * Load User by Action and Facebook Id
     *
     * @param int $action
     * @param int $id
     * @param int $productId
     *
     * @return Social_Facebook_Model_Facebook
     */
    public function loadUserByActionId($action, $id, $productId)
    {
        return $this->_getResource()->loadUserByActionId($action, $id, $productId);
    }

    /**
     * Get Count of all Users by Action, Product Id
     *
     * @param int $action
     * @param int $productId
     *
     * @return int
     */
    public function getCountByActionProduct($action, $productId)
    {
        return $this->_getResource()->getCountByActionProduct($action, $productId);
    }

    /**
     * Get Count of all Users by Product Id
     *
     * @param int $productId
     *
     * @return int
     */
    public function getCountByProduct($productId)
    {
        return $this->_getResource()->getCountByProduct($productId);
    }

    /**
     * Get cache key name
     *
     * @param string $facebookId
     * @return string
     */
    protected function getFriendsCacheKey($facebookId)
    {
        return 'social_facebook_' . $facebookId;
    }

    /**
     * Get friends from cache
     *
     * @param string $facebookId
     * @return array | false
     */
    protected function getFriends($facebookId)
    {
        $users  = Mage::app()->loadCache($this->getFriendsCacheKey($facebookId));
        if(empty($users)) {
            return false;
        }
        return unserialize($users);
    }

    
    /**
     * Set friend info in cache
     *
     * @param object $data
     * @param string $facebookId
     * @return array
     */
    protected function cacheFriends($data, $facebookId)
    {
        if (empty($data)) {
            return false;
        }

        $users  = array();
        $users[] = $facebookId;
        foreach ($data->data as $user) {
            $users[] = $user->id;
        }

        Mage::app()->saveCache(serialize($users), $this->getFriendsCacheKey($facebookId), array(), 3600);
        return $users;
    }

    /**
     * Get Facebook Api
     *
     * @return Social_Facebook_Model_Api
     */
    public function getApi($exchangingToken=false)
    {
        $api = Mage::getSingleton('social_facebook/api');
        $apiSession = $api->getSessionApi();

        if(empty($apiSession)) {
            $session = Mage::getSingleton('core/session');

            $apiSession = Mage::getSingleton('social_facebook/api')
                ->setProductUrl($session->getData('product_url'))
                ->setFacebookAction($session->getData('facebook_action'))
                ->setProductOgUrl($session->getData('product_og_url'))
                ->setAccessToken($exchangingToken ? '' : $this->getAccessToken());

            $api->setSessionApi($apiSession);
        }
        return $apiSession;
    }

    /**
     * Facebook Api Remove Session
     *
     * @return Social_Facebook_Model_Facebook
     */
    public function removeSessionApi()
    {
        Mage::getSingleton('core/session')
            ->unsetData('product_url')
            ->unsetData('facebook_action')
            ->unsetData('product_og_url')
            ->unsetData('access_token')
            ->unsetData('facebook_id')
        ;

        $this->_accessToken = false;

        return $this;
    }

    /**
     * Get Access Token
     *
     * @return mixed
     */
    public function getAccessToken()
    {
        if (!empty($this->_accessToken)) {
            return $this->_accessToken;
        }

        try {
            $session = Mage::getSingleton('core/session');

            $productUrl         = $session->getData('product_url');
            $this->_accessToken = $session->getData('access_token');

            if (!empty($this->_accessToken) && $this->getApi()->getFacebookUser()) {
                return $this->_accessToken;
            } else {
                $session->unsetData('access_token');
            }

            $accessCode = Mage::app()->getRequest()->getParam('code');

            if (!empty($accessCode)) {
                $this->_accessToken = $this->getApi(true)
                    ->setFacebookCode($accessCode)
                    ->getAccessToken()
                ;
            }
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError(
                 Mage::helper('social_facebook')->__('Cannot Get Facebook Access Token')
            );
            Mage::logException($e);
        }

        if (!empty($this->_accessToken)) {
            $session->setData('access_token', $this->_accessToken);
            if (!empty($accessCode)) {
                Mage::app()->getResponse()->setRedirect($productUrl);
                Mage::app()->getResponse()->sendResponse();
                return false;
            }
            return $this->_accessToken;
        } else {
            $session->unsetData('access_token');
            $session->unsetData('facebook_action');
            return false;
        }
    }

    /**
     * Send Facebook Action
     *
     * @return array | false
     */
    public function sendFacebookAction($fbUid, $productId)
    {
        try {
            if (!$this->getAccessToken()) {
                return false;
            }

            $action = $this->getApi()->sendFacebookAction();
            $session = Mage::getSingleton('core/session');

            $product = Mage::getModel('catalog/product')->load($productId);
            $productData = $product->getData();

            $categories = $product->getCategoryIds();
            $categoryData = array();

            if (!empty($categories)) {
                foreach ($categories as $categoryId) {
                    $category = Mage::getModel('catalog/category')->load($categoryId);
                    $categoryData[] = $category->getData();
                }
            }

            $merchantInfo = Mage::app()->getStore();

            $merchantData = new stdClass();
            $merchantData->current_url = $merchantInfo->getCurrentUrl();
            $merchantData->current_url_from_store = $merchantInfo->getCurrentUrl(true);
            $merchantData->frontend_name = $merchantInfo->getFrontendName();
            $merchantData->is_active = $merchantInfo->getIsActive();
            $merchantData->website = $merchantInfo->getWebsite();
            $merchantData->is_admin = $merchantInfo->isAdmin();

            $eventInfo = new stdClass();
            $eventInfo->app_name = Mage::helper('social_facebook')->getAppName();
            $eventInfo->object_type = Mage::helper('social_facebook')->getObjectType();
            $eventInfo->product_url = $session->getData('product_url');
            $eventInfo->action = $session->getData('facebook_action');
            $eventInfo->og_product_url = $session->getData('product_og_url');
            $eventInfo->fb_uid = $session->getData('facebook_id');
            $eventInfo->fb_action_id = $action->id;

            $productData["product_categories"] = $categoryData;

            $response = $this->getApi()->makeFacebookRequest(
                array('access_token' => $this->getAccessToken()),
                Social_Facebook_Model_Api::URL_GRAPH_FACEBOOK_OBJECT_ID . (string)$action->id, Zend_Http_Client::GET);

            $actionInfo = $response[1];

            $response = $this->getApi()->makeFacebookRequest(
                array('access_token' => $this->getAccessToken()),
                Social_Facebook_Model_Api::URL_GRAPH_FACEBOOK_ME_FRIENDS, Zend_Http_Client::GET);

            $friends = $response[1];

            $eventInfo->fb_action_info = array('fb' => Mage::helper('core')->jsonEncode($actionInfo),
                'actions' => Mage::helper('social_facebook')->getAllActions(),
                'friends' => Mage::helper('core')->jsonEncode($friends));

            $dataObj = new stdClass();
            $dataObj->product_info = Mage::helper('core')->jsonEncode($productData);
            $dataObj->merchant_info = Mage::helper('core')->jsonEncode($merchantData);
            $dataObj->event_info = Mage::helper('core')->jsonEncode($eventInfo);

	    $data = Mage::helper('core')->jsonEncode($dataObj);

            $this->getApi()->makeXcomRequest(self::NEW_EVENT_TOPIC, $data);
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            return false;
        } catch (Exception $e) {
            $action = Mage::getSingleton('core/session')->getData('facebook_action');
            Mage::getSingleton('core/session')->addError(
                 Mage::helper('social_facebook')->__('Could not create the "%s" action...can you try again?', $action)
            );
            Mage::logException($e);
            return false;
        }

        return $action;
    }

    /**
     * Get Facebook Friends
     *
     * @return array | false
     */
    protected function getFacebookFriends()
    {
        try {
            if (!$this->getAccessToken()) {
                return false;
            }
            $friends = $this->getApi()->getFacebookFriends();
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            return false;
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError(
                 Mage::helper('social_facebook')->__('Cannot Get Your Facebook Friends')
            );
            Mage::logException($e);
            return false;
        }

        return $friends;
    }

    /**
     * Get Facebook User
     *
     * @return array | false
     */
    public function getFacebookUser()
    {
        try {
            if (!$this->getAccessToken()) {
                return false;
            }

            $user = $this->getApi()->getFacebookUser();
            if ($user) {
                Mage::getSingleton('core/session')->setData('facebook_id', $user['facebook_id']);
            }
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            return false;
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError(
                 Mage::helper('social_facebook')->__('Cannot Get Facebook User')
            );
            Mage::logException($e);
            return false;
        }

        return $user;
    }

    /**
     * Get Facebook Friends
     *
     * @return array
     */
    public function getFriendsForUser($facebookId)
    {
        $users  = $this->getFriends($facebookId);
        if (empty($users)) {
            $result = $this->getFacebookFriends();
            if (!empty($result)) {
                return $this->cacheFriends($result, $facebookId);
            }
        }
        return $users;
    }
}
