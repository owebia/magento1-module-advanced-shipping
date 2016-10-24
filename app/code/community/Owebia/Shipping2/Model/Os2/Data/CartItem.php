<?php
/**
 * Copyright © 2008-2016 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_CartItem extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
    private $parentCartItem;
    private $cartProduct;
    private $loadedProduct;
    private $quantity;
    private $categories;
    
    protected $_product;
    protected $_item;
    protected $_parentItem;
    protected $_type;
    protected $_options;
    protected $_getOptions;

    public function __construct($arguments)
    {
        parent::__construct();
        $this->_item = $item = $arguments['item'];
        $this->_parentItem = $parentItem = $arguments['parent_item'];
        $this->_getOptions = $options = $arguments['options'];
        $this->_product = null;
        $this->_type = $parentItem ? $parentItem->getProduct()->getTypeId() : $item->getProduct()->getTypeId();
        $this->_loadedObject = $this->_getItem('load_item_data_on_parent');

        if (false) {
            echo '---------------------------------<br/>';
            foreach ($this->_item->getData() as $index => $value) {
                $value = is_object($value) ? get_class($value) : (is_array($value) ? 'array' : $value);
                echo "$index = $value<br/>";
            }
            if ($parentItem) {
                echo '----- parent -----<br/>';
                foreach ($parentItem->getData() as $index => $value) echo "$index = $value<br/>";
            }
            echo 'type:' . $this->_type . '<br/>';
            echo 'sku:' . $this->getData('sku') . '<br/>';
        }
    }

    public function getProduct()
    {
        if (!isset($this->_product)) {
            //echo $this->_loadedObject->getData('product_id') . ', '
            //. $this->_getItem('load_product_data_on_parent')->getData('product_id') . '<br/>';
            $productId = $this->_getItem('load_product_data_on_parent')->getData('product_id');
            $this->_product = Mage::getModel('owebia_shipping2/Os2_Data_Product', array('id' => $productId));
        }
        return $this->_product;
    }

    protected function _load($name)
    {
        $elems = explode('.', $name, $limit = 2);
        $count = count($elems);
        if ($count == 2) {
            switch ($elems[0]) {
                case 'o':
                case 'option':
                    return $this->_getOption($elems[1]);
            }
        }
        switch ($name) {
            case 'price-tax+discount':
                return (double) $this->getData('base_original_price') - $this->getData('discount_amount')
                    / $this->getData('qty');
            case 'price-tax-discount':
                return (double) $this->getData('base_original_price');
            case 'price+tax+discount':
                /*echo 'base_original_price '.$this->getData('base_original_price').'';
                echo ' + (tax_amount '.$this->getData('tax_amount').'';
                echo ' - discount_amount '.$this->getData('discount_amount').')';
                echo '/ '.$this->getData('qty').'<br>';
                echo ' ::: = '.($this->getData('base_original_price')
                //+($this->getData('tax_amount')-$this->getData('discount_amount'))/$this->getData('qty')).'<br>';*/
                return (double) $this->getData('base_original_price')
                    + ( $this->getData('tax_amount') - $this->getData('discount_amount') ) / $this->getData('qty');
            case 'price+tax-discount':
                return (double) $this->getData('price_incl_tax');
            case 'weight':
                if ($this->_type == 'bundle' && $this->getProduct()->getData('weight_type') == 0) {
                    return (double) parent::_load($name);
                }
                return $this->getData('qty') * $this->getProduct()->getData('weight');
            default: 
                return parent::_load($name);
        }
    }

    public function __toString()
    {
        return $this->getData('name') . ' (id:' . $this->getData('product_id') . ', sku:' . $this->getData('sku') . ')';
    }

    protected function _getOption($optionName, $getById = false)
    {
        $options = $this->_getOptions();
        if (isset($options[$optionName])) {
            return $getById ? $options[$optionName]['value_id'] : $options[$optionName]['value'];
        }
        else return null;
    }

    protected function _getItem($what)
    {
        $getParent = isset($this->_getOptions[$this->_type][$what]) && $this->_getOptions[$this->_type][$what] == true;
        /*echo 'getItem(' . $what . ')[' . $this->_type . '] = ' . ($getParent ? 'parent' : 'self') . '<br/>';
        print_r($this->_getOptions[$this->_type]);*/
        return $getParent ? $this->_parentItem : $this->_item;
    }

    protected function _getOptions()
    {
        if (isset($this->_options)) return $this->_options;
        $item = $this->_getItem('load_item_option_on_parent');
        $options = array();
        if ($optionIds = $item->getOptionByCode('option_ids')) {
            foreach (explode(',', $optionIds->getValue()) as $optionId) {
                if ($option = $item->getProduct()->getOptionById($optionId)) {
                    $quoteItemOption = $item->getOptionByCode('option_' . $option->getId());

                    $group = $option->groupFactory($option->getType())
                        ->setOption($option)
                        ->setQuoteItemOption($quoteItemOption);

                    $label = $option->getTitle();
                    $options[$label] = array(
                        'label' => $label,
                        'value' => $group->getFormattedOptionValue($quoteItemOption->getValue()),
                        'print_value' => $group->getPrintableOptionValue($quoteItemOption->getValue()),
                        'value_id' => $quoteItemOption->getValue(),
                        'option_id' => $option->getId(),
                        'option_type' => $option->getType(),
                        'custom_view' => $group->isCustomizedView()
                    );
                }
            }
        }
        if ($addOptions = $item->getOptionByCode('additional_options')) {
            $options = array_merge($options, unserialize($addOptions->getValue()));
        }
        $this->_options = $options;
        return $this->_options;
    }
}
