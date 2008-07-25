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
 * @package    Mage_Cybersource
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Mage_Cybersource_Block_Info extends Mage_Payment_Block_Info_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('cybersource/info.phtml');
    }

    protected function _getConfig()
    {
        return Mage::getSingleton('cybersource/config');
    }


     /**
     * Retrieve credit card type name
     *
     * @return string
     */
    public function getCcTypeName()
    {
        $types = $this->_getConfig()->getCcTypes();
        if (isset($types[$this->getInfo()->getCcType()])) {
            return $types[$this->getInfo()->getCcType()];
        }
        return $this->getInfo()->getCcType();
    }

    /**
     * Retrieve CC start month for switch/solo card
     *
     * @return string
     */
    public function getCcStartMonth()
    {
        $month = $this->getInfo()->getCcSsStartMonth();
        if ($month<10) {
            $month = '0'.$month;
        }
        return $month;
    }

    public function toPdf()
    {
        $this->setTemplate('cybersource/pdf/info.phtml');
        return $this->toHtml();
    }
}