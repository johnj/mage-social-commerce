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
 * @copyright   Copyright (c) 2009 Phoenix Medien GmbH & Co. KG (http://www.phoenix-medien.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Social_Facebook_Block_Start extends Mage_Core_Block_Template
{
    const FACEBOOK_BLOCK_NO_TEXT        = 0;
    const FACEBOOK_BLOCK_START_CONNECT  = 1;
    const FACEBOOK_BLOCK_START_FRIENDS  = 2;

    /**
     * Block Initialization
     *
     * @return
     */
    protected function _construct()
    {
        if (!Mage::helper('social_facebook')->isEnabled()) {
            return;
        }
        parent::_construct();

        $this->setTemplate('social/facebook/empty.phtml');

        $this->setShowSumm(Social_Facebook_Block_Start::FACEBOOK_BLOCK_NO_TEXT);

        /** @var $product Mage_Catalog_Model_Product */
        $product = Mage::registry('product');
        $session = Mage::getSingleton('core/session');
        $session->setData('product_id', $product->getId());
        $session->setData('product_url', $product->getUrlModel()->getUrlInStore($product));

        $accessToken = $session->getData('access_token');
        $facebookId  = $session->getData('facebook_id');

        $this->setPeopleCount(
            Mage::getModel('social_facebook/facebook')->getCountByProduct($product->getId())
        );

        if (!$accessToken) {
            $this->setShowSumm(Social_Facebook_Block_Start::FACEBOOK_BLOCK_START_CONNECT);
            $this->setConnectUrl(Mage::helper('social_facebook')->getRedirectUrl($product));
            $session->unsetData('facebook_action');
            $session->setData('no_boxes', 1);
        } else {
            $actions = Mage::helper('social_facebook')->getAllActions();
            $users  = array();
            foreach ($actions as $action) {
                $data = Mage::getModel('social_facebook/facebook')->getLinkedFriends($facebookId, $product->getId(),
                    $action['action']);
                if (!empty($data)) {
                    break;
                }
            }

            if (empty($data)) {
                $this->setShowSumm(Social_Facebook_Block_Start::FACEBOOK_BLOCK_START_FRIENDS);
                $session->setData('no_boxes', 1);
            } else {
                $session->unsetData('no_boxes');
            }
        }
    }
}
