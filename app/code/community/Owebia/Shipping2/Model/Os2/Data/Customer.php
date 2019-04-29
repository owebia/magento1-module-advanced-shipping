<?php
/**
 * Copyright © 2008-2019 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_Customer extends Owebia_Shipping2_Model_Os2_Data_AbstractWithAttributes
{
    protected $_additionalAttributes = array('*');

    public function __construct($arguments)
    {
        parent::__construct(
            array(
                'id' => isset($arguments['quote']) ? $arguments['quote']->getData('customer_id') : null,
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
