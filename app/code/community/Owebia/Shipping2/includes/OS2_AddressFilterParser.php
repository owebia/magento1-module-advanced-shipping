<?php
/**
 * Copyright © 2008-2016 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class OS2_AddressFilterParser
{
    protected $_input = null;
    protected $_position = null;
    protected $_bufferStart = null;

    protected $_output = '';
    protected $_level = null;
    protected $_parentLevel = null;
    protected $_regexp = false;
    protected $_litteral = false;
    protected $_litteralQuote = null;

    public function parse($input)
    {
        $this->current = array();

        $this->_input = $input;
        $this->length = strlen($this->_input);
        // look at each character
        $join = ' && ';
        for ($this->_position = 0; $this->_position < $this->length; $this->_position++) {
            $char = $this->_input[$this->_position];
            switch ($char) {
                case ')':
                    if ($this->_regexp) break;
                    if ($this->_litteral) break;
                    $this->push($this->buffer() . ')');
                    $this->_parentLevel = null;
                    break;
                case ' ':
                    if ($this->_regexp) break;
                    if ($this->_litteral) break;
                    $this->push($this->buffer());
                    break;
                case '-':
                    if ($this->_regexp) break;
                    if ($this->_litteral) break;
                    $this->push($this->buffer());
                    $join = ' && !';
                    break;
                case ',':
                    if ($this->_regexp) break;
                    if ($this->_litteral) break;
                    $this->push($this->buffer());
                    $this->push(' || ');
                    break;
                case '(':
                    if ($this->_regexp) break;
                    if ($this->_litteral) break;
                    $this->push($this->buffer());
                    $this->push($join, $onlyIfNotEmpty = true);
                    $this->push('(');
                    $this->_parentLevel = $this->_level;
                    $join = ' && ';
                    break;
                case "'":
                case '"':
                    if (!$this->_litteral || $this->_litteralQuote == $char) {
                        $this->_litteral = !$this->_litteral;
                        $this->_litteralQuote = $char;
                    }
                    if ($this->_bufferStart === null) {
                        $this->_bufferStart = $this->_position;
                    }
                    break;
                case '/':
                    $this->_regexp = !$this->_regexp;
                default:
                    if ($this->_bufferStart === null) {
                        $this->_bufferStart = $this->_position;
                    }
            }
        }
        $this->push($this->buffer());
        return $this->_output;
    }

    protected function escapeString($input)
    {
        return OwebiaShippingHelper::escapeString($input);
    }

    protected function buffer()
    {
        if ($this->_bufferStart !== null) {
            // extract string from buffer start to current position
            $buffer = substr($this->_input, $this->_bufferStart, $this->_position - $this->_bufferStart);
            // clean buffer
            $this->_bufferStart = null;
            // throw token into current scope
            if ($buffer == '*') {
                $buffer = 1;
            } else if ($this->_parentLevel == 'country') {
                if (preg_match('/^[A-Z]{2}$/', $buffer)) {
                    $buffer = "{{c}}==={$this->escapeString($buffer)}";
                    $this->_level = 'country';
                } else if (substr($buffer, 0, 1) == '/'
                    && (substr($buffer, strlen($buffer) - 1, 1) == '/'
                        || substr($buffer, strlen($buffer) - 2, 2) == '/i'
                    )
                ) {
                    $buffer = "preg_match('" . str_replace("'", "\\'", $buffer) . "', (string)({{p}}))";
                } else if (strpos($buffer, '*') !== false) {
                    $buffer = "preg_match('/^"
                        . str_replace(
                            array("'", '*'),
                            array("\\'", '(?:.*)'),
                            $buffer
                        )
                        . "$/', (string)({{p}}))";
                } else if (preg_match('/^"[^"]+"$/', $buffer)) {
                    $buffer = trim($buffer, '"');
                    $buffer = "({{p}}==={$this->escapeString($buffer)} || {{r}}==={$this->escapeString($buffer)})";
                } else if (preg_match('/^\'[^\']+\'$/', $buffer)) {
                    $buffer = trim($buffer, "'");
                    $buffer = "({{p}}==={$this->escapeString($buffer)} || {{r}}==={$this->escapeString($buffer)})";
                } else {
                    $buffer = "({{p}}==={$this->escapeString($buffer)} || {{r}}==={$this->escapeString($buffer)})";
                }
            } else if (preg_match('/^[A-Z]{2}$/', $buffer)) {
                $buffer = "{{c}}==={$this->escapeString($buffer)}";
                $this->_level = 'country';
            }
            return $buffer;
        }
        return null;
    }

    protected function push($text, $onlyIfNotEmpty = false)
    {
        if (isset($text)) {
            if (!$onlyIfNotEmpty || $this->_output) {
                $this->_output .= $text;
            }
        }
    }
}
