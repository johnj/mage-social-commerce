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
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
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
     * Cache Friends From Facebook
     *
     * @param object $data
     * @param string $facebookId
     * @return array
     */
    public function cacheFriends($data, $facebookId)
    {
        $name   = 'social_facebook_' . $facebookId;

        $users  = Mage::app()->loadCache($name);

        if (empty($users)) {
            if (empty($data)) {
                return false;
            }
            $users  = array();
            $users[] = $facebookId;
            foreach ($data->data as $user) {
                $users[] = $user->id;
            }

            Mage::app()->saveCache(serialize($users), $name, array(), 3600);
        } else {
            $users = unserialize($users);
        }

        return $users;
    }

    /**
     * Get Linked Facebook Friends
     *
     * @param string $facebookId
     * @param int $productId
     * @param string $action
     * @return array
     */
    public function getLinkedFriends($facebookId, $productId, $action)
    {
        $friends = $this->cacheFriends(array(), $facebookId);
        if (!empty($friends)) {
            return $this->_getResource()->getLinkedFriends($friends, $productId, $action);
        }
        return array();
    }

    /**
     * Get Facebook Api
     *
     * @return Social_Facebook_Model_Api
     */
    public function getApi()
    {
        $session = Mage::getSingleton('core/session');

        return Mage::getSingleton('social_facebook/api')
            ->setProductUrl($session->getData('product_url'))
            ->setFacebookAction($session->getData('facebook_action'))
            ->setProductOgUrl($session->getData('product_og_url'))
            ->setAccessToken($this->_accessToken)
        ;
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

            $facebookCode = Mage::app()->getRequest()->getParam('code');

            if (!empty($facebookCode)) {
                $this->_accessToken = $this->getApi()
                    ->setFacebookCode($facebookCode)
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
            if (!empty($facebookCode)) {
                Mage::app()->getResponse()->setRedirect($productUrl);
                Mage::app()->getResponse()->sendResponse();
                exit();
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
     * @return mixed
     */
    public function sendFacebookAction()
    {
        try {
            if (!$this->getAccessToken()) {
                return false;
            }
            $action =  $this->getApi()->sendFacebookAction();
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        } catch (Exception $e) {
            $action = Mage::getSingleton('core/session')->getData('facebook_action');
            Mage::getSingleton('core/session')->addError(
                 Mage::helper('social_facebook')->__('Cannot Make "%s" Action. Please, try later.', $action)
            );
            Mage::logException($e);
        }

        return $action;
    }

    /**
     * Get Facebook Friends
     *
     * @return mixed
     */
    public function getFacebookFriends()
    {
        try {
            if (!$this->getAccessToken()) {
                return false;
            }
            $friends = $this->getApi()->getFacebookFriends();
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError(
                 Mage::helper('social_facebook')->__('Cannot Get Your Facebook Friends')
            );
            Mage::logException($e);
        }

        return $friends;
    }

    /**
     * Get Facebook User
     *
     * @return mixed
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
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError(
                 Mage::helper('social_facebook')->__('Cannot Get Facebook User')
            );
            Mage::logException($e);
        }

        return $user;
    }
}
