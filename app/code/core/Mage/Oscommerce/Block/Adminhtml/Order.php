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
 * @package    Mage_Oscommerce
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * osCommerce convert list block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Mage_Oscommerce_Block_Adminhtml_Order extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    protected $_blockGroup = 'oscommerce';
    public function __construct()
    {
        $this->_controller = 'adminhtml_order';
        $this->_headerText = Mage::helper('adminhtml')->__('Manage osCommerce Orders');
        parent::__construct();
        $this->_removeButton('add');        
    }
}