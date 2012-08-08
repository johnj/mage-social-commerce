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

class Social_Facebook_Block_Start extends Mage_Core_Block_Template
{
    const FACEBOOK_BLOCK_NO_TEXT        = 0;
    const FACEBOOK_BLOCK_START_CONNECT  = 1;
    const FACEBOOK_BLOCK_START_FRIENDS  = 2;

    protected function _construct()
    {
        if (!Mage::helper('social_facebook')->isEnabled()) {
            return;
        }

        parent::_construct();

        $this->setTemplate('social/facebook/socialdata.phtml');

        $this->setShowSumm(Social_Facebook_Block_Start::FACEBOOK_BLOCK_NO_TEXT);

        /** @var $product Mage_Catalog_Model_Product */
        $product = Mage::registry('product');
        $session = Mage::getSingleton('core/session');
        $session->setData('product_id', $product->getId());
        $session->setData('product_url', $product->getUrlModel()->getUrlInStore($product));
    }
}
