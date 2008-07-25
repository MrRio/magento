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
 * @package    Mage_CatalogSearch
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Advanced search form
 *
 * @category   Mage
 * @package    Mage_CatalogSearch
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_CatalogSearch_Block_Advanced_Form extends Mage_Core_Block_Template
{
    public function _prepareLayout()
    {
        if ($headBlock = $this->getLayout()->getBlock('head')) {
            $headBlock->setTitle(Mage::helper('catalogsearch')->__('Catalog Advanced Search'));
        }

        // add Home breadcrumb
        $this->getLayout()->getBlock('breadcrumbs')
            ->addCrumb('home',
                array('label'=>Mage::helper('catalogsearch')->__('Home'),
                    'title'=>Mage::helper('catalogsearch')->__('Go to Home Page'),
                    'link'=>Mage::getBaseUrl())
                )
            ->addCrumb('search',
                array('label'=>Mage::helper('catalogsearch')->__('Catalog Advanced Search'))
                );
        return parent::_prepareLayout();
    }

    /**
     * Retrieve collection of product searchable attributes
     *
     * @return Varien_Data_Collection_Db
     */
    public function getSearchableAttributes()
    {
        $attributes = $this->getModel()->getAttributes();
        return $attributes;
    }

    /**
     * Retrieve attribute label
     *
     * @param  $attribute
     * @return string
     */
    public function getAttributeLabel($attribute)
    {
        return Mage::helper('catalogsearch')->__($attribute->getFrontend()->getLabel());
    }

    /**
     * Retrieve attribute input validation class
     *
     * @param   $attribute
     * @return  string
     */
    public function getAttributeValidationClass($attribute)
    {
        return $attribute->getFrontendClass();
    }

    public function getAttributeValue($attribute, $part=null)
    {
        $value = $this->getRequest()->getQuery($attribute->getAttributeCode());
        if ($part && $value) {
            if (isset($value[$part])) {
                $value = $value[$part];
            }
            else {
                $value = '';
            }
        }

        if (!is_array($value)) {
            $value = htmlspecialchars($value);
        }
        return $value;
    }

    /**
     * Retrieve attribute input type
     *
     * @param   $attribute
     * @return  string
     */
    public function getAttributeInputType($attribute)
    {
        $dataType   = $attribute->getBackend()->getType();
        $imputType  = $attribute->getFrontend()->getInputType();
        if ($imputType == 'select' || $imputType == 'multiselect') {
            return 'select';
        }

        if ($imputType == 'boolean') {
            return 'yesno';
        }

        if ($dataType == 'int' || $dataType == 'decimal') {
            return 'number';
        }

        if ($dataType == 'datetime') {
            return 'date';
        }

        return 'string';
    }

    public function getAttributeSelectElement($attribute)
    {
        $extra = '';
        $options = $attribute->getSource()->getAllOptions(false);

        $name = $attribute->getAttributeCode();

        // 2 - avoid yes/no selects to be multiselects
        if (is_array($options) && count($options)>2) {
            $extra = 'multiple="multiple" size="4"';
            $name.= '[]';
        }
        else {
            array_unshift($options, array('value'=>'', 'label'=>Mage::helper('catalogsearch')->__('All')));
        }



        return $this->_getSelectBlock()
            ->setName($name)
            ->setId($attribute->getAttributeCode())
            ->setTitle($this->getAttributeLabel($attribute))
            ->setExtraParams($extra)
            ->setValue($this->getAttributeValue($attribute))
            ->setOptions($options)
            ->getHtml();
    }

    public function getAttributeYesNoElement($attribute)
    {
        $options = array(
            array('value' => '',  'label' => Mage::helper('catalogsearch')->__('All')),
            array('value' => '1', 'label' => Mage::helper('catalogsearch')->__('Yes')),
            array('value' => '0', 'label' => Mage::helper('catalogsearch')->__('No'))
        );

        $name = $attribute->getAttributeCode();
        return $this->_getSelectBlock()
            ->setName($name)
            ->setId($attribute->getAttributeCode())
            ->setTitle($this->getAttributeLabel($attribute))
            ->setExtraParams("")
            ->setValue($this->getAttributeValue($attribute))
            ->setOptions($options)
            ->getHtml();
    }

    protected function _getSelectBlock()
    {
        $block = $this->getData('_select_block');
        if (is_null($block)) {
            $block = $this->getLayout()->createBlock('core/html_select');
            $this->setData('_select_block', $block);
        }
        return $block;
    }

    protected function _getDateBlock()
    {
        $block = $this->getData('_date_block');
        if (is_null($block)) {
            $block = $this->getLayout()->createBlock('core/html_date');
            $this->setData('_date_block', $block);
        }
        return $block;
    }
    /**
     * Retrieve advanced search model object
     *
     * @return Mage_CatalogSearch_Model_Advanced
     */
    public function getModel()
    {
        return Mage::getSingleton('catalogsearch/advanced');
    }

    public function getSearchPostUrl()
    {
        return $this->getUrl('*/*/result');
    }

    public function getDateInput($attribute, $part = 'from')
    {
        $name = $attribute->getAttributeCode() . '[' . $part . ']';
        $value = $this->getAttributeValue($attribute, $part);

        return $this->_getDateBlock()
            ->setName($name)
            ->setId($attribute->getAttributeCode() . '_' . $part)
            ->setTitle($this->getAttributeLabel($attribute))
            ->setValue($value)
            ->setImage($this->getSkinUrl('images/calendar.gif'))
            ->setFormat('%m/%d/%y')
            ->setClass('input-text')
            ->getHtml();
    }
}