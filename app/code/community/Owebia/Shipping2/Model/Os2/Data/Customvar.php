<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_Customvar extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
    public function __sleep()
    {
        return array('*');
    }

    protected function _load($name)
    {
        return Mage::getModel('core/variable')
            ->setStoreId(Mage::app()->getStore()->getId()) // to get store value
            ->loadByCode($name)->getValue('text');
    }
}
