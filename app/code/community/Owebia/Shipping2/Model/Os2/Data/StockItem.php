<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_StockItem extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
    protected function _loadObject()
    {
        return Mage::getModel('cataloginventory/stock_item')->loadByProduct($this->getData('product_id'));
    }

    protected function _load($name)
    {
        switch ($name) {
            case 'is_in_stock':
                return (bool)parent::_load($name);
            case 'qty':
                $qty = parent::_load($name);
                return $this->getData('is_qty_decimal') ? (float)$qty : (int)$qty;
            default:
                return parent::_load($name);
        }
    }
}
