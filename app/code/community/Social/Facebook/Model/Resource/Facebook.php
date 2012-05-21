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
 * Facebook resource
 *
 * @category   Social
 * @package    Social_Facebook
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Social_Facebook_Model_Resource_Facebook extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Internal constructor
     */
    protected function _construct()
    {
        $this->_init('social_facebook/facebook', 'entity_id');
    }

    /**
     * Get Count of all Users by Action, Product Id
     *
     * @param int $facebookAction
     * @param int $productId
     *
     * @return int
     */
    public function getCountByActionProduct($facebookAction, $productId)
    {
        $read = $this->_getReadAdapter();
        $select = $read->select()
            ->from(array('main_table' => $this->getMainTable()), array('count(*)'))
            ->where('facebook_action = ?', $facebookAction)
            ->where('item_id = ?', $productId);

        return $read->fetchOne($select);
    }

    /**
     * Load User by Action and Facebook Id
     *
     * @param int $facebookAction
     * @param int $facebookId
     * @param int $productId
     *
     * @return Social_Facebook_Model_Facebook
     */
    public function loadUserByActionId($facebookAction, $facebookId, $productId)
    {
        $read = $this->_getReadAdapter();
        $select = $read->select()
            ->from(array('main_table' => $this->getMainTable()), array('*'))
            ->where('facebook_id = ?', $facebookId)
            ->where('facebook_action = ?', $facebookAction)
            ->where('item_id = ?', $productId);

        return $read->fetchRow($select);
    }

    /**
     * Get Count of all Users by Product Id
     *
     * @param int $productId
     *
     * @return int
     */
    public function getCountByProduct($productId)
    {
        $actions = Mage::helper('social_facebook')->getAllActions();
        $actionArray = array();
        foreach ($actions as $action) {
            $actionArray[] = $action['action'];
        }
        $read = $this->_getReadAdapter();
        $select = $read->select()
            ->from(array('main_table' => $this->getMainTable()), array('count(*)'))
            ->where('facebook_action in (?)', $actionArray)
            ->where('item_id = ?', $productId);

        return $read->fetchOne($select);
    }

    /**
     * Get Linked Facebook Friends
     *
     * @param array $friends
     * @param int $productId
     * @param string $facebookAction
     * @return array
     */
    public function getLinkedFriends($friends, $productId, $facebookAction)
    {
        $read = $this->_getReadAdapter();
        $select = $read->select()
            ->from(array('main_table' => $this->getMainTable()), array('facebook_id', 'facebook_name'))
            ->where('facebook_id in (?)', $friends)
            ->where('facebook_action = ?', $facebookAction)
            ->where('item_id = ?', $productId)
            ->order(array('entity_id DESC'))
            ->limit(Mage::helper('social_facebook')->getAppFriendCount($facebookAction))
            ->group('facebook_id');

        return $read->fetchPairs($select);
    }
}
