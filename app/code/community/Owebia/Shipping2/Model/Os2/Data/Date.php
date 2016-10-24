<?php
/**
 * Copyright © 2008-2016 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_Date extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
    private $_timestamp;

    public function __construct()
    {
        parent::__construct();
        $this->_timestamp = (int)Mage::getModel('core/date')->timestamp();
    }

    public function __sleep()
    {
        return array('timestamp', 'year', 'month', 'day', 'hour', 'minute', 'second', 'weekday');
    }

    protected function _load($name)
    {
        switch ($name) {
            case 'timestamp':
                return $this->_timestamp;
            case 'year':
                return (int)date('Y', $this->_timestamp);
            case 'month':
                return (int)date('m', $this->_timestamp);
            case 'day':
                return (int)date('d', $this->_timestamp);
            case 'hour':
                return (int)date('H', $this->_timestamp);
            case 'minute':
                return (int)date('i', $this->_timestamp);
            case 'second':
                return (int)date('s', $this->_timestamp);
            case 'weekday':
                return (int)date('w', $this->_timestamp);
        }
        return null;
    }
}
