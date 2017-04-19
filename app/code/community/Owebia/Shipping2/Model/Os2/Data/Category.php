<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_Category extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
    protected function _loadObject()
    {
        return Mage::getModel('catalog/category')->load($this->getData('id'));
    }

    public function __toString()
    {
        return $this->getData('name') . ' (id:' . $this->getData('id') . ', url_key:' . $this->getData('url_key') . ')';
    }
}
