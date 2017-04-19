<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_Address extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
    protected $_additionalAttributes = array('country_id', 'country_name', 'postcode');

    protected function _load($name)
    {
        switch ($name) {
            case 'country_name':
                return Mage::getModel('directory/country')->load($this->getData('country_id'))->getName();
        }
        return parent::_load($name);
    }
}
