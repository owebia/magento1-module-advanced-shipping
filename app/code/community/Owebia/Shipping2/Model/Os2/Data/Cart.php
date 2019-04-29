<?php
/**
 * Copyright © 2008-2019 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_Cart extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
    protected $_additionalAttributes = array('coupon_code', 'weight_unit', 'weight_for_charge', 'free_shipping');
    protected $_freeShipping;
    protected $_items;
    protected $_quote;
    protected $_options;

    public function __construct($arguments)
    {
        parent::__construct();
        $request = $arguments['request'];
        $this->_quote = $arguments['quote'];
        $this->_options = $arguments['options'];

        // Bad value of package_value_with_discount
        // when "Apply Customer Tax = After discount" and "Apply Discount On Prices = Including tax"

        $this->_data = array(
            // Do not use quote to retrieve values, totals are not available
            // package_value and package_value_with_discount : Bad value in backoffice orders
            'price-tax+discount' => null,
            'price-tax-discount' => null,
            'price+tax+discount' => null,
            'price+tax-discount' => null,
            'weight' => $request->getData('package_weight'),
            'qty' => $request->getData('package_qty'),
            'free_shipping' => $request->getData('free_shipping'),
        );

        $cartItems = array();
        $items = $request->getAllItems();
        $quoteTotalCollected = false;
        $bundleProcessChildren = isset($this->_options['bundle']['process_children'])
            && $this->_options['bundle']['process_children'];
        foreach ($items as $item) {
            $product = $item->getProduct();
            if ($product instanceof Mage_Catalog_Model_Product) {
                $key = null;
                if ($item instanceof Mage_Sales_Model_Quote_Address_Item) { // Multishipping
                    $key = $item->getQuoteItemId();
                } else if ($item instanceof Mage_Sales_Model_Quote_Item) { // Onepage checkout
                    $key = $item->getId();
                }

                // If no item id set on cart item, generate a spurious one
                if (empty($key)) {
                    $key = md5($product->getId() . '_' . count($cartItems));
                }

                $cartItems[$key] = $item;
            }
        }

        // Discount includes tax only when the option "Including Tax" for "Catalog Prices" is selected
        $doesDiscountIncludeTax = Mage::helper('tax')->priceIncludesTax($request->getStoreId());

        // Do not use quote to retrieve values, totals are not available
        $totalInclTaxWithoutDiscount = 0;
        $totalExclTaxWithoutDiscount = 0;
        $totalInclTaxWithDiscount = 0;
        $totalExclTaxWithDiscount = 0;
        $this->_items = array();
        foreach ($cartItems as $item) {
            $type = $item->getProduct()->getTypeId();
            $parentItemId = $item->getData('parent_item_id');
            $parentItem = isset($cartItems[$parentItemId]) ? $cartItems[$parentItemId] : null;
            $parentType = isset($parentItem) ? $parentItem->getProduct()->getTypeId() : null;
            if ($type != 'configurable') {
                if ($type == 'bundle' && $bundleProcessChildren) {
                    $this->_data['qty'] -= $item->getQty();
                    continue;
                }
                if ($parentType == 'bundle') {
                    if (!$bundleProcessChildren) continue;
                    else $this->_data['qty'] += $item->getQty();
                }
                $this->_items[] = Mage::getModel(
                    'owebia_shipping2/Os2_Data_CartItem',
                    array('item' => $item, 'parent_item' => $parentItem, 'options' => $this->_options)
                );
            }

            $baseRowTotalExclTax = $item->getData('base_row_total');
            $taxPercent = $item->getData('tax_percent');
            $baseDiscountAmount = $item->getData('base_discount_amount');
            $inclTaxFactor = 1 + $taxPercent / 100;
            $discountExclTax = $doesDiscountIncludeTax ? $baseDiscountAmount / $inclTaxFactor : $baseDiscountAmount;
            $discountInclTax = $doesDiscountIncludeTax ? $baseDiscountAmount : $baseDiscountAmount * $inclTaxFactor;

            $totalExclTaxWithoutDiscount += $baseRowTotalExclTax;
            $totalExclTaxWithDiscount += $baseRowTotalExclTax - $discountExclTax;
            $totalInclTaxWithDiscount += $item->getData('base_row_total_incl_tax') - $discountInclTax;
            $totalInclTaxWithoutDiscount += $item->getData('base_row_total_incl_tax');
        }

        $this->_data['price-tax+discount'] = $totalExclTaxWithDiscount;
        $this->_data['price-tax-discount'] = $totalExclTaxWithoutDiscount;
        $this->_data['price+tax+discount'] = $totalInclTaxWithDiscount;
        $this->_data['price+tax-discount'] = $totalInclTaxWithoutDiscount;
    }

    protected function _load($name)
    {
        switch ($name) {
            case 'weight_for_charge':
                $weightForCharge = $this->getData('weight');
                foreach ($this->_items as $item) {
                    if ($item->getData('free_shipping')) $weightForCharge -= $item->getData('weight');
                }
                return $weightForCharge;
            case 'coupon_code':
                $couponCode = null;
                return $this->_quote->getData('coupon_code');
            case 'weight_unit':
                return Mage::getStoreConfig('owebia_shipping2/general/weight_unit');
        }
        return parent::_load($name);
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'items':
                return $this->_items = $value;
        }
        return parent::__set($name, $value);
    }

    public function getData($name)
    {
        switch ($name) {
            case 'items':
                return $this->_items;
            case 'free_shipping':
                if (isset($this->_freeShipping)) return $this->_freeShipping;
                $freeShipping = parent::getData('free_shipping');
                if (!$freeShipping) {
                    foreach ($this->_items as $item) {
                        $freeShipping = $item->getData('free_shipping');
                        if (!$freeShipping) break;
                    }
                }
                return $this->_freeShipping = $freeShipping;
        }
        return parent::getData($name);
    }
}
