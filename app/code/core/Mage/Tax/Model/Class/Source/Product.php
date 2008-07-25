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
 * @category   Mage
 * @package    Mage_Tax
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Mage_Tax_Model_Class_Source_Product extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
	public function getAllOptions($withEmpty = false)
	{
		if (is_null($this->_options)) {
			$this->_options = Mage::getResourceModel('tax/class_collection')
        		->addFieldToFilter('class_type', 'PRODUCT')
        		->load()
        		->toOptionArray();
		}

		$options = $this->_options;
        array_unshift($options, array('value'=>'0', 'label'=>Mage::helper('tax')->__('None')));
        if ($withEmpty) {
            array_unshift($options, array('value'=>'', 'label'=>Mage::helper('tax')->__('-- Please Select --')));
        }
        return $options;
	}

	/**
     * Get a text for option value
     *
     * @param string|integer $value
     * @return string
     */
    public function getOptionText($value)
    {
        $options = $this->getAllOptions(false);

        foreach ($options as $item) {
            if ($item['value'] == $value) {
                return $item['label'];
            }
        }
        return false;
    }

	public function toOptionArray()
	{
		return $this->getAllOptions();
	}
}