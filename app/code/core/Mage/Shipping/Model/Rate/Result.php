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
 * @package    Mage_Shipping
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Mage_Shipping_Model_Rate_Result
{
	protected $_rates = array();
	protected $_error = null;

	/**
	 * Reset result
	 */
	public function reset()
	{
	    $this->_rates = array();
	    return $this;
	}

	public function setError($error)
	{
	    $this->_error = $error;
	}

	public function getError()
	{
	    return $this->_error;
	}

	/**
	 * Add a rate to the result
	 *
	 * @param Mage_Shipping_Model_Rate_Result_Abstract|Mage_Shipping_Model_Rate_Result $result
	 */
	public function append($result)
	{
        if ($result instanceof Mage_Shipping_Model_Rate_Result_Abstract) {
            $this->_rates[] = $result;
        }
        elseif ($result instanceof Mage_Shipping_Model_Rate_Result) {
            $rates = $result->getAllRates();
            foreach ($rates as $rate) {
                $this->append($rate);
            }
        }
        return $this;
    }

	/**
	 * Return all quotes in the result
	 */
	public function getAllRates()
	{
		return $this->_rates;
	}

	/**
	 * Return rate by id in array
	 */
	public function getRateById($id)
	{
	    return isset($this->_rates[$id]) ? $this->_rates[$id] : null;
	}

	/**
	 * Return quotes for specified type
	 *
	 * @param string $type
	 */
	public function getRatesByCarrier($carrier)
	{
		$result = array();
		foreach ($this->_rates as $rate) {
			if ($rate->getCarrier()===$carrier) {
				$result[] = $rate;
			}
		}
		return $result;
	}

	public function asArray()
	{
        $currencyFilter = Mage::app()->getStore()->getPriceFilter();
        $rates = array();
        $allRates = $this->getAllRates();
        foreach ($allRates as $rate) {
            $rates[$rate->getCarrier()]['title'] = $rate->getCarrierTitle();
            $rates[$rate->getCarrier()]['methods'][$rate->getMethod()] = array(
                'title'=>$rate->getMethodTitle(),
                'price'=>$rate->getPrice(),
                'price_formatted'=>$currencyFilter->filter($rate->getPrice()),
            );
        }
        return $rates;
	}

	public function getCheapestRate()
	{
	    $cheapest = null;
	    $minPrice = 100000;
	    foreach ($this->getAllRates() as $rate) {
	        if (is_numeric($rate->getPrice()) && $rate->getPrice()<$minPrice) {
	            $cheapest = $rate;
	            $minPrice = $rate->getPrice();
	        }
	    }
	    return $cheapest;
	}
}
