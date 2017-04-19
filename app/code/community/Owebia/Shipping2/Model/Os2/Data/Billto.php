<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_Billto extends Owebia_Shipping2_Model_Os2_Data_Address
{
    protected function _loadObject()
    {
        $quote = Mage::getModel('checkout/cart')->getQuote();
        $address = $quote->getBillingAddress();
        return $address;
    }
}
