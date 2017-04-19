<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_AttributeSet extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
    protected function _loadObject()
    {
        return Mage::getModel('eav/entity_attribute_set')->load($this->getData('id'));
    }

    protected function _load($name)
    {
        switch ($name) {
            case 'name':
                return $this->getData('attribute_set_name');
            default:
                return parent::_load($name);
        }
    }
}
