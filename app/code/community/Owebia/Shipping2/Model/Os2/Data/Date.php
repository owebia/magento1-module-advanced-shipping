<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_Date extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
    protected $_timestamp;

    public function __construct()
    {
        parent::__construct();
        $this->_timestamp = (int)Mage::getModel('core/date')->timestamp();
    }

    public function __sleep()
    {
        return array('timestamp', 'year', 'month', 'day', 'hour', 'minute', 'second', 'weekday');
    }

    protected function getDate($format)
    {
        return (int)Mage::getModel('core/date')
            ->date($format, $this->_timestamp);
    }

    protected function _load($name)
    {
        switch ($name) {
            case 'timestamp':
                return $this->_timestamp;
            case 'year':
                return $this->getDate('Y');
            case 'month':
                return $this->getDate('m');
            case 'day':
                return $this->getDate('d');
            case 'hour':
                return $this->getDate('H');
            case 'minute':
                return $this->getDate('i');
            case 'second':
                return $this->getDate('s');
            case 'weekday':
                return $this->getDate('w');
        }
        return null;
    }
}
