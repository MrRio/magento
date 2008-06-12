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
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Tax Calculation Model
 *
 * @author Magento Core Team <core@magentocommerce.com>
 */
class Mage_Tax_Model_Calculation extends Mage_Core_Model_Abstract
{
    protected $_rates = array();
    protected $_ctc = array();
    protected $_ptc = array();

    protected $_rateCache = array();
    protected $_rateCalculationProcess = array();

    protected function _construct()
    {
        $this->_init('tax/calculation');
    }

    public function deleteByRuleId($ruleId)
    {
        $this->getResource()->deleteByRuleId($ruleId);
        return $this;
    }

    public function getRates($ruleId)
    {
        if (!isset($this->_rates[$ruleId])) {
            $this->_rates[$ruleId] = $this->getResource()->getDistinct('tax_calculation_rate_id', $ruleId);
        }
        return $this->_rates[$ruleId];
    }

    public function getCustomerTaxClasses($ruleId)
    {
        if (!isset($this->_ctc[$ruleId])) {
            $this->_ctc[$ruleId] = $this->getResource()->getDistinct('customer_tax_class_id', $ruleId);
        }
        return $this->_ctc[$ruleId];
    }

    public function getProductTaxClasses($ruleId)
    {
        if (!isset($this->_ptc[$ruleId])) {
            $this->_ptc[$ruleId] = $this->getResource()->getDistinct('product_tax_class_id', $ruleId);
        }
        return $this->_ptc[$ruleId];
    }

    public function getRate($request)
    {
        if (!$request->getCountryId() || !$request->getCustomerClassId() || !$request->getProductClassId()) {
            return 0;
        }

        $cacheKey = "{$request->getProductClassId()}|{$request->getCustomerClassId()}|{$request->getCountryId()}|{$request->getRegionId()}|{$request->getPostcode()}";
        if (!isset($this->_rateCache[$cacheKey])) {
            $this->unsRateValue();
            $this->unsCalculationProcess();
            Mage::dispatchEvent('tax_rate_data_fetch', array('request'=>$this));
            if (!$this->hasRateValue()) {
                $this->setCalculationProcess($this->_getResource()->getCalculationProcess($request));
                $this->setRateValue($this->_getResource()->getRate($request));
            }
            $this->_rateCache[$cacheKey] = $this->getRateValue();
            $this->_rateCalculationProcess[$cacheKey] = $this->getCalculationProcess();
        }
        return $this->_rateCache[$cacheKey];
    }

    public function getRateRequest($shippingAddress = null, $billingAddress = null, $customerTaxClass = null, $store = null)
    {
        $address = new Varien_Object();
        $session = Mage::getSingleton('customer/session');
        $basedOn = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_BASED_ON, $store);
        if (((is_null($billingAddress) || !$billingAddress->getCountryId()) && $basedOn == 'billing') || ((is_null($shippingAddress) || !$shippingAddress->getCountryId()) && $basedOn == 'shipping')){
            if (!$session->isLoggedIn()) {
                $basedOn = 'default';
            } else {
                $defBilling = $session->getCustomer()->getDefaultBillingAddress();
                $defShipping = $session->getCustomer()->getDefaultShippingAddress();

                if ($basedOn == 'billing' && $defBilling && $defBilling->getCountryId()) {
                    $billingAddress = $defBilling;
                } else if ($basedOn == 'shipping' && $defShipping && $defShipping->getCountryId()) {
                    $shippingAddress = $defShipping;
                } else {
                    $basedOn = 'default';
                }
            }
        }

        switch ($basedOn) {
            case 'billing':
                $address = $billingAddress;
                break;

            case 'shipping':
                $address = $shippingAddress;
                break;

            case 'origin':
                $address
                    ->setCountryId(Mage::getStoreConfig('shipping/origin/country_id', $store))
                    ->setRegionId(Mage::getStoreConfig('shipping/origin/region_id', $store))
                    ->setPostcode(Mage::getStoreConfig('shipping/origin/postcode', $store));
                break;

            case 'default':
                $address
                    ->setCountryId(Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_DEFAULT_COUNTRY, $store))
                    ->setRegionId(Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_DEFAULT_REGION, $store))
                    ->setPostcode(Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_DEFAULT_POSTCODE, $store));
                break;
        }

        if (is_null($customerTaxClass)) {
            $customerTaxClass = Mage::getSingleton('customer/session')->getCustomer()->getTaxClassId();
        }

        $request = new Varien_Object();
        $request
            ->setCountryId($address->getCountryId())
            ->setRegionId($address->getRegionId())
            ->setPostcode($address->getPostcode())
            ->setCustomerClassId($customerTaxClass);

        return $request;
    }

    protected function _getRates($request, $fieldName, $type)
    {
        $result = array();
        $classes = Mage::getModel('tax/class')->getCollection()
            ->addFieldToFilter('class_type', $type)
            ->load();
        foreach ($classes as $class) {
            $request->setData($fieldName, $class->getId());
            $result[$class->getId()] = $this->getRate($request);
        }

        return $result;
    }

    public function getRatesForAllProductTaxClasses($request)
    {
        return $this->_getRates($request, 'product_class_id', 'PRODUCT');
    }
    public function getRatesForAllCustomerTaxClasses($request)
    {
        return $this->_getRates($request, 'customer_class_id', 'CUSTOMER');
    }

    public function getAppliedRates($request)
    {
        $cacheKey = "{$request->getProductClassId()}|{$request->getCustomerClassId()}|{$request->getCountryId()}|{$request->getRegionId()}|{$request->getPostcode()}";
        if (!isset($this->_rateCalculationProcess[$cacheKey])) {
            $this->_rateCalculationProcess[$cacheKey] = $this->_getResource()->getCalculationProcess($request);
        }
        return $this->_rateCalculationProcess[$cacheKey];
        /*
        $rateIds = $this->_getResource()->getRateIds($request);
        if ($rateIds) {
            return Mage::getModel('tax/calculation_rate')->getCollection()->addFieldToFilter('tax_calculation_rate_id', $rateIds);
        }
        return array();
        */
    }

    public function reproduceProcess($rates)
    {
        return $this->getResource()->getCalculationProcess(null, $rates);
    }
}