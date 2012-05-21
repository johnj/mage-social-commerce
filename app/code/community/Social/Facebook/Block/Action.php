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

class Social_Facebook_Block_Action extends Mage_Core_Block_Template
{
    /**
     * Block Initialization
     *
     * @return Social_Facebook_Block_Action
     */
    protected function _construct()
    {
        if (!Mage::helper('social_facebook')->isEnabled()) {
            return;
        }
        parent::_construct();

        $product = Mage::registry('product');
        $this->setProductId($product->getId());

        $this->setAllActions(Mage::helper('social_facebook')->getAllActions());

        return $this;
    }

    /**
     * Get Url for redirect to Facebook
     *
     * @param string $action
     * @return string
     */
    public function getFacebookUrl($action)
    {
        return $this->getUrl(
            'facebook/index/redirect/',
            array(
                'productId' => $this->getProductId(),
                'action'    => $this->escapeHtml($action),
            ));
    }
}
