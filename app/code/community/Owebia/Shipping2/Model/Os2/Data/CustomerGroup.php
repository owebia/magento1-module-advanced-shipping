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

class Owebia_Shipping2_Model_Os2_Data_CustomerGroup extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
	protected static $_customer_groups = null;

	public static function getCollection()
	{
		if (!self::$_customer_groups) {
			$collection = Mage::getModel('customer/group')->getCollection();
			$customer_groups = array();
			foreach ($collection as $customer_group) {
				$customer_groups[$customer_group->getId()] = $customer_group->getCustomerGroupCode();
			}
			self::$_customer_groups = $customer_groups;
		}
		return self::$_customer_groups;
	}

	public static function readable($input)
	{
		$customer_groups = self::getCollection();
		$elems = preg_split('/\b/', $input);
		$output = '';
		foreach ($elems as $elem) {
			$output .= isset($customer_groups[$elem]) ? $customer_groups[$elem] : $elem;
		}
		return $output;
	}

	protected $additional_attributes = array('*');

	public function __construct($arguments=null)
	{
		$customer_group_id = Mage::getSingleton('customer/session')->getCustomerGroupId();
		if ($customer_group_id==0) { // Pour les commandes depuis Adminhtml
			$customer_group_id2 = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getCustomerGroupId();
			if (isset($customer_group_id2)) $customer_group_id = $customer_group_id2;
		}
		parent::__construct(array(
			'id' => $customer_group_id,
		));
	}

	protected function _load($name)
	{
		switch ($name) {
			case 'code': return $this->customer_group_code;
			default: return parent::_load($name);
		}
	}

	protected function _loadObject()
	{
		return Mage::getSingleton('customer/group')->load($this->id);
	}

	public function __toString()
	{
		return $this->code.' (id:'.$this->id.')';
	}
}
