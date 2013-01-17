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

class Social_Facebook_Block_Head extends Mage_Core_Block_Template
{
    /**
     * Block Initialization
     *
     * @return Social_Facebook_Block_Head
     */
    protected function _construct()
    {
        $helper = Mage::helper('social_facebook');
        if (!$helper->isEnabled()) {
            return;
        }
        parent::_construct();

        /** @var $product Mage_Catalog_Model_Product */
        $product = Mage::registry('product');

        if ($product) {
            $this->setTemplate('social/facebook/page.phtml');

            $tags[] = array(
                'property'  => 'fb:app_id',
                'content'   => $helper->getAppId()
            );
            $tags[] = array(
                'property'  => 'og:type',
                'content'   => $helper->getAppName() . ':' . $helper->getObjectType()
            );
            $tags[] = array(
                'property'  => 'og:url',
                'content'   => Mage::getUrl('facebook/index/page', array('id' => $product->getId()))
            );
            $tags[] = array(
                'property'  => 'og:title',
                'content'   => $this->escapeHtml($product->getName())
            );
            $tags[] = array(
                'property'  => 'og:image',
                'content'   => $this->escapeHtml(Mage::helper('catalog/image')->init($product, 'image')->resize(256))
            );

            if($product->getShortDescription()!=$product->getName()) {
                $tags[] = array(
                        'property'  => 'og:description',
                        'content'   => $this->escapeHtml($product->getShortDescription())
                        );
            }

            $tags[] = array(
                'property'  => $helper->getAppName(). ':price',
                'content'   => Mage::helper('core')->currency($product->getFinalPrice(), true, false)
            );

            $this->setMetaTags($tags);

            $this->setRedirectUrl($product->getUrlModel()->getUrlInStore($product));

            $this->setAppName($helper->getAppName());
        }

        return $this;
    }
}
