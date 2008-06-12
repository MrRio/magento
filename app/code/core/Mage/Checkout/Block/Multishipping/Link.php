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
 * @package    Mage_Checkout
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Multishipping cart link
 *
 * @category   Mage
 * @package    Mage_Checkout
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Checkout_Block_Multishipping_Link extends Mage_Core_Block_Template
{
    public function getCheckoutUrl()
    {
        return $this->getUrl('checkout/multishipping', array('_secure'=>true));
    }

    public function _toHtml()
    {
        $maximunQty = (int)Mage::getStoreConfig('shipping/option/checkout_multiple_maximum_qty');
        if (Mage::getStoreConfig('shipping/option/checkout_multiple')
            && !Mage::getSingleton('checkout/session')->getQuote()->hasItemsWithDecimalQty()
            && Mage::getSingleton('checkout/session')->getQuote()->validateMinimumAmount()
                && (Mage::getSingleton('checkout/session')->getQuote()->getItemsSummaryQty() - Mage::getSingleton('checkout/session')->getQuote()->getItemVirtualQty()) > 1
                && Mage::getSingleton('checkout/session')->getQuote()->getItemsSummaryQty() <= $maximunQty) {
            return parent::_toHtml();
        }

        return '';
    }
}
