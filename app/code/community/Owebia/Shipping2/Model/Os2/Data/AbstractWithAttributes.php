<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_AbstractWithAttributes extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
    protected function _load($name)
    {
        $elems = explode('.', $name, $limit = 2);
        $count = count($elems);
        if ($count == 2) {
            switch ($elems[0]) {
                case 'a':
                case 'attribute':
                    $name = $elems[1];
                    return $this->_getAttribute($name);
            }
        }
        return $this->_getAttribute($name);
    }

    protected function _getAttribute($attributeName)
    {
        $getValue = false;
        if (substr($attributeName, strlen($attributeName) - 6, 6) == '.value') {
            $getValue = true;
            $attributeName = substr($attributeName, 0, strlen($attributeName) - 6);
        }

        $object = $this->_getObject();
        if (!$object) return null;
        $attribute = $object->getResource()->getAttribute($attributeName);
        if (!$attribute) return null;

        $attributeFrontend = $attribute->getFrontend();
        $inputType = $attributeFrontend->getInputType();
        switch ($inputType) {
            case 'select' :
                $value = !$getValue ? $object->getData($attributeName) : $attributeFrontend->getValue($object);
                break;
            default :
                $value = $object->getData($attributeName);
                break;
        }
        return $value;
    }
}
