<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_Abstract
{
    protected $_additionalAttributes = array();
    protected $_attributes;
    protected $_loadedObject = false;
    protected $_data;

    public function __construct($arguments = null)
    {
        $this->_data = (array)$arguments;
    }

    protected function _loadObject()
    {
        return null;
    }

    protected function _getObject()
    {
        if ($this->_loadedObject === false) $this->_loadedObject = $this->_loadObject();
        return $this->_loadedObject;
    }

    protected function _load($name)
    {
        $object = $this->_getObject();
        if (!$object) return null;
        return $object->getData($name);
    }

    public function __sleep()
    {
        if (isset($this->_attributes)) return $this->_attributes; 
        $this->_attributes = array_unique(array_merge(array_keys($this->_data), $this->_additionalAttributes));
        return $this->_attributes;
    }

    public function getData($name)
    {
        if (!is_array($this->_data)) $this->_data = array();
        if (array_key_exists($name, $this->_data)) return $this->_data[$name];
        $this->_data[$name] = $this->_load($name);
        return $this->_data[$name];
    }

    public function __get($name)
    {
        return $this->getData($name);
    }
}
