<?php
/**
 * Copyright © 2008-2016 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_Customer extends Owebia_Shipping2_Model_Os2_Data_AbstractWithAttributes
{
    protected $additionalAttributes = array('*');

    public function __construct($arguments = null)
    {
        parent::__construct(
            array(
                'id' => Mage::getModel('owebia_shipping2/Os2_Data_Quote')->getData('customer_id'),
            )
        );
    }

    protected function _loadObject()
    {
        return Mage::getModel('customer/customer')->load($this->getData('id'));
    }

    public function __toString()
    {
        return $this->getData('firstname') . ' ' . $this->getData('lastname') . ' (id:' . $this->getData('id') . ')';
    }
}
