<?php

/**
 * Copyright (c) 2008-14 Owebia
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @website    http://www.owebia.com/
 * @project    Magento Owebia Shipping 2 module
 * @author     Antoine Lemoine
 * @license    http://www.opensource.org/licenses/MIT  The MIT License (MIT)
**/

class Owebia_Shipping2_Model_Os2_Data_Product extends Owebia_Shipping2_Model_Os2_Data_AbstractWithAttributes
{
    protected $_categories;
    protected $_attributeSet;
    protected $_stockItem;
    
    protected function _loadObject()
    {
        return Mage::getModel('catalog/product')->load($this->getData('id'));
    }

    protected function _load($name)
    {
        $elems = explode('.', $name, $limit=2);
        $count = count($elems);
        if ($count==2) {
            switch ($elems[0]) {
                case 'attribute_set':
                    return $this->getAttributeSet()->{$elems[1]};
                case 'stock':
                    return $this->_getStockItem()->{$elems[1]};
                case 'category':
                    $category = $this->_getCategory();
                    return $category ? $category->{$elems[1]} : null;
            }
        }
        switch ($name) {
            case 'attribute_set': return $this->getAttributeSet()->getData('name'); // Compatibility
            case 'category': // Compatibility
                $category = $this->_getCategory();
                return $category ? $category->getData('name') : null;
            case 'categories': // Compatibility
                $categories = $this->getCategories();
                $output = array();
                foreach ($categories as $category) {
                    $output[] = $category->getData('name');
                }
                return $output;
            case 'categories.id': // Compatibility
                $categories = $this->getCategories();
                $output = array();
                foreach ($categories as $category) {
                    $output[] = $category->getData('id');
                }
                return $output;
            default: return parent::_load($name);
        }
    }

    public function getAttributeSet()
    {
        if (isset($this->_attributeSet)) return $this->_attributeSet;
        return $this->_attributeSet = Mage::getModel('owebia_shipping2/Os2_Data_AttributeSet', array('id' => (int)$this->getData('attribute_set_id')));
    }

    protected function _getStockItem()
    {
        //foreach ($this->_loaded_object->getData() as $index => $value) echo "$index = $value<br/>";
        if (isset($this->_stockItem)) return $this->_stockItem;
        return $this->_stockItem = Mage::getModel('owebia_shipping2/Os2_Data_StockItem', array('product_id' => (int)$this->getData('id')));
    }

    protected function _getCategory()
    {
        $categories = $this->getCategories();
        return $categories ? $categories[0] : null;
    }

    public function getCategories()
    {
        if (isset($this->_categories)) return $this->_categories;
        $product = $this->_loadObject();
        $ids = $product->getCategoryIds();
        $this->_categories = array();
        foreach ($ids as $id) {
            $this->_categories[] = Mage::getModel('owebia_shipping2/Os2_Data_Category', array('id' => (int)$id));
        }
        return $this->_categories;
    }

    protected function _getAttribute($attributeName)
    {
        switch ($attributeName) {
            case 'weight': return (double)parent::_getAttribute($attributeName);
            default: return parent::_getAttribute($attributeName);
        }
    }

    /*public function _getAttribute($attributeName)
    {
        return parent::_getAttribute($attributeName);

        // Dynamic weight for bundle product
        if ($this->type=='bundle' && $attributeName=='weight' && $product->getData('weight_type')==0) {
            // !!! Use cartProduct and not product
            return $this->cartProduct->getTypeInstance(true)->getWeight($this->cartProduct);
        }
    }*/

    public function __toString()
    {
        return $this->getData('name').' (id:'.$this->getData('id').', sku:'.$this->getData('sku').')';
    }
}
