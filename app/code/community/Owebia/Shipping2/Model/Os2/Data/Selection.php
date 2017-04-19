<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Data_Selection extends Owebia_Shipping2_Model_Os2_Data_Abstract
{
    public function set($name, $value)
    {
        $this->_data[$name] = $value;
    }
}
