<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_CustomerGroup extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
    protected static $_customerGroups = null;

    public static function getCollection()
    {
        if (!self::$_customerGroups) {
            $collection = Mage::getModel('customer/group')->getCollection();
            $customerGroups = array();
            foreach ($collection as $customerGroup) {
                $customerGroups[$customerGroup->getId()] = $customerGroup->getCustomerGroupCode();
            }
            self::$_customerGroups = $customerGroups;
        }
        return self::$_customerGroups;
    }

    public static function readable($input)
    {
        $customerGroups = self::getCollection();
        $elems = preg_split('/\b/', $input);
        $output = '';
        foreach ($elems as $elem) {
            $output .= isset($customerGroups[$elem]) ? $customerGroups[$elem] : $elem;
        }
        return $output;
    }

    protected $_additionalAttributes = array('*');

    public function __construct()
    {
        $customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
        if ($customerGroupId == 0) { // Pour les commandes depuis Adminhtml
            $adminCustomerGroupId = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getCustomerGroupId();
            if (isset($adminCustomerGroupId)) {
                $customerGroupId = $adminCustomerGroupId;
            }
        }
        parent::__construct(
            array(
                'id' => $customerGroupId,
            )
        );
    }

    protected function _load($name)
    {
        switch ($name) {
            case 'code':
                return $this->getData('customer_group_code');
            default:
                return parent::_load($name);
        }
    }

    protected function _loadObject()
    {
        return Mage::getSingleton('customer/group')->load($this->getData('id'));
    }

    public function __toString()
    {
        return $this->getData('code') . ' (id:' . $this->getData('id') . ')';
    }
}
