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

class Owebia_Shipping2_Model_Os2_Data_Abstract
{
	protected $additional_attributes = array();
	protected $_attributes;
	protected $_loaded_object = false;
	protected $_data;

	public function __construct($arguments=null) {
		$this->_data = (array)$arguments;
		//echo '<pre>Owebia_Shipping2_Model_Os2_Data_Abstract::__construct<br/>';foreach ($this->_data as $n => $v){echo "\t$n => ".(is_object($v) ? get_class($v) : (is_array($v) ? 'array' : $v))."<br/>";}
	}

	protected function _loadObject() {
		return null;
	}

	protected function _getObject() {
		if ($this->_loaded_object===false) $this->_loaded_object = $this->_loadObject();
		//foreach ($this->_loaded_object->getData() as $index => $value) echo "$index = $value<br/>";
		return $this->_loaded_object;
	}

	protected function _load($name) {
		$object = $this->_getObject();
		if (!$object) return null;
		/*echo get_class($this).'.getData('.$name.')'.$object->getData($name).'<br/>';
		foreach ($object->getData() as $index => $value) echo "$index = $value<br/>";*/
		return $object->getData($name);
	}

	public function __sleep() {
		if (isset($this->_attributes)) return $this->_attributes; 
		$this->_attributes = array_unique(array_merge(array_keys($this->_data), $this->additional_attributes));
		/*usort($this->_attributes, function($v1, $v2){
			if ($v1=='id') return -1;
			if ($v2=='id') return 1;
			if ($v2=='*') return -1;
			if ($v1=='*') return 1;
			return $v1==$v2 ? 0 : ($v1<$v2 ? -1 : 1);
		});*/
		return $this->_attributes;
	}

	public function __get($name) {
		/*$name2 = str_replace('.', '_', $name);
		if (isset($this->_data[$name2])) return $this->_data[$name2];*/
		//if (isset($this->_data[$name])) return $this->_data[$name]; // pb if id is null
		if (!is_array($this->_data)) $this->_data = array();
		if (array_key_exists($name, $this->_data)) return $this->_data[$name];
		//if (in_array($name, $this->additional_attributes)) $this->_data[$name] = $this->_load($name);
		$this->_data[$name] = $this->_load($name);
		return $this->_data[$name];
	}
}

?>