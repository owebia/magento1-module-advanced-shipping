<?php
/**
 * Copyright © 2008-2016 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_Quote extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
    protected $additionalAttributes = array(
        'subtotal', 'subtotal_with_discount', 'grand_total', 'base_subtotal',
        'base_subtotal_with_discount', 'base_grand_total', '*',
    );

    protected function _loadObject()
    {
        // Backend
        if (Mage::app()->getStore()->isAdmin()) {
            $sessionQuote = Mage::getSingleton('adminhtml/session_quote');
            if (!$sessionQuote->getQuoteId()) return; // Avoid infinite loop
            $quote = $sessionQuote->getQuote();
        }
        // Frontend
        else {
            $session = Mage::getSingleton('checkout/session');
            if (!$session->getQuoteId()) return; // Avoid infinite loop
            $quote = $session->getQuote();
        }

        return $quote;
    }
}
