<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_Quote extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
    protected $_additionalAttributes = array(
        'subtotal', 'subtotal_with_discount', 'grand_total', 'base_subtotal',
        'base_subtotal_with_discount', 'base_grand_total', '*',
    );

    protected function _loadObject()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            // Backend
            $sessionQuote = Mage::getSingleton('adminhtml/session_quote');
            if (!$sessionQuote->getQuoteId()) return; // Avoid infinite loop
            $quote = $sessionQuote->getQuote();
        } else {
            // Frontend
            $session = Mage::getSingleton('checkout/session');
            if (!$session->getQuoteId()) return; // Avoid infinite loop
            $quote = $session->getQuote();
        }

        return $quote;
    }
}
