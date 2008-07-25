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
 * @package    Mage_Catalog
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Catalog product model
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Model_Product extends Mage_Catalog_Model_Abstract
{
    const CACHE_TAG              = 'catalog_product';
    protected $_cacheTag         = 'catalog_product';
    protected $_eventPrefix      = 'catalog_product';
    protected $_eventObject      = 'product';
    protected $_canAffectOptions = false;

    /**
     * Product type instance
     *
     * @var Mage_Catalog_Model_Product_Type_Abstract
     */
    protected $_typeInstance = null;

    /**
     * Product link instance
     *
     * @var Mage_Catalog_Model_Product_Link
     */
    protected $_linkInstance;

    /**
     * Product object customization (not stored in DB)
     *
     * @var array
     */
    protected $_customOptions = array();

    protected $_urlModel = null;

    protected static $_url;
    protected static $_urlRewrite;

    protected $_errors    = array();

    protected $_optionInstance;

    protected $_options = array();

    /**
     * Initialize resources
     */
    protected function _construct()
    {
        $this->_urlModel = Mage::getSingleton('catalog/product_url');
        $this->_init('catalog/product');
    }

    public function validate()
    {
        $this->_getResource()->validate($this);
        return $this;
    }

    public function getName()
    {
        return $this->_getData('name');
    }

    /**
     * Get product price throught type instance
     *
     * @return unknown
     */
    public function getPrice()
    {
        return $this->getPriceModel()->getPrice($this);
    }

    public function getTypeId()
    {
        return $this->_getData('type_id');
    }

    public function getStatus()
    {
        return $this->_getData('status');
    }

    /**
     * Retrieve type instance
     *
     * Type instance implement type depended logic
     *
     * @return  Mage_Catalog_Model_Product_Type_Abstract
     */
    public function getTypeInstance()
    {
        if ($this->_typeInstance === null) {
            $this->_typeInstance = Mage::getSingleton('catalog/product_type')->factory($this);
        }
        return $this->_typeInstance;
    }

    public function setTypeInstance($instance)
    {
        $this->_typeInstance = $instance;
        return $this;
    }

    /**
     * Retrieve type instance
     *
     * @return  Mage_Catalog_Model_Product_Link
     */
    public function getLinkInstance()
    {
        if (!$this->_linkInstance) {
            $this->_linkInstance = Mage::getSingleton('catalog/product_link');
        }
        return $this->_linkInstance;
    }

    /**
     * Retrive product id by sku
     *
     * @param   string $sku
     * @return  integer
     */
    public function getIdBySku($sku)
    {
        return $this->_getResource()->getIdBySku($sku);
    }

    /**
     * Retrieve product category id
     *
     * @return int
     */
    public function getCategoryId()
    {
        if ($category = Mage::registry('current_category')) {
            return $category->getId();
        }
        return false;
    }

    /**
     * Retrieve product category
     *
     * @return Mage_Catalog_Model_Category
     */
    public function getCategory()
    {
        $category = $this->getData('category');
        if (is_null($category) && $this->getCategoryId()) {
            $category = Mage::getModel('catalog/category')->load($this->getCategoryId());
            $this->setCategory($category);
        }
        return $category;
    }

    public function setCategoryIds($ids)
    {
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        } elseif (!is_array($ids)) {
            Mage::throwException(Mage::helper('catalog')->__('Invalid category IDs'));
        }
        foreach ($ids as $i=>$v) {
            if (empty($v)) {
                unset($ids[$i]);
            }
        }
        $this->setData('category_ids', $ids);
        return $this;
    }

    public function getCategoryIds()
    {
        if ($this->hasData('category_ids')) {
            $ids = $this->getData('category_ids');
            if (!is_array($ids)) {
                $ids = !empty($ids) ? explode(',', $ids) : array();
                $this->setData('category_ids', $ids);
            }
        } else {
            $ids = $this->_getResource()->getCategoryIds($this);
            $this->setData('category_ids', $ids);
        }
        return $this->getData('category_ids');
    }

    /**
     * Retrieve product categories
     *
     * @return Varien_Data_Collection
     */
    public function getCategoryCollection()
    {
        return $this->getResource()->getCategoryCollection($this);
    }

    /**
     * Retrieve product websites identifiers
     *
     * @return array
     */
    public function getWebsiteIds()
    {
        if (!$this->hasWebsiteIds()) {
            $ids = $this->_getResource()->getWebsiteIds($this);
            $this->setWebsiteIds($ids);
        }
        return $this->getData('website_ids');
    }

    public function getStoreIds()
    {
        if (!$this->hasStoreIds()) {
            $storeIds = array();
            if ($websiteIds = $this->getWebsiteIds()) {
                foreach ($websiteIds as $websiteId) {
                    $websiteStores = Mage::app()->getWebsite($websiteId)->getStoreIds();
                    $storeIds = array_merge($storeIds, $websiteStores);
                }
            }
            $this->setStoreIds($storeIds);
        }
        return $this->getData('store_ids');
    }

    /**
     * Retrieve product attributes
     *
     * if $groupId is null - retrieve all product attributes
     *
     * @param   int $groupId
     * @return  array
     */
    public function getAttributes($groupId = null, $skipSuper=false)
    {
        $productAttributes = $this->getTypeInstance()->getEditableAttributes();
        if ($groupId) {
            $attributes = array();
            foreach ($productAttributes as $attribute) {
                if ($attribute->isInGroup($this->getAttributeSetId(), $groupId)) {
                    $attributes[] = $attribute;
                }
            }
        }
        else {
            $attributes = $productAttributes;
        }

        return $attributes;
    }

    /**
     * Check product options and type options and save them, too
     *
     */
    protected function _beforeSave()
    {
        $this->cleanCache();
        $this->setTypeHasOptions(false);
        $this->setTypeHasRequiredOptions(false);

        $this->getTypeInstance()->beforeSave();

        $hasOptions         = false;
        $hasRequiredOptions = false;
        $this->canAffectOptions($this->_canAffectOptions && $this->getCanSaveCustomOptions());
        if ($this->getCanSaveCustomOptions()) {
            $options = $this->getProductOptions();
            if (is_array($options)) {
                foreach ($this->getProductOptions() as $option) {
                    $this->getOptionInstance()->addOption($option);
                    if ((!isset($option['is_delete'])) || $option['is_delete'] != '1') {
                        $hasOptions = true;
                    }
                }
                foreach ($this->getOptionInstance()->getOptions() as $option) {
                    if ($option['is_require'] == '1') {
                        $hasRequiredOptions = true;
                        break;
                    }
                }
            }
        }

        /**
         * Set true, if any
         * Set false, ONLY if options have been affected by Options tab and Type instance tab
         */
        if ($hasOptions || (bool)$this->getTypeHasOptions()) {
            $this->setHasOptions(true);
            if ($hasRequiredOptions || (bool)$this->getTypeHasRequiredOptions()) {
                $this->setRequiredOptions(true);
            }
            elseif ($this->canAffectOptions()) {
                $this->setRequiredOptions(false);
            }
        }
        elseif ($this->canAffectOptions()) {
            $this->setHasOptions(false);
            $this->setRequiredOptions(false);
        }

        parent::_beforeSave();
    }

    /**
     * Check/set if options can be affected when saving product
     * If value specified, it will be set.
     *
     * @param bool $value
     * @return bool
     */
    public function canAffectOptions($value = null)
    {
        if (null !== $value) {
            $this->_canAffectOptions = (bool)$value;
        }
        return $this->_canAffectOptions;
    }

    /**
     * Saving product type related data
     *
     * @return unknown
     */
    protected function _afterSave()
    {
        $this->getLinkInstance()->saveProductRelations($this);
        $this->getTypeInstance()->save();

        /**
         * Product Custom Options
         */
        /* @var $optionModel Mage_Catalog_Model_Product_Option */
//        $options = $this->getProductOptions();
//        if (is_array($options)) {
//            foreach ($this->getProductOptions() as $option) {
//                $this->getOptionInstance()->addOption($option);
//            }
//        }
        $this->getOptionInstance()->setProduct($this)
            ->saveOptions();

        parent::_afterSave();
    }

    protected function _beforeDelete()
    {
        $this->_substractQtyFromQuotes();
        $this->cleanCache();
        parent::_beforeDelete();
    }

    /**
     * Substract this product from all quotes quantities
     *
     * @throws Exception
     */
    protected function _substractQtyFromQuotes()
    {
        // get all quotes and store ids, in which the product may be
        /*
        SELECT qi.item_id, qi.qty, qi.quote_id, q.store_id, q.items_qty, q.items_count
        FROM sales_flat_quote_item qi
            INNER JOIN sales_flat_quote q ON qi.quote_id=q.entity_id
        WHERE qi.product_id=?d
        */
        $quotesCollection = Mage::getModel('sales/quote')->getCollection();
        $quoteItemsCollection = Mage::getModel('sales/quote_item')->getCollection()
            ->resetJoinQuotes($quotesCollection->getResource()->getMainTable(), $this->getId());
        $quotesStores = $quoteItemsCollection->getConnection()->fetchAll($quoteItemsCollection->getSelect());

        foreach ($quotesStores as $quoteStore) {
            // substract quantity from the quote
            $quoteItem = Mage::getModel('sales/quote')
                ->setId($quoteStore['quote_id'])
                ->setItemsCount((int)$quoteStore['items_count'] - 1)
                ->setItemsQty((int)$quoteStore['items_qty'] - (int)$quoteStore['qty'])
                ->setStoreId($quoteStore['store_id']) // it is used in _beforeSave()
            ;
            $quoteItem->save();
        }
    }

    protected function _afterLoad()
    {
        parent::_afterLoad();
        foreach ($this->getProductOptionsCollection() as $option) {
            $option->setProduct($this);
            $this->addOption($option);
        }

        return $this;
    }

    public function cleanCache()
    {
        Mage::app()->cleanCache('catalog_product_'.$this->getId());
    }

    public function getPriceModel()
    {
        return Mage::getSingleton('catalog/product_type')->priceFactory($this->getTypeId());
    }

    /**
     * Get product tier price by qty
     *
     * @param   double $qty
     * @return  double
     */
    public function getTierPrice($qty=null)
    {
        return $this->getPriceModel()->getTierPrice($qty, $this);
    }

    /**
     * Count how many tier prices we have for the product
     *
     * @return  int
     */
    public function getTierPriceCount()
    {
        return $this->getPriceModel()->getTierPriceCount($this);
    }

    /**
     * Get formated by currency tier price
     *
     * @param   double $qty
     * @return  array || double
     */
    public function getFormatedTierPrice($qty=null)
    {
        return $this->getPriceModel()->getFormatedTierPrice($qty, $this);
    }

    /**
     * Get formated by currency product price
     *
     * @return  array || double
     */
    public function getFormatedPrice()
    {
        return $this->getPriceModel()->getFormatedPrice($this);
    }

    /**
     * Get product final price
     *
     * @param double $qty
     * @return double
     */
    public function getFinalPrice($qty=null)
    {
        return $this->getPriceModel()->getFinalPrice($qty, $this);
    }

    public function getCalculatedFinalPrice()
    {
        return $this->_getData('calculated_final_price');
    }

    public function getMinimalPrice()
    {
        return $this->_getData('minimal_price');
    }

    public function getSpecialPrice()
    {
        return $this->_getData('special_price');
    }

    public function getSpecialFromDate()
    {
        return $this->_getData('special_from_date');
    }

    public function getSpecialToDate()
    {
        return $this->_getData('special_to_date');
    }


/*******************************************************************************
 ** Linked products API
 */
    /**
     * Retrieve array of related roducts
     *
     * @return array
     */
    public function getRelatedProducts()
    {
        if (!$this->hasRelatedProducts()) {
            $products = array();
            $collection = $this->getRelatedProductCollection();
            foreach ($collection as $product) {
                $products[] = $product;
            }
            $this->setRelatedProducts($products);
        }
        return $this->getData('related_products');
    }

    /**
     * Retrieve related products identifiers
     *
     * @return array
     */
    public function getRelatedProductIds()
    {
        if (!$this->hasRelatedProductIds()) {
            $ids = array();
            foreach ($this->getRelatedProducts() as $product) {
                $ids[] = $product->getId();
            }
            $this->setRelatedProductIds($ids);
        }
        return $this->getData('related_product_ids');
    }

    /**
     * Retrieve collection related product
     */
    public function getRelatedProductCollection()
    {
        $collection = $this->getLinkInstance()->useRelatedLinks()
            ->getProductCollection()
            ->setIsStrongMode();
        $collection->setProduct($this);
        return $collection;
    }

    /**
     * Retrieve array of up sell products
     *
     * @return array
     */
    public function getUpSellProducts()
    {
        if (!$this->hasUpSellProducts()) {
            $products = array();
            foreach ($this->getUpSellProductCollection() as $product) {
                $products[] = $product;
            }
            $this->setUpSellProducts($products);
        }
        return $this->getData('up_sell_products');
    }

    /**
     * Retrieve up sell products identifiers
     *
     * @return array
     */
    public function getUpSellProductIds()
    {
        if (!$this->hasUpSellProductIds()) {
            $ids = array();
            foreach ($this->getUpSellProducts() as $product) {
                $ids[] = $product->getId();
            }
            $this->setUpSellProductIds($ids);
        }
        return $this->getData('up_sell_product_ids');
    }

    /**
     * Retrieve collection up sell product
     */
    public function getUpSellProductCollection()
    {
        $collection = $this->getLinkInstance()->useUpSellLinks()
            ->getProductCollection()
            ->setIsStrongMode();
        $collection->setProduct($this);
        return $collection;
    }

    /**
     * Retrieve array of cross sell roducts
     *
     * @return array
     */
    public function getCrossSellProducts()
    {
        if (!$this->hasCrossSellProducts()) {
            $products = array();
            foreach ($this->getCrossSellProductCollection() as $product) {
                $products[] = $product;
            }
            $this->setCrossSellProducts($products);
        }
        return $this->getData('cross_sell_products');
    }

    /**
     * Retrieve cross sell products identifiers
     *
     * @return array
     */
    public function getCrossSellProductIds()
    {
        if (!$this->hasCrossSellProductIds()) {
            $ids = array();
            foreach ($this->getCrossSellProducts() as $product) {
                $ids[] = $product->getId();
            }
            $this->setCrossSellProductIds($ids);
        }
        return $this->getData('cross_sell_product_ids');
    }

    /**
     * Retrieve collection cross sell product
     */
    public function getCrossSellProductCollection()
    {
        $collection = $this->getLinkInstance()->useCrossSellLinks()
            ->getProductCollection()
            ->setIsStrongMode();
        $collection->setProduct($this);
        return $collection;
    }

/*******************************************************************************
 ** Media API
 */
    /**
     * Retrive attributes for media gallery
     *
     * @return array
     */
    public function getMediaAttributes()
    {
        if (!$this->hasMediaAttributes()) {
            $mediaAttributes = array();
            foreach ($this->getAttributes() as $attribute) {
                if($attribute->getFrontend()->getInputType() == 'media_image') {
                    $mediaAttributes[$attribute->getAttributeCode()] = $attribute;
                }
            }
            $this->setMediaAttributes($mediaAttributes);
        }
        return $this->getData('media_attributes');
    }

    /**
     * Retrive media gallery images
     *
     * @return Varien_Data_Collection
     */
    public function getMediaGalleryImages()
    {
        if(!$this->hasData('media_gallery_images') && is_array($this->getMediaGallery('images'))) {
            $images = new Varien_Data_Collection();
            foreach ($this->getMediaGallery('images') as $image) {
                if ($image['disabled']) {
                    continue;
                }
                $image['url'] = $this->getMediaConfig()->getMediaUrl($image['file']);
                $image['id'] = $image['value_id'];
                $image['path'] = $this->getMediaConfig()->getMediaPath($image['file']);
                $images->addItem(new Varien_Object($image));
            }
            $this->setData('media_gallery_images', $images);
        }

        return $this->getData('media_gallery_images');
    }

    /**
     * Add image to media gallery
     *
     * @param string        $file              file path of image in file system
     * @param string|array  $mediaAttribute    code of attribute with type 'media_image',
     *                                         leave blank if image should be only in gallery
     * @param boolean       $move              if true, it will move source file
     * @param boolean       $exclude           mark image as disabled in product page view
     */
    public function addImageToMediaGallery($file, $mediaAttribute=null, $move=false, $exclude=true)
    {
        $attributes = $this->getTypeInstance()->getSetAttributes();
        if (!isset($attributes['media_gallery'])) {
            return $this;
        }
        $mediaGalleryAttribute = $attributes['media_gallery'];
        /* @var $mediaGalleryAttribute Mage_Catalog_Model_Resource_Eav_Attribute */
        $mediaGalleryAttribute->getBackend()->addImage($this, $file, $mediaAttribute, $move, $exclude);
        return $this;
    }

    /**
     * Retrive product media config
     *
     * @return Mage_Catalog_Model_Product_Media_Config
     */
    public function getMediaConfig()
    {
        return Mage::getSingleton('catalog/product_media_config');
    }

    /**
     * Create duplicate
     *
     * @return unknown
     */
    public function duplicate()
    {
        $this->getWebsiteIds();
        $this->getCategoryIds();

        Mage::dispatchEvent('catalog_model_product_duplicate', array($this->_eventObject=>$this));
        $newProduct = Mage::getModel('catalog/product')
            ->setData($this->getData())
            ->setIsDuplicate(true)
            ->setOriginalId($this->getId())
            ->setSku(null)
            ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_DISABLED)
            ->setId(null);
        /* @var $newProduct Mage_Catalog_Model_Product */

        $newOptionsArray = array();
        $newProduct->setCanSaveCustomOptions(true);
        foreach ($this->getOptions() as $_option) {
            /* @var $_option Mage_Catalog_Model_Product_Option */
            $newOptionsArray[] = $_option->prepareOptionForDuplicate();
        }
        $newProduct->setProductOptions($newOptionsArray);

        $newId = $newProduct->getId();

        $newProduct->save();

        /*if ($storeIds = $this->getWebsiteIds()) {
            foreach ($storeIds as $storeId) {
                $this->setStoreId($storeId)
                   ->load($this->getId());

                $newProduct->setData($this->getData())
                    ->setSku(null)
                    ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_DISABLED)
                    ->setId($newId)
                    ->save();
            }
        }*/
        return $newProduct;
    }

    public function isSuperGroup()
    {
        return $this->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED;
    }

    public function isSuperConfig()
    {
        return $this->isConfigurable();
    }
    /**
     * Check is product grouped
     *
     * @return bool
     */
    public function isGrouped()
    {
        return $this->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED;
    }

    /**
     * Check is product configurable
     *
     * @return bool
     */
    public function isConfigurable()
    {
        return $this->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
    }

    public function isSuper()
    {
        return $this->isConfigurable() || $this->isGrouped();
    }

    public function getVisibleInCatalogStatuses()
    {
        return Mage::getSingleton('catalog/product_status')->getVisibleStatusIds();
    }

    public function isVisibleInCatalog()
    {
        return in_array($this->getStatus(), $this->getVisibleInCatalogStatuses());
    }

    public function getVisibleInSiteVisibilities()
    {
        return Mage::getSingleton('catalog/product_visibility')->getVisibleInSiteIds();
    }

    public function isVisibleInSiteVisibility()
    {
        return in_array($this->getVisibility(), $this->getVisibleInSiteVisibilities());
    }

    /**
     * Check is product available for sale
     *
     * @return bool
     */
    public function isSalable()
    {
        return $this->getTypeInstance()->isSalable();
    }

    public function isSaleable()
    {
        return $this->isSalable();
    }

    public function isInStock()
    {
        return $this->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
    }

    public function getAttributeText($attributeCode)
    {
        return $this->getResource()
            ->getAttribute($attributeCode)
                ->getSource()
                    ->getOptionText($this->getData($attributeCode));
    }

    public function getCustomDesignDate()
    {
        $result = array();
        $result['from'] = $this->getData('custom_design_from');
        $result['to'] = $this->getData('custom_design_to');

        return $result;
    }

    /**
     * Get product url
     *
     * @return string
     */
    public function getProductUrl()
    {
        return $this->_urlModel->getProductUrl($this);
    }

    public function formatUrlKey($str)
    {
        return $this->_urlModel->formatUrlKey($str);
    }

    public function getUrlPath($category=null)
    {
        return $this->_urlModel->getUrlPath($this, $category);
    }

    public function getImageUrl()
    {
        return $this->_urlModel->getImageUrl($this);
    }

    public function getCustomImageUrl($size, $extension=null, $watermark=null)
    {
        return $this->_urlModel->getCustomImageUrl($this, $size, $extension, $watermark);
    }

    public function getSmallImageUrl()
    {
        return $this->_urlModel->getSmallImageUrl($this);
    }

    public function getCustomSmallImageUrl($size, $extension=null, $watermark=null)
    {
        return $this->_urlModel->getCustomSmallImageUrl($this, $size, $extension, $watermark);
    }

    public function getThumbnailUrl()
    {
        return $this->_urlModel->getThumbnailUrl($this);
    }

    public function importFromTextArray(array $row)
    {
        $hlp = Mage::helper('catalog');
        $line = $row['i'];
        $row = $row['row'];
        $isError = false;
        $this->unsetData();
        $catalogConfig = Mage::getSingleton('catalog/config');
        unset($row['entity_id']);
        $productId = null;
        // validate SKU
        if (empty($row['sku'])) {
            //$this->printError($hlp->__('SKU is required'), $line);
            //return ;
            $this->addError($hlp->__('SKU is required line: %s', $line));
        } else {
            $productId = $this->getIdBySku($row['sku']);
        }

        if ($productId) {
            $this->unsetData();
            $this->load($productId);
            if (isset($row['store'])) {
                $storeId = Mage::app()->getStore($row['store'])->getId();
                if ($storeId) $this->setStoreId($storeId);
            }
        } else {
            if ($row['store'] && $storeId = Mage::app()->getStore($row['store'])->getId()) {
                $this->setStoreId($storeId);
            } else {
                $this->setStoreId(0);
            }

            // if attribute_set not set use default
            if (empty($row['attribute_set'])) {
                $row['attribute_set'] = !empty($row['attribute_set_id']) ? $row['attribute_set_id'] : 'Default';
            }

            if ($row['attribute_set']) {
                // get attribute_set_id, if not throw error
                $attributeSetId = $catalogConfig->getAttributeSetId('catalog_product', $row['attribute_set']);
            }
            if (!isset($attributeSetId)) {
//                $this->printError($hlp->__("Invalid attribute set specified"), $line);
//                return;
                  $this->addError($hlp->__("Invalid attribute set specified line: %s", $line));
            }

            $this->setAttributeSetId($attributeSetId);

            if (empty($row['type'])) {
                $row['type'] = !empty($row['type_id']) ? $row['type_id'] : 'Simple Product';
            }
            // get product type_id, if not throw error
            $typeId = $catalogConfig->getProductTypeId($row['type']);
            if (!$typeId) {
                  $this->addError($hlp->__("Invalid product type specified line: %s", $line));
//                $this->printError($hlp->__("Invalid product type specified"), $line);
//                return;
            }
            $this->setTypeId($typeId);
        }

        if ($errors = $this->getErrors()) {
            $this->unsetData();
            $this->printError(join("<br />",$errors));
            $this->resetErrors();
            return;
        }

        $entity = $this->getResource();

        //print_r($entity);
        foreach ($row as $field=>$value) {
            $attribute = $entity->getAttribute($field);
            if (!$attribute) {
                continue;
            }

            if ($attribute->usesSource()) {
                $source = $attribute->getSource();
                $optionId = $catalogConfig->getSourceOptionId($source, $value);
                if (is_null($optionId)) {
                    $this->printError($hlp->__("Invalid attribute option specified for attribute attribute %s (%s)", $field, $value), $line);
                }
                $value = $optionId;
            }

            $this->setData($field, $value);
        }

        $postedStores = array(0=>0);
        if (isset($row['store'])) {
            foreach (explode(',', $row['store']) as $store) {
                $storeId = Mage::app()->getStore($store)->getId();
                if (!$this->hasStoreId()) {
                    $this->setStoreId($storeId);
                }
                $postedStores[$storeId] = $this->getStoreId();
            }
        }

        $this->setPostedStores($postedStores);

        if (isset($row['categories'])) {
            $this->setCategoryIds($row['categories']);
        }
        return $this;
    }

    public function importFromTextArraySilently(array $row)
    {
        $hlp = Mage::helper('catalog');
        $line = $row['i'];
        $row = $row['row'];
        $isError = false;
        $this->unsetData();
        $catalogConfig = Mage::getSingleton('catalog/config');
        unset($row['entity_id']);
        $productId = null;
        // validate SKU
        if (empty($row['sku'])) {
            //$this->printError($hlp->__('SKU is required'), $line);
            //return ;
            $this->addError($hlp->__('SKU is required line: %s', $line));
        } else {
            $productId = $this->getIdBySku($row['sku']);
        }

        if ($productId) {
            $this->unsetData();
            $this->load($productId);
            if (isset($row['store'])) {
                $storeId = Mage::app()->getStore($row['store'])->getId();
                if ($storeId) $this->setStoreId($storeId);
            }
        } else {
            if ($row['store'] && $storeId = Mage::app()->getStore($row['store'])->getId()) {
                $this->setStoreId($storeId);
            } else {
                $this->setStoreId(0);
            }

            // if attribute_set not set use default
            if (empty($row['attribute_set'])) {
                $row['attribute_set'] = !empty($row['attribute_set_id']) ? $row['attribute_set_id'] : 'Default';
            }

            if ($row['attribute_set']) {
                // get attribute_set_id, if not throw error
                $attributeSetId = $catalogConfig->getAttributeSetId('catalog_product', $row['attribute_set']);
            }
            if (!isset($attributeSetId)) {
//                $this->printError($hlp->__("Invalid attribute set specified"), $line);
//                return;
                  $this->addError($hlp->__("Invalid attribute set specified line: %s", $line));
            }

            $this->setAttributeSetId($attributeSetId);

            if (empty($row['type'])) {
                $row['type'] = !empty($row['type_id']) ? $row['type_id'] : 'Simple Product';
            }
            // get product type_id, if not throw error
            $typeId = $catalogConfig->getProductTypeId($row['type']);
            if (!$typeId) {
                  $this->addError($hlp->__("Invalid product type specified line: %s", $line));
//                $this->printError($hlp->__("Invalid product type specified"), $line);
//                return;
            }
            $this->setTypeId($typeId);
        }

        if ($errors = $this->getErrors()) {
            $this->unsetData();
//            $this->printError(join("<br />",$errors));
            $this->resetErrors();
            return false;
        }

        $entity = $this->getResource();

        //print_r($entity);
        foreach ($row as $field=>$value) {
            $attribute = $entity->getAttribute($field);
            if (!$attribute) {
                continue;
            }

            if ($attribute->usesSource()) {
                $source = $attribute->getSource();
                $optionId = $catalogConfig->getSourceOptionId($source, $value);
                if (is_null($optionId)) {
                    //$this->printError($hlp->__("Invalid attribute option specified for attribute attribute %s (%s)", $field, $value), $line);
                }
                $value = $optionId;
            }

            $this->setData($field, $value);
        }

        $postedStores = array(0=>0);
        if (isset($row['store'])) {
            foreach (explode(',', $row['store']) as $store) {
                $storeId = Mage::app()->getStore($store)->getId();
                if (!$this->hasStoreId()) {
                    $this->setStoreId($storeId);
                }
                $postedStores[$storeId] = $this->getStoreId();
            }
        }

        $this->setPostedStores($postedStores);

        if (isset($row['categories'])) {
            $this->setCategoryIds($row['categories']);
        }
        return $this;
    }

    function addError($error)
    {
        $this->_errors[] = $error;
    }

    function getErrors()
    {
        return $this->_errors;
    }

    function resetErrors()
    {
        $this->_errors = array();
    }

    function printError($error, $line = null)
    {
        if ($error == null) return false;
        $img = 'error_msg_icon.gif';
        $liStyle = 'background-color:#FDD; ';
        echo '<li style="'.$liStyle.'">';
        echo '<img src="'.Mage::getDesign()->getSkinUrl('images/'.$img).'" class="v-middle"/>';
        echo $error;
        if ($line) {
            echo '<small>, Line: <b>'.$line.'</b></small>';
        }
        echo "</li>";
    }

    public function addAttributeUpdate($code, $value, $store)
    {
        $oldValue = $this->getData($code);
        $oldStore = $this->getStoreId();

        $this->setData($code, $value);
        $this->setStoreId($store);
        $this->getResource()->saveAttribute($this, $code);

        $this->setData($code, $oldValue);
        $this->setStoreId($oldStore);
    }

    public function toArray(array $arrAttributes=array())
    {
        $data = parent::toArray($arrAttributes);
        if ($stock = $this->getStockItem()) {
            $data['stock_item'] = $stock->toArray();
        }
        unset($data['stock_item']['product']);
        return $data;
    }

    public function fromArray($data)
    {
        if (isset($data['stock_item'])) {
            $stockItem = Mage::getModel('cataloginventory/stock_item')
                ->setData($data['stock_item'])
                ->setProduct($this);
            $this->setStockItem($stockItem);
            unset($data['stock_item']);
        }
        $this->setData($data);
        return $this;
    }

    public function loadParentProductIds()
    {
        return $this->setParentProductIds($this->_getResource()->getParentProductIds($this));
    }

    public function delete()
    {
        parent::delete();
        Mage::dispatchEvent($this->_eventPrefix.'_delete_after_done', array($this->_eventObject=>$this));
        return $this;
    }

    public function getRequestPath()
    {
        return $this->_getData('request_path');
    }

    /**
     * Custom function for other modules
     */

    public function getGiftMessageAvailable()
    {
        return $this->_getData('gift_message_available');
    }

    public function getRatingSummary()
    {
        return $this->_getData('rating_summary');
    }

    /**
     * Check is product composite
     *
     * @return bool
     */
    public function isComposite()
    {
        return $this->getTypeInstance()->isComposite();
    }

    /**
     * Retrieve sku through type instance
     *
     * @return string
     */
    public function getSku()
    {
        return $this->getTypeInstance()->getSku();
    }

    /**
     * Retrieve weight throught type instance
     *
     * @return unknown
     */
    public function getWeight()
    {
        return $this->getTypeInstance()->getWeight();
    }

    /**
     * Retrieve option instance
     *
     * @return Mage_Catalog_Model_Product_Option
     */
    public function getOptionInstance()
    {
        if (!$this->_optionInstance) {
            $this->_optionInstance = Mage::getSingleton('catalog/product_option');
        }
        return $this->_optionInstance;
    }

    /**
     * Retrieve options collection of product
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Option_Collection
     */
    public function getProductOptionsCollection()
    {
        $collection = $this->getOptionInstance()
            ->getProductOptionCollection($this);

        return $collection;
    }

    /**
     * Add option to array of product options
     *
     * @param Mage_Catalog_Model_Product_Option $option
     * @return Mage_Catalog_Model_Product
     */
    public function addOption(Mage_Catalog_Model_Product_Option $option)
    {
        $this->_options[$option->getId()] = $option;
        return $this;
    }

    /**
     * Get option from options array of product by given option id
     *
     * @param int $optionId
     * @return Mage_Catalog_Model_Product_Option | null
     */
    public function getOptionById($optionId)
    {
        if (isset($this->_options[$optionId])) {
            return $this->_options[$optionId];
        }

        return null;
    }

    /**
     * Get all options of product
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Retrieve is a virtual product
     *
     * @return bool
     */
    public function getIsVirtual()
    {
        return $this->getTypeInstance()->isVirtual();
    }

    /**
     * Add custom option information to product
     *
     * @param   string $code
     * @param   mixed $value
     * @param   int $productId
     * @return  Mage_Catalog_Model_Product
     */
    public function addCustomOption($code, $value, $product=null)
    {
        $product = $product ? $product : $this;
        $this->_customOptions[$code] = new Varien_Object(array(
            'product_id'=> $product->getId(),
            'product'   => $product,
            'code'      => $code,
            'value'     => $value,
        ));
        return $this;
    }

    public function setCustomOptions(array $options)
    {
        $this->_customOptions = $options;
    }

    /**
     * Get all custom options of the product
     *
     * @return array
     */
    public function getCustomOptions()
    {
        return $this->_customOptions;
    }

    /**
     * Get product custom option info
     *
     * @param   string $code
     * @return  array
     */
    public function getCustomOption($code)
    {
        if (isset($this->_customOptions[$code])) {
            return $this->_customOptions[$code];
        }
        return null;
    }

    /**
     * Checks if there custom option for this product
     *
     * @return bool
     */
    public function hasCustomOptions()
    {
        if (count($this->_customOptions)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check availability display product in category
     *
     * @param   int $categoryId
     * @return  bool
     */
    public function canBeShowInCategory($categoryId)
    {
        return $this->_getResource()->canBeShowInCategory($this, $categoryId);
    }
}