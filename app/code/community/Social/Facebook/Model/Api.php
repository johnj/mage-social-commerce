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
class Social_Facebook_Model_Api extends Varien_Object
{
    const URL_GRAPH_DIALOG_OAUTH        = 'http://www.facebook.com/dialog/oauth';
    const URL_GRAPH_OAUTH_ACCESS_TOKEN  = 'https://graph.facebook.com/oauth/access_token';
    const URL_GRAPH_FACEBOOK_ABOUT_ME   = 'https://graph.facebook.com/me/';
    const URL_GRAPH_FACEBOOK_ME_FRIENDS = 'https://graph.facebook.com/me/friends';
    const URL_GRAPH_FACEBOOK_OBJECT_ID = 'https://graph.facebook.com/';

    protected $_accessToken     = false;
    protected $_productUrl      = false;
    protected $_facebookCode    = false;
    protected $_facebookAction  = false;
    protected $_productOgUrl    = false;
    private $_xcom              = false;
    private $_xcom_sync         = false;
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

    public function getSchemaLocation($schema) {
        return dirname(__FILE__) . "/../etc/" . $schema;
    }

    /**
     * send message to Xfabric
     *
     * @param object $params
     * @throws Mage_Core_Exception
     * @return boolean
     */
    public function makeXcomRequest($topic, $object, $schema_file, $sync=false, $debug=false)
    {
        $http_code = 0;
        $xcom = NULL;
        try {
            if(!class_exists('Xcom')) {
                Mage::throwException(Mage::helper('social_facebook')->__('The Xcom class is not available, please install and enable the X.commerce PHP5 extension'));
            }

            if(!$sync) {
                $xcom = $this->getXcom();
            } else {
                $xcom = $this->getXcomSync();
            }

            if($debug) { $xcom->__debug = true; }

            $file_location = $this->getSchemaLocation($schema_file);

            if(!is_readable($file_location)) {
                Mage::throwException(Mage::helper('social_facebook')->__('Unable to load schema file: ') . $file_location);
            }

            $http_code = $xcom->send($topic, $object, file_get_contents($file_location), '1.0');
        } catch (Exception $e) {
            Mage::logException($e);
        }

        if (empty($http_code) || $http_code!=200) {
            Mage::throwException(Mage::helper('social_facebook')->__('Error sending message to fabric, HTTP CODE: ' . $http_code));
        }

        return $http_code;
    }

    public function getXcomSync() {
        if(empty($this->_xcom_sync)) {
            $this->_xcom_sync = new Xcom(Mage::helper('social_facebook')->getXcomFabricURL(true), '', Mage::helper('social_facebook')->getXcomCapToken());
        }
        return $this->_xcom_sync;
    }

    public function getXcom() {
        if(empty($this->_xcom)) {
            $this->_xcom = new Xcom(Mage::helper('social_facebook')->getXcomFabricURL(), '', Mage::helper('social_facebook')->getXcomCapToken());
        }
        return $this->_xcom;
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
            if($result->error->code=="3501") {
                 /*
                  *user already associated with this action/product 
                  */
                $m = array();
                preg_match('/Original Action ID: (\d+)/i', $result->error->message, $m);
                return $m[1];
            }
            Mage::throwException(Mage::helper('social_facebook')->__('Facebook error: ') . $result->error->message);
        }

        return array($response, $result);
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

        list($response, $result) = $this->makeFacebookRequest(
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
        parse_str($result, $params);
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
        static $_authInfo = false;

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

        return $_authInfo;
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

        list($response, $result) = $this->makeFacebookRequest(
            array(),
            Social_Facebook_Model_Api::URL_GRAPH_FACEBOOK_ABOUT_ME . $appName . ':' . $this->_facebookAction . '?access_token=' . urlencode($this->_accessToken) . "&$objectType=". urlencode($this->_productOgUrl),
            Zend_Http_Client::POST
        );

        if(!is_object($response)) {
            $action = new stdClass();
            $action->id = $response;
            return $action;
        }

        return $result;
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

        list($response, $result) = $this->makeFacebookRequest(
            array('access_token' => $this->_accessToken),
            Social_Facebook_Model_Api::URL_GRAPH_FACEBOOK_ME_FRIENDS,
            Zend_Http_Client::GET
        );

        return $result;
    }

    public function setSocialData($json) {
        $this->_social_data = $json;
    }

    public function getSocialData() {
        return $this->_social_data;
    }
}
