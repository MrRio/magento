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
 * @category    Mage
 * @package     Mage_Bundle
 * @copyright   Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Order item render block
 *
 * @category    Mage
 * @package     Mage_Bundle
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Bundle_Block_Sales_Order_Items_Renderer extends Mage_Sales_Block_Order_Item_Renderer_Default
{
    protected $_itemsArray = null;

    public function isShipmentSeparately($item = null)
    {
        if ($item) {
            if ($item->getOrderItem()) {
                $item = $item->getOrderItem();
            }
            if ($parentItem = $item->getParentItem()) {
                if ($options = $parentItem->getProductOptions()) {
                    if (isset($options['shipment_type']) && $options['shipment_type'] == Mage_Catalog_Model_Product_Type_Abstract::SHIPMENT_SEPARATELY) {
                        return true;
                    } else {
                        return false;
                    }
                }
            } else {
                if ($options = $item->getProductOptions()) {
                    if (isset($options['shipment_type']) && $options['shipment_type'] == Mage_Catalog_Model_Product_Type_Abstract::SHIPMENT_SEPARATELY) {
                        return false;
                    } else {
                        return true;
                    }
                }
            }
        }

        if ($options = $this->getOrderItem()->getProductOptions()) {
            if (isset($options['shipment_type']) && $options['shipment_type'] == Mage_Catalog_Model_Product_Type_Abstract::SHIPMENT_SEPARATELY) {
                return true;
            }
        }
        return false;
    }

    public function isChildCalculated($item = null)
    {
        if ($item) {
            if ($item->getOrderItem()) {
                $item = $item->getOrderItem();
            }
            if ($parentItem = $item->getParentItem()) {
                if ($options = $parentItem->getProductOptions()) {
                    if (isset($options['product_calculations']) && $options['product_calculations'] == Mage_Catalog_Model_Product_Type_Abstract::CALCULATE_CHILD) {
                        return true;
                    } else {
                        return false;
                    }
                }
            } else {
                if ($options = $item->getProductOptions()) {
                    if (isset($options['product_calculations']) && $options['product_calculations'] == Mage_Catalog_Model_Product_Type_Abstract::CALCULATE_CHILD) {
                        return false;
                    } else {
                        return true;
                    }
                }
            }
        }

        if ($options = $this->getOrderItem()->getProductOptions()) {
            if (isset($options['product_calculations'])
                && $options['product_calculations'] == Mage_Catalog_Model_Product_Type_Abstract::CALCULATE_CHILD) {
                return true;
            }
        }
        return false;
    }

    public function getBundleOptions() {
        if ($options = $this->getOrderItem()->getProductOptions()) {
            if (isset($options['bundle_options'])) {
                return $options['bundle_options'];
            }
        }
        return array();
    }

    public function getValueHtml($value)
    {
        if (is_array($value)) {
            return sprintf('%d', $value['qty']) . ' x ' . $this->htmlEscape($value['title']) . " " . $this->getOrder()->formatPrice($value['price']);
        } else {
            return $this->htmlEscape($value);
        }
    }

    /**
     * Getting all available childs for Invoice, Shipmen or Creditmemo item
     *
     * @param Varien_Object $item
     * @return array
     */
    public function getChilds($item)
    {
        if (!$this->_itemsArray) {
            if ($item instanceof Mage_Sales_Model_Order_Invoice_Item) {
                $_items = $item->getInvoice()->getAllItems();
            } else if ($item instanceof Mage_Sales_Model_Order_Shipment_Item) {
                $_items = $item->getShipment()->getAllItems();
            } else if ($item instanceof Mage_Sales_Model_Order_Creditmemo_Item) {
                $_items = $item->getCreditmemo()->getAllItems();
            }

            if ($_items) {
                foreach ($_items as $_item) {
                    if ($parentItem = $_item->getOrderItem()->getParentItem()) {
                        $this->_itemsArray[$parentItem->getId()][$_item->getOrderItemId()] = $_item;
                    } else {
                        $this->_itemsArray[$_item->getOrderItem()->getId()][$_item->getOrderItemId()] = $_item;
                    }
                }
            }
        }

        if (isset($this->_itemsArray[$item->getOrderItem()->getId()])) {
            return $this->_itemsArray[$item->getOrderItem()->getId()];
        } else {
            return null;
        }
    }

    public function canShowPriceInfo($item)
    {
        if (($item->getOrderItem()->getParentItem() && $this->isChildCalculated())
                || (!$item->getOrderItem()->getParentItem() && !$this->isChildCalculated())) {
            return true;
        }
        return false;
    }
}