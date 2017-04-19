<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

if (file_exists(dirname(__FILE__) . DS . 'Mage_Checkout_CartController.php')) {
    require_once 'Mage_Checkout_CartController.php';
} else {
    require_once Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'CartController.php';
}

class Owebia_Shipping2_Checkout_CartController extends Mage_Checkout_CartController
{
    /**
     * Initialize shipping information
     */
    public function estimatePostAction()
    {
        $country    = (string) $this->getRequest()->getParam('country_id');
        $postcode   = (string) $this->getRequest()->getParam('estimate_postcode');
        $city       = (string) $this->getRequest()->getParam('estimate_city');
        $regionId   = (string) $this->getRequest()->getParam('region_id');
        $region     = (string) $this->getRequest()->getParam('region');

        $this->_getQuote()->getShippingAddress()
            ->setCountryId($country)
            ->setCity($city)
            ->setPostcode($postcode)
            ->setRegionId($regionId)
            ->setRegion($region)
            ->setCollectShippingRates(true);

        /*<owebia>*/
        // Recalcul des totaux
        $this->_getQuote()->collectTotals();
        /*</owebia>*/

        $this->_getQuote()->save();
        $this->_goBack();
    }
}
