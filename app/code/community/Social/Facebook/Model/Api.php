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

class Social_Facebook_Model_Api extends Varien_Object
{
    const URL_GRAPH_DIALOG_OAUTH        = 'http://www.facebook.com/dialog/oauth';
    const URL_GRAPH_OAUTH_ACCESS_TOKEN  = 'https://graph.facebook.com/oauth/access_token';
    const URL_GRAPH_FACEBOOK_ABOUT_ME   = 'https://graph.facebook.com/me/';
    const URL_GRAPH_FACEBOOK_ME_FRIENDS = 'https://graph.facebook.com/me/friends';
    const URL_GRAPH_FACEBOOK_OBJECT_ID  = 'https://graph.facebook.com/';
    const URL_SC_API_SERVER             = 'https://sc.jawed.name/xcomsocialcom/';
    const FB_ERR_ACTION_EXISTS          = 3501;
    const HTTP_OK                       = 200;

    protected $_accessToken     = false;
    protected $_productUrl      = false;
    protected $_facebookCode    = false;
    protected $_facebookAction  = false;
    protected $_productOgUrl    = false;
    protected $_socialData      = false;
    private $_Xcom              = false;
    private $_XcomSync          = false;
    private $_social_data       = NULL;


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
     * Get the local schema location
     *
     * @param string $schema
     * @return string
     */
    public function getSchemaLocation($schema)
    {
        return Mage::getModuleDir('etc', 'Social_Facebook') . '/' . $schema;
    }

    /**
     * send message to SC
     *
     * @param object $params
     * @throws Mage_Core_Exception
     * @return int
     */
    public function makeXcomRequest($topic, $json)
    {
        try {
            $client = new Varien_Http_Client();

            $client->setUri(self::URL_SC_API_SERVER . $topic);
            $client->setConfig(array(
                        'adapter' => 'Zend_Http_Client_Adapter_Curl',
                        'timeout'       => 4,
                        'curloptions' => array(CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_CAINFO => Mage::getModuleDir('etc', 'Social_Facebook') . '/cainfo.crt'),
                        ));
            $client->setMethod('POST');
            $client->setParameterPost('social', $json);
            $client->setHeaders('Authorization', Mage::helper('social_facebook')->getSCBearerToken());

            $response   = $client->request();
            $result     = Mage::helper('core')->jsonDecode($response->getBody(), Zend_Json::TYPE_OBJECT);
        } catch (Exception $e) {
            Mage::logException($e);
            $result = $response->getBody();
        }

        if ($response->getStatus()!=self::HTTP_OK) {
            try {
                Mage::throwException(Mage::helper('social_facebook')->__( 'Error sending message to the Social Commerce API, HTTP CODE: %s', $response->getStatus()));
            } catch(Exception $e) {
                Mage::logException($e);
            }
        }

        return array($response->getStatus(), $result);
    }

    /**
     * Get a sync instance of Xcom
     *
     * @return Xcom
     */
    public function getXcomSync()
    {
        if (empty($this->_XcomSync)) {
            $this->_XcomSync = new Xcom(Mage::helper('social_facebook')->getXcomFabricURL(true), '',
                Mage::helper('social_facebook')->getXcomCapToken());
        }
        return $this->_XcomSync;
    }

    /**
     * Get an async instance of Xcom
     *
     * @return Xcom
     */
    public function getXcom()
    {
        if (empty($this->_Xcom)) {
            $this->_Xcom = new Xcom(Mage::helper('social_facebook')->getXcomFabricURL(), '',
                Mage::helper('social_facebook')->getXcomCapToken());
        }
        return $this->_Xcom;
    }

    /**
     * Make Request To Facebook
     *
     * @param array $params
     * @param string $uri
     * @param string $method
     * @throws Mage_Core_Exception
     * @return array | string
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
            try {
                $result     = Mage::helper('core')->jsonDecode($response->getBody(), Zend_Json::TYPE_OBJECT);
            } catch(Zend_Json_Exception $e) {
                /* failed to decode, that's fine, we'll pass through */
                $result = $response->getBody();
            }
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('social_facebook')->__('Facebook Request API Error'));
        }

        if (!empty($result) && (!empty($result->error) || !empty($result->error->message))) {
            if($result->error->code==self::FB_ERR_ACTION_EXISTS) {
                /*
                 * user already associated with this action/product 
                 */
                $matches = array();
                preg_match('/Original Action ID: (\d+)/i', $result->error->message, $matches);
                if (!empty($matches)) {
                    return $matches[1];
                }
            }
            Mage::throwException(Mage::helper('social_facebook')->__('Facebook error: ') . $result->error->message);
        }

        return array($response, $result);
    }

    /**
     * Get Access Token
     *
     * @return string | false
     */
    public function getAccessToken()
    {
        if (empty($this->_facebookCode)) {
            return false;
        }

        if (!empty($this->_accessToken)) {
            return $this->_accessToken;
        }

        $result = $this->makeFacebookRequest(
            array(
                'client_id'     => Mage::helper('social_facebook')->getAppId(),
                'redirect_uri'  => $this->_productUrl,
                'client_secret' => Mage::helper('social_facebook')->getAppSecret(),
                'code'          => $this->_facebookCode
            ),
            Social_Facebook_Model_Api::URL_GRAPH_OAUTH_ACCESS_TOKEN,
            Zend_Http_Client::GET
        );

        $params = null;
        parse_str($result[1], $params);

        if (empty($params['access_token'])) {
            return false;
        }

        $this->_accessToken = $params['access_token'];

        return $this->_accessToken;
    }

    /**
     * Get Facebook User
     *
     * @return array | false
     */
    public function getFacebookUser()
    {
        $_authInfo = Mage::getSingleton('social_facebook/facebook')->getFacebookAuthInfo();

        if(!empty($_authInfo)) {
            return $_authInfo;
        }

        if (empty($this->_accessToken)) {
            $this->getAccessToken();
        }

        if (empty($this->_accessToken)) {
            return false;
        }

        list($response, $result) = $this->makeFacebookRequest(
            array('access_token' => $this->_accessToken),
            Social_Facebook_Model_Api::URL_GRAPH_FACEBOOK_ABOUT_ME,
            Zend_Http_Client::GET
        );

        $_authInfo = array(
            'facebook_id'   => $result->id,
            'facebook_name' => $result->name
        );

        Mage::getSingleton('social_facebook/facebook')->setFacebookAuthInfo($_authInfo);

        return $_authInfo;
    }

    /**
     * Send Facebook Action
     *
     * @return array | false
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

        $url = $appName . ':' . $this->_facebookAction;

        if(strtolower($this->_facebookAction)=='like') {
            $objectType = 'object';
            $url = 'og.likes';
        }

        list($response, $result) = $this->makeFacebookRequest(
            array(),
                    Social_Facebook_Model_Api::URL_GRAPH_FACEBOOK_ABOUT_ME . $url
                    . '?access_token=' . urlencode($this->_accessToken)
                    . "&$objectType=". urlencode($this->_productOgUrl),
            Zend_Http_Client::POST
        );

        if (!is_object($response)) {
            $action = new stdClass();
            $action->id = $response;
            return $action;
        }

        return $result;
    }

    /**
     * Get Facebook Friends
     *
     * @return array | false
     */
    public function getFacebookFriends()
    {
        if (empty($this->_accessToken)) {
            $this->getAccessToken();
        }

        if (empty($this->_accessToken)) {
            return false;
        }

        list($response, $result) = $this->makeFacebookRequest(
            array('access_token' => $this->_accessToken),
            Social_Facebook_Model_Api::URL_GRAPH_FACEBOOK_ME_FRIENDS,
            Zend_Http_Client::GET
        );

        return $result;
    }

    /**
     * Set Social Data
     *
     * @return void
     */
    public function setSocialData($json)
    {
        $this->_socialData = $json;
    }

    /**
     * Get Social Data
     *
     * @return stdClass
     */
    public function getSocialData()
    {
        return $this->_socialData;
    }

    /**
     * Fetch Social Data
     *
     * @return stdClass | false
     */
    public function fetchSocialData($productData, $categoryData) {
        $dataObj = new stdClass();

        $data = array('actions' => Mage::helper('social_facebook')->getAllActions(),
            'url' => Mage::app()->getStore()->getCurrentUrl(false));

        $facebookModel  = Mage::getSingleton('social_facebook/facebook');
        $session = Mage::getSingleton('core/session');
        $accessToken = $session->getData('access_token');

        if (!empty($accessToken)) {
            $user = $facebookModel->getFacebookUser();
            if (!empty($user["facebook_id"])) {
                $data['fb_uid'] = $user["facebook_id"];
                $data['friends'] = $facebookModel->getFriendsForUser($user["facebook_id"]);
            }
        }

        $data['actions'] = array();

        $actions = Mage::helper('social_facebook')->getAllActions();

        if (empty($actions)) {
            Mage::throwException(Mage::helper('social_facebook')->__('[error] no social commerce
                actions have been setup, please setup some actions in the Social Commerce
                configuration'));
        }

        foreach ($actions as $action) {
            $data['actions'][$action['action']] = array('info' => $action,
                'limit' => Mage::helper('social_facebook')->getAppFriendCount($action['action']));
        }

        $data['product_info'] = array('product' => $productData, 'category' => $categoryData);

        $dataObj = Mage::helper('core')->jsonEncode($data);

        list($rc, $response) = $this->makeXcomRequest('/social/events/product/fetch', $dataObj);

        $json = Mage::helper('core')->jsonDecode($response, Zend_Json::TYPE_OBJECT);

        if (empty($json)) {
            return false;
        }

        $this->setSocialData($json);

        return $json;
    }
}
