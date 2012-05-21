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

class Social_Facebook_Block_Adminhtml_Select extends Mage_Core_Block_Html_Select
{
    protected $_options = array();

    public function setInputName($value)
    {
        return $this->setName($value);
    }

    public function _toHtml()
    {
        $yesnoSource = Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray();

        foreach ($yesnoSource as $action) {
            $this->addOption($action['value'], $action['label']);
        }

        return parent::_toHtml();
    }

}
