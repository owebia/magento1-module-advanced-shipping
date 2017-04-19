<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_Store extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
    protected $_store;

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
                return $this->_store->getConfig('general/store_information/' . $name);
        }
        return null;
    }
}
