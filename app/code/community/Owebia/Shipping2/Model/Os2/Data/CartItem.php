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

class Owebia_Shipping2_Model_Os2_Data_CartItem extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
	private $parent_cart_item;
	private $cart_product;
	private $loaded_product;
	private $quantity;
	private $categories;
	
	protected $_product;
	protected $_item;
	protected $_parent_item;
	protected $_type;
	protected $_options;
	protected $_get_options;

	public function __construct($arguments) {
		parent::__construct();
		$this->_item = $item = $arguments['item'];
		$this->_parent_item = $parent_item = $arguments['parent_item'];
		$this->_get_options = $options = $arguments['options'];
		$this->_product = null;
		$this->_type = $parent_item ? $parent_item->getProduct()->getTypeId() : $item->getProduct()->getTypeId();
		$this->_loaded_object = $this->_getItem('load_item_data_on_parent');

		if (false) {
			echo '---------------------------------<br/>';
			foreach ($this->_item->getData() as $index => $value) {
				$value = is_object($value) ? get_class($value) : (is_array($value) ? 'array' : $value);
				echo "$index = $value<br/>";
			}
			if ($parent_item) {
				echo '----- parent -----<br/>';
				foreach ($parent_item->getData() as $index => $value) echo "$index = $value<br/>";
			}
			echo 'type:'.$this->_type.'<br/>';
			echo 'sku:'.$this->sku.'<br/>';
		}
	}

	public function getProduct() {
		if (!isset($this->_product)) {
			//echo $this->_loaded_object->getData('product_id').', '.$this->_getItem('load_product_data_on_parent')->getData('product_id').'<br/>';
			$product_id = $this->_getItem('load_product_data_on_parent')->getData('product_id');
			$this->_product = Mage::getModel('owebia_shipping2/Os2_Data_Product', array('id' => $product_id));
		}
		return $this->_product;
	}

	protected function _load($name) {
		$elems = explode('.', $name, $limit=2);
		$count = count($elems);
		$last_index = $count-1;
		if ($count==2) {
			switch ($elems[0]) {
				case 'o':
				case 'option':
					return $this->_getOption($elems[1]);
			}
		}
		switch ($name) {
			case 'price-tax+discount': return (double)$this->base_original_price-$this->discount_amount/$this->qty;
			case 'price-tax-discount': return (double)$this->base_original_price;
			case 'price+tax+discount':
				/*echo 'base_original_price '.$this->base_original_price.'';
				echo ' + (tax_amount '.$this->tax_amount.'';
				echo ' - discount_amount '.$this->discount_amount.')';
				echo '/ '.$this->qty.'<br>';
				echo ' ::: = '.($this->base_original_price+($this->tax_amount-$this->discount_amount)/$this->qty).'<br>';*/
				return (double)$this->base_original_price+($this->tax_amount-$this->discount_amount)/$this->qty;
			case 'price+tax-discount': return (double)$this->price_incl_tax;
			case 'weight':
				if ($this->_type=='bundle' && $this->getProduct()->weight_type==0) {
					return (double)parent::_load($name);
				}
				return $this->qty*$this->getProduct()->weight;
			default: 
				return parent::_load($name);
		}
	}

	public function __toString() {
		return $this->name.' (id:'.$this->product_id.', sku:'.$this->sku.')';
	}

	protected function _getOption($option_name, $get_by_id=false) {
		$options = $this->_getOptions();
		if (isset($options[$option_name])) return $get_by_id ? $options[$option_name]['value_id'] : $options[$option_name]['value'];
		else return null;
	}

	protected function _getItem($what) {
		$get_parent = isset($this->_get_options[$this->_type][$what]) && $this->_get_options[$this->_type][$what]==true;
		/*echo 'getItem('.$what.')['.$this->_type.'] = '.($get_parent ? 'parent' : 'self').'<br/>';
		print_r($this->_get_options[$this->_type]);*/
		return $get_parent ? $this->_parent_item : $this->_item;
	}

	protected function _getOptions() {
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

?>