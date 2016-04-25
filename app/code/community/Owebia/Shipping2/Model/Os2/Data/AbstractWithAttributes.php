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
        $last_index = $count-1;
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

    protected function _getAttribute($attribute_name)
    {
        $get_value = false;
        if (substr($attribute_name, strlen($attribute_name)-6, 6)=='.value') {
            $get_value = true;
            $attribute_name = substr($attribute_name, 0, strlen($attribute_name)-6);
        }

        $object = $this->_getObject();
        if (!$object) return null;
        $attribute = $object->getResource()->getAttribute($attribute_name);
        if (!$attribute) return null;

        $attribute_frontend = $attribute->getFrontend();
        $input_type = $attribute_frontend->getInputType();
        switch ($input_type) {
            case 'select' :
                //echo 'attribute_name:'.$object->getData($attribute_name).', '.$attribute_frontend->getValue($object).';<br/>';
                $value = !$get_value ? $object->getData($attribute_name) : $attribute_frontend->getValue($object);
                break;
            default :
                $value = $object->getData($attribute_name);
                break;
        }
        return $value;
    }
}
