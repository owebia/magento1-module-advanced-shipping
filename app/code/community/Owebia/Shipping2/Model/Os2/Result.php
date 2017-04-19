<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Result
{
    protected $_configParser;
    public $success;
    public $result;

    public function setConfigParser($configParser)
    {
        $this->_configParser = $configParser;
        return $this;
    }

    public function setSuccess($success)
    {
        $this->success = $success;
        return $this;
    }

    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

    public function __toString()
    {
        return $this->_configParser->toString($this->result);
    }
}
