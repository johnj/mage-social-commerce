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
 * Facebook Observer
 *
 * @category   Social
 * @package    Social_Facebook
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Social_Facebook_Model_Observer
{
    /**
     * Save & Send Actions to Facebook
     *
     * @return Social_Facebook_Model_Observer
     */
    public function catalogProduct()
    {
        if (!Mage::helper('social_facebook')->isEnabled()) {
            return false;
        }
        $session        = Mage::getSingleton('core/session');

        $facebookAction = $session->getData('facebook_action');
        $productId      = $session->getData('product_id');
        $productUrl     = $session->getData('product_url');

        /** @var $facebookModel Social_Facebook_Model_Facebook */
        $facebookModel  = Mage::getSingleton('social_facebook/facebook');
        $user = $facebookModel->getFacebookUser();

        $session->setData('facebook_user', $user);

        if ($facebookAction) {
            $result = $facebookModel->sendFacebookAction($user['facebook_id'], $productId);

            if (!empty($result)) {
                $session->addSuccess(Mage::helper('social_facebook')->__('I %s this product!', $facebookAction));
            }

            Mage::app()->getResponse()->setRedirect($productUrl);
            Mage::app()->getResponse()->sendResponse();
            $session->unsetData('facebook_action');
            exit();
        }

        if ($user) {
            $facebookId = $user['facebook_id'];
        }

        if (isset($facebookId)) {
            $facebookModel->getFriendsForUser($facebookId);
        }

        return $this;
    }

}
