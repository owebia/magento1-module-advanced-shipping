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

class Owebia_Shipping2_Model_Os2_Data_Store extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
	private $_store;

	public function __construct($arguments=null)
	{
		parent::__construct();
		if ($arguments && isset($argument['id'])) $this->_store = Mage::app()->getStore((int)$argument['id']);
		else $this->_store = Mage::app()->getStore();
	}

	public function __sleep()
	{
		return array('id', 'code', 'name', 'address', 'phone');
	}

	protected function _load($name)
	{
		switch ($name) {
			case 'id':
				return $this->_store->getId();
			case 'code':
				return $this->_store->getData($name);
			case 'name':
			case 'address':
			case 'phone':
				return $this->_store->getConfig('general/store_information/'.$name);
		}
		return null;
	}
}
