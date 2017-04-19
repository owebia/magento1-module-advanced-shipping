<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Message
{
    public $type;
    public $message;
    public $args;

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    public function setArgs($args)
    {
        $this->args = $args;
        return $this;
    }

    public function __toString()
    {
        return vsprintf($this->message, $this->args);
    }
}
