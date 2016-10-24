<?php
/**
 * Copyright © 2008-2016 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Os2_Message
{
    public $type;
    public $message;
    public $args;

    public function __construct($type, $args)
    {
        $this->type = $type;
        $this->message = array_shift($args);
        $this->args = $args;
    }

    public function __toString()
    {
        return vsprintf($this->message, $this->args);
    }
}
