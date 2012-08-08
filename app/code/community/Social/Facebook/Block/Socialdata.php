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

class Social_Facebook_Block_Socialdata extends Mage_Core_Block_Template
{
    private $_count;

    /**
     * Block Initialization
     *
     * @return Social_Facebook_Block_Head
     */
    protected function _construct()
    {
        $helper = Mage::helper('social_facebook');
        $api = Mage::getSingleton('social_facebook/api');
        $this->_count = 0;

        if (!$helper->isEnabled()) {
            return;
        }
        parent::_construct();

        /** @var $product Mage_Catalog_Model_Product */
        $product = Mage::registry('product');
        $json = $api->getSocialData();
        $this->setSocialData($json);

        if ($product) {
            if (!empty($json->count)) {
                $this->setPeopleCount($json->count);
            }
        }

        $this->setTemplate('social/facebook/empty.phtml');

        $this->setShowSumm(Social_Facebook_Block_Start::FACEBOOK_BLOCK_NO_TEXT);

        $session = Mage::getSingleton('core/session');
        $session->setData('product_id', $product->getId());
        $session->setData('product_url', $product->getUrlModel()->getUrlInStore($product));
        $this->setFbUserId($session->getData('facebook_id'));

        $accessToken = $session->getData('access_token');

        if (!$accessToken) {
            $this->setShowSumm(Social_Facebook_Block_Start::FACEBOOK_BLOCK_START_CONNECT);
            $this->setConnectUrl(Mage::helper('social_facebook')->getRedirectUrl($product));
            $session->unsetData('facebook_action');
            $session->setData('no_boxes', 1);
        } else {
            $actions = Mage::helper('social_facebook')->getAllActions();
            foreach ($actions as $action) {
                if (!empty($json->actions->$action['action'])) {
                    $data = true; break;
                }
            }

            if (empty($data)) {
                $this->setShowSumm(Social_Facebook_Block_Start::FACEBOOK_BLOCK_START_FRIENDS);
                $session->setData('no_boxes', 1);
            } else {
                $session->unsetData('no_boxes');
            }
        }

        return $this;
    }
}
