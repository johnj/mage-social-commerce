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
class Social_Facebook_Model_Api extends Varien_Object
{
    const URL_GRAPH_DIALOG_OAUTH        = 'http://www.facebook.com/dialog/oauth';
    const URL_GRAPH_OAUTH_ACCESS_TOKEN  = 'https://graph.facebook.com/oauth/access_token';
    const URL_GRAPH_FACEBOOK_ABOUT_ME   = 'https://graph.facebook.com/me/';
    const URL_GRAPH_FACEBOOK_ME_FRIENDS = 'https://graph.facebook.com/me/friends';

    protected $_accessToken     = false;
    protected $_productUrl      = false;
    protected $_facebookCode    = false;
    protected $_facebookAction  = false;
    protected $_productOgUrl    = false;


    /**
     * Set Product Url
     *
     * @param string $productUrl
     * @return Social_Facebook_Model_Api
     */
    public function setProductUrl($productUrl)
    {
        $this->_productUrl = $productUrl;
        return $this;
    }

    /**
     * Set Facebook Code
     *
     * @param string $facebookCode
     * @return Social_Facebook_Model_Api
     */
    public function setFacebookCode($facebookCode)
    {
        $this->_facebookCode = $facebookCode;
        return $this;
    }

    /**
     * Set Facebook Action
     *
     * @param string $facebookAction
     * @return Social_Facebook_Model_Api
     */
    public function setFacebookAction($facebookAction)
    {
        $this->_facebookAction = $facebookAction;
        return $this;
    }

    /**
     * Set Product Og Url
     *
     * @param string $productOgUrl
     * @return Social_Facebook_Model_Api
     */
    public function setProductOgUrl($productOgUrl)
    {
        $this->_productOgUrl = $productOgUrl;
        return $this;
    }

    /**
     * Set Facebook Access Token
     *
     * @param string $accessToken
     * @return Social_Facebook_Model_Api
     */
    public function setAccessToken($accessToken)
    {
        $this->_accessToken = $accessToken;
        return $this;
    }

    /**
     * Make Request To Facebook
     *
     * @param array $params
     * @param string $uri
     * @param string $method
     * @throws Mage_Core_Exception
     * @return Zend_Http_Response
     */
    public function makeFacebookRequest($params, $uri, $method)
    {
        try {
            $client = new Varien_Http_Client();

            $client->setUri($uri);
            $client->setConfig(array(
                'maxredirects'  => 5,
                'timeout'       => 30,
            ));
            $client->setParameterGet($params);
            $client->setMethod($method);

            $response   = $client->request();
            $result     = json_decode($response->getBody());
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('social_facebook')->__('Facebook Request API Error'));
        }

        if (!empty($result) && (!empty($result->error) || !empty($result->error->message))) {
            Mage::throwException(Mage::helper('social_facebook')->__('Facebook error: ') . $result->error->message);
        }

        return $client->request();
    }

    /**
     * Get Access Token
     *
     * @return mixed
     */
    public function getAccessToken()
    {
        if (empty($this->_facebookCode)) {
            return false;
        }

        $response = $this->makeFacebookRequest(
            array(
                'client_id'     => Mage::helper('social_facebook')->getAppId(),
                'redirect_uri'  => $this->_productUrl,
                'client_secret' => Mage::helper('social_facebook')->getAppSecret(),
                'code'          => $this->_facebookCode
            ),
            Social_Facebook_Model_Api::URL_GRAPH_OAUTH_ACCESS_TOKEN,
            Zend_Http_Client::GET
        );

        // remove the @expires
        $params = null;
        parse_str($response->getBody(), $params);
        $this->_accessToken = $params['access_token'];

        return $this->_accessToken;
    }

    /**
     * Get Facebook User
     *
     * @return mixed
     */
    public function getFacebookUser()
    {
        if (empty($this->_accessToken)) {
            $this->getAccessToken();
        }

        if (empty($this->_accessToken)) {
            return false;
        }

        $response = $this->makeFacebookRequest(
            array('access_token' => $this->_accessToken),
            Social_Facebook_Model_Api::URL_GRAPH_FACEBOOK_ABOUT_ME,
            Zend_Http_Client::GET
        );

        $result = json_decode($response->getBody());

        return array(
            'facebook_id'   => $result->id,
            'facebook_name' => $result->name
        );
    }

    /**
     * Send Facebook Action
     *
     * @return mixed
     */
    public function sendFacebookAction()
    {
        if (empty($this->_accessToken)) {
            $this->getAccessToken();
        }

        $appName    = Mage::helper('social_facebook')->getAppName();
        $objectType = Mage::helper('social_facebook')->getObjectType();

        if (empty($this->_accessToken) || empty($this->_productOgUrl) || empty($appName) || empty($objectType)) {
            return false;
        }

        $response = $this->makeFacebookRequest(
            array(
                'access_token'  => $this->_accessToken,
                $objectType     => $this->_productOgUrl
            ),
            Social_Facebook_Model_Api::URL_GRAPH_FACEBOOK_ABOUT_ME . $appName . ':' . $this->_facebookAction,
            Zend_Http_Client::POST
        );

        return json_decode($response->getBody());
    }

    /**
     * Get Facebook Friends
     *
     * @return mixed
     */
    public function getFacebookFriends()
    {
        if (empty($this->_accessToken)) {
            $this->getAccessToken();
        }

        if (empty($this->_accessToken)) {
            return false;
        }

        $response = $this->makeFacebookRequest(
            array('access_token' => $this->_accessToken),
            Social_Facebook_Model_Api::URL_GRAPH_FACEBOOK_ME_FRIENDS,
            Zend_Http_Client::GET
        );

        return json_decode($response->getBody());
    }
}
