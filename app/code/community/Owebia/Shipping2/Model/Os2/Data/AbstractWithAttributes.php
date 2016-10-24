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

class Owebia_Shipping2_Model_Os2_Data_AbstractWithAttributes extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
    protected function _load($name)
    {
        $elems = explode('.', $name, $limit=2);
        $count = count($elems);
        if ($count==2) {
            switch ($elems[0]) {
                case 'a':
                case 'attribute':
                    $name = $elems[1];
                    return $this->_getAttribute($name);
            }
        }
        //return parent::_load($name);
        return $this->_getAttribute($name);
    }

    protected function _getAttribute($attributeName)
    {
        $getValue = false;
        if (substr($attributeName, strlen($attributeName)-6, 6)=='.value') {
            $getValue = true;
            $attributeName = substr($attributeName, 0, strlen($attributeName)-6);
        }

        $object = $this->_getObject();
        if (!$object) return null;
        $attribute = $object->getResource()->getAttribute($attributeName);
        if (!$attribute) return null;

        $attributeFrontend = $attribute->getFrontend();
        $inputType = $attributeFrontend->getInputType();
        switch ($inputType) {
            case 'select' :
                //echo 'attributeName:'.$object->getData($attributeName).', '.$attributeFrontend->getValue($object).';<br/>';
                $value = !$getValue ? $object->getData($attributeName) : $attributeFrontend->getValue($object);
                break;
            default :
                $value = $object->getData($attributeName);
                break;
        }
        return $value;
    }
}
