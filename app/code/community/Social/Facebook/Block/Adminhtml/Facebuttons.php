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

class Social_Facebook_Block_Adminhtml_Facebuttons extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    /**
     * @var Mage_CatalogInventory_Block_Adminhtml_Form_Field_Customergroup
     */
    protected $_selectRenderer;

    /**
     * Retrieve checkbox column renderer
     *
     * @return Social_Facebook_Block_Adminhtml_Select
     */
    protected function _selectRenderer()
    {
        if (!$this->_selectRenderer) {
            $this->_selectRenderer = $this->getLayout()->createBlock(
                'social_facebook/adminhtml_select', '',
                array('is_render_to_js_template' => true)
            );
            $this->_selectRenderer->setClass('customer_group_select');
            $this->_selectRenderer->setExtraParams('style="width:120px"');
        }
        return $this->_selectRenderer;
    }

    /**
     * Prepare to render
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn('action', array(
            'label' => Mage::helper('social_facebook')->__('Facebook Action'),
            'style' => 'width:120px',
        ));
        $this->addColumn('title', array(
            'label' => Mage::helper('social_facebook')->__('Button Title'),
            'style' => 'width:120px',
        ));
        $this->addColumn('box', array(
            'label'     => Mage::helper('social_facebook')->__('Enable FriendBox'),
            'renderer'  => $this->_selectRenderer(),
        ));
        $this->addColumn('count', array(
            'label' => Mage::helper('social_facebook')->__('Number of friends to show (default %d)', Mage::helper('social_facebook')->getAppFriendCount('')),
            'style' => 'width:120px',
            'value' => 5
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('social_facebook')->__('Add an Action');
    }

    /**
     * Prepare existing row data object
     *
     * @param Varien_Object
     */
    protected function _prepareArrayRow(Varien_Object $row)
    {
        $row->setData(
            'option_extra_attr_' . $this->_selectRenderer()->calcOptionHash($row->getData('box')),
            'selected="selected"'
        );
    }
}
