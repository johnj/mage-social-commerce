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

class Social_Facebook_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
     * Action For Facebook Action Redirect
     */
    public function redirectAction()
    {
        $link = $this->_facebookRedirect();
        if (!$link) {
            return;
        }

        $this->_redirectUrl($link);
        return;
    }

    /**
     * Get Facebook Redirect For Current Action
     *
     * @return string
     */
    private function _facebookRedirect()
    {
        $session    = Mage::getSingleton('core/session');
        $action     = $this->getRequest()->getParam('action');
        $productId  = $this->getRequest()->getParam('productId');
        $product    = Mage::getModel('catalog/product')->load($productId);
        $productUrl = $product->getUrlModel()->getUrlInStore($product);

        $session->setData('product_id', $productId);
        $session->setData('product_url', $productUrl);
        $session->setData('product_og_url', Mage::getUrl('facebook/index/page', array('id' => $productId)));
        $session->setData('facebook_action', $action);

        if ($session->getData('access_token') && $this->_checkAnswer() && $action) {
            $this->_redirectUrl($productUrl);
            return;
        }

        return Mage::helper('social_facebook')->getRedirectUrl($product);
    }

    /**
     * Check is Facebook Token Alive
     *
     * @return bool
     */
    protected function _checkAnswer()
    {
        if (Mage::getSingleton('social_facebook/facebook')->getFacebookUser()) {
            return true;
        }

        return false;
    }

    /**
     * Get Meta tags for Facebook
     */
    public function pageAction()
    {
        $productId = (int)$this->getRequest()->getParam('id');
        if ($productId) {
            $product = Mage::getModel('catalog/product')->load($productId);

            if ($product->getId()) {
                Mage::register('product', $product);
            }

            $this->loadLayout();
            $response = $this->getLayout()->createBlock('social_facebook/head')->toHtml();
            $this->getResponse()->setBody($response);
        }
    }

    /**
     * get JSON for product/social info
     */
    public function widgetAction()
    {
        if (!Mage::helper('social_facebook')->isEnabled()) {
            return;
        }

        if (!Mage::helper('social_facebook')->getSCBearerToken()) {
            $this->getResponse()->setBody(Mage::helper('social_facebook')->__("
                <strong>[error]</strong> you've enabled the Social Commerce extension
                but haven't set a Social Commerce App Secret yet!"));
            return;
        }

        $productId = (int)$this->getRequest()->getParam('id');

        if ($productId) {
            $product = Mage::getModel('catalog/product')->load($productId);
            if ($product->getId()) {
                Mage::register('product', $product);
            }
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
        } else {
            return;
        }

        $api = Mage::getSingleton('social_facebook/api');

        try {
            $api->fetchSocialData($productData, $categoryData);
            $this->loadLayout();

            $block = $this->getLayout()->createBlock('social_facebook/socialdata');

            if (!empty($block)) {
                $response = $block->toHtml();
                $this->getResponse()->setBody($response);
            }
        } catch(Exception $e) {
            $this->getResponse()->setBody($e->getMessage());
        }
    }
}
