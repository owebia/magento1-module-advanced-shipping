<?php
/**
 * Copyright © 2008-2016 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Result
{
    protected $_configParser;
    public $success;
    public $result;

    public function __construct($configParser, $success, $result = null)
    {
        $this->_configParser = $configParser;
        $this->success = $success;
        $this->result = $result;
    }

    public function __toString()
    {
        return $this->_configParser->toString($this->result);
    }
}
