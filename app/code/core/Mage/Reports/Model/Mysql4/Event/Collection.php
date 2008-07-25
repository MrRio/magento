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
 * @package    Mage_Reports
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Report event collection
 *
 * @category   Mage
 * @package    Mage_Reports
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Mage_Reports_Model_Mysql4_Event_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Store Ids
     *
     * @var array
     */
    protected $_storeIds;

    protected function _construct()
    {
        $this->_init('reports/event');
    }

    /**
     * Add store ids filter
     *
     * @param array $storeIds
     * @return Mage_Reports_Model_Mysql4_Event_Collection
     */
    public function addStoreFilter(array $storeIds)
    {
        $this->_storeIds = $storeIds;
        return $this;
    }

    /**
     * Add recently filter
     *
     * @param int $typeId
     * @param int $subjectId
     * @param int $subtype
     * @param int|array $ignore
     * @param int $limit
     * @return Mage_Reports_Model_Mysql4_Event_Collection
     */
    public function addRecentlyFiler($typeId, $subjectId, $subtype = 0, $ignore = null, $limit = 15)
    {
        $stores = array();
        if (Mage::app()->getStore()->getId() == 0) {
            if (!is_null($this->_storeIds)) {
                $stores = $this->_storeIds;
            }
            else {
                foreach (Mage::app()->getStores() as $store) {
                    $stores[] = $store->getId();
                }
            }
        }
        else {
            switch (Mage::getStoreConfig('catalog/recently_products/scope')) {
                case 'website':
                    $resourceStore = Mage::app()->getStore()->getWebsite()->getStores();
                    break;
                case 'group':
                    $resourceStore = Mage::app()->getStore()->getGroup()->getStores();
                    break;
                default:
                    $resourceStore = array(Mage::app()->getStore());
                    break;
            }

            foreach ($resourceStore as $store) {
                $stores[] = $store->getId();
            }
        }
        $this->_select
            ->where('event_type_id=?', $typeId)
            ->where('subject_id=?', $subjectId)
            ->where('subtype=?', $subtype)
            ->where('store_id IN(?)', $stores);
        if ($ignore) {
            if (is_array($ignore)) {
                $this->_select->where('object_id NOT IN(?)', $ignore);
            }
            else {
                $this->_select->where('object_id<>?', $ignore);
            }
        }
        $this->_select->group('object_id')
            ->limit($limit);
        return $this;
    }
}