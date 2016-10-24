<?php
/**
 * Copyright © 2008-2016 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_Abstract
{
    protected $additionalAttributes = array();
    protected $_attributes;
    protected $_loadedObject = false;
    protected $_data;

    public function __construct($arguments = null)
    {
        $this->_data = (array)$arguments;
        //echo '<pre>Owebia_Shipping2_Model_Os2_Data_Abstract::__construct<br/>';foreach ($this->_data as $n => $v)
        //{echo "\t$n => ".(is_object($v) ? get_class($v) : (is_array($v) ? 'array' : $v))."<br/>";}
    }

    protected function _loadObject()
    {
        return null;
    }

    protected function _getObject()
    {
        if ($this->_loadedObject === false) $this->_loadedObject = $this->_loadObject();
        //foreach ($this->_loadedObject->getData() as $index => $value) echo "$index = $value<br/>";
        return $this->_loadedObject;
    }

    protected function _load($name)
    {
        $object = $this->_getObject();
        if (!$object) return null;
        /*echo get_class($this).'.getData('.$name.')'.$object->getData($name).'<br/>';
        foreach ($object->getData() as $index => $value) echo "$index = $value<br/>";*/
        return $object->getData($name);
    }

    public function __sleep()
    {
        if (isset($this->_attributes)) return $this->_attributes; 
        $this->_attributes = array_unique(array_merge(array_keys($this->_data), $this->additionalAttributes));
        /*usort($this->_attributes, function($v1, $v2){
            if ($v1=='id') return -1;
            if ($v2=='id') return 1;
            if ($v2=='*') return -1;
            if ($v1=='*') return 1;
            return $v1==$v2 ? 0 : ($v1<$v2 ? -1 : 1);
        });*/
        return $this->_attributes;
    }

    public function getData($name)
    {
        /*$name2 = str_replace('.', '_', $name);
        if (isset($this->_data[$name2])) return $this->_data[$name2];*/
        //if (isset($this->_data[$name])) return $this->_data[$name]; // pb if id is null
        if (!is_array($this->_data)) $this->_data = array();
        if (array_key_exists($name, $this->_data)) return $this->_data[$name];
        //if (in_array($name, $this->additionalAttributes)) $this->_data[$name] = $this->_load($name);
        $this->_data[$name] = $this->_load($name);
        return $this->_data[$name];
    }

    public function __get($name)
    {
        return $this->getData($name);
    }
}
