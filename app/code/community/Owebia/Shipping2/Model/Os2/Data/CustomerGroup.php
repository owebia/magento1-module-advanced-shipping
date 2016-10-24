<?php

/**
 * Copyright (c) 2008-14 Owebia
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @website    http://www.owebia.com/
 * @project    Magento Owebia Shipping 2 module
 * @author     Antoine Lemoine
 * @license    http://www.opensource.org/licenses/MIT  The MIT License (MIT)
**/

class Owebia_Shipping2_Model_Os2_Data_CustomerGroup extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
    protected static $customerGroups = null;

    public static function getCollection()
    {
        if (!self::$customerGroups) {
            $collection = Mage::getModel('customer/group')->getCollection();
            $customerGroups = array();
            foreach ($collection as $customerGroup) {
                $customerGroups[$customerGroup->getId()] = $customerGroup->getCustomerGroupCode();
            }
            self::$customerGroups = $customerGroups;
        }
        return self::$customerGroups;
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

    protected $additionalAttributes = array('*');

    public function __construct($arguments = null)
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
