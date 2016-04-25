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
    protected $_attribute_set;
    protected $_stock_item;
    
    protected function _loadObject()
    {
        return Mage::getModel('catalog/product')->load($this->id);
    }

    protected function _load($name)
    {
        $elems = explode('.', $name, $limit=2);
        $count = count($elems);
        $last_index = $count-1;
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
            case 'attribute_set': return $this->getAttributeSet()->name; // Compatibility
            case 'category': // Compatibility
                $category = $this->_getCategory();
                return $category ? $category->name : null;
            case 'categories': // Compatibility
                $categories = $this->getCategories();
                $output = array();
                foreach ($categories as $category) {
                    $output[] = $category->name;
                }
                return $output;
            case 'categories.id': // Compatibility
                $categories = $this->getCategories();
                $output = array();
                foreach ($categories as $category) {
                    $output[] = $category->id;
                }
                return $output;
            default: return parent::_load($name);
        }
    }

    public function getAttributeSet()
    {
        if (isset($this->_attribute_set)) return $this->_attribute_set;
        return $this->_attribute_set = Mage::getModel('owebia_shipping2/Os2_Data_AttributeSet', array('id' => (int)$this->attribute_set_id));
    }

    protected function _getStockItem()
    {
        //foreach ($this->_loaded_object->getData() as $index => $value) echo "$index = $value<br/>";
        if (isset($this->_stock_item)) return $this->_stock_item;
        return $this->_stock_item = Mage::getModel('owebia_shipping2/Os2_Data_StockItem', array('product_id' => (int)$this->id));
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

    protected function _getAttribute($attribute_name)
    {
        switch ($attribute_name) {
            case 'weight': return (double)parent::_getAttribute($attribute_name);
            default: return parent::_getAttribute($attribute_name);
        }
    }

    /*public function _getAttribute($attribute_name)
    {
        return parent::_getAttribute($attribute_name);

        // Dynamic weight for bundle product
        if ($this->type=='bundle' && $attribute_name=='weight' && $product->getData('weight_type')==0) {
            // !!! Use cart_product and not product
            return $this->cart_product->getTypeInstance(true)->getWeight($this->cart_product);
        }
    }*/

    public function __toString()
    {
        return $this->name.' (id:'.$this->id.', sku:'.$this->sku.')';
    }
}
