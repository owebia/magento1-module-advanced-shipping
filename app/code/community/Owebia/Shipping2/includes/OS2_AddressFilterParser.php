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

class OS2_AddressFilterParser
{
    protected $input = null;
    protected $position = null;
    protected $bufferStart = null;

    protected $output = '';
    protected $level = null;
    protected $parentLevel = null;
    protected $regexp = false;
    protected $litteral = false;
    protected $litteralQuote = null;
    protected $caseInsensitive = false;

    public function parse($input)
    {
        $this->current = array();

        $this->input = $input;
        $this->length = strlen($this->input);
        // look at each character
        $join = ' && ';
        for ($this->position = 0; $this->position < $this->length; $this->position++) {
            $char = $this->input[$this->position];
            switch ($char) {
                case ')':
                    if ($this->regexp) break;
                    if ($this->litteral) break;
                    $this->push($this->buffer() . ')');
                    $this->parentLevel = null;
                    break;
                case ' ':
                    if ($this->regexp) break;
                    if ($this->litteral) break;
                    $this->push($this->buffer());
                    break;
                case '-':
                    if ($this->regexp) break;
                    if ($this->litteral) break;
                    $this->push($this->buffer());
                    $join = ' && !';
                    break;
                case ',':
                    if ($this->regexp) break;
                    if ($this->litteral) break;
                    $this->push($this->buffer());
                    $this->push(' || ');
                    break;
                case '(':
                    if ($this->regexp) break;
                    if ($this->litteral) break;
                    $this->push($this->buffer());
                    $this->push($join, $onlyIfNotEmpty = true);
                    $this->push('(');
                    $this->parentLevel = $this->level;
                    $join = ' && ';
                    break;
                case "'":
                case '"':
                    if (!$this->litteral || $this->litteralQuote == $char) {
                        $this->litteral = !$this->litteral;
                        $this->litteralQuote = $char;
                    }
                    if ($this->bufferStart === null) {
                        $this->bufferStart = $this->position;
                    }
                    break;
                case '/':
                    $this->regexp = !$this->regexp;
                default:
                    if ($this->bufferStart === null) {
                        $this->bufferStart = $this->position;
                    }
            }
        }
        $this->push($this->buffer());
        return $this->output;
    }

    protected function escapeString($input)
    {
        return OwebiaShippingHelper::escapeString($input);
    }

    protected function buffer()
    {
        if ($this->bufferStart !== null) {
            // extract string from buffer start to current position
            $buffer = substr($this->input, $this->bufferStart, $this->position - $this->bufferStart);
            // clean buffer
            $this->bufferStart = null;
            // throw token into current scope
            //var_export($buffer);echo "\n";
            if ($buffer == '*') {
                $buffer = 1;
            } else if ($this->parentLevel == 'country') {
                if (preg_match('/^[A-Z]{2}$/', $buffer)) {
                    $buffer = "{{c}}==={$this->escapeString($buffer)}";
                    $this->level = 'country';
                } else if (substr($buffer, 0, 1) == '/' && (substr($buffer, strlen($buffer)-1, 1) == '/' || substr($buffer, strlen($buffer) - 2, 2) == '/i')) {
                    $caseInsensitive = substr($buffer, strlen($buffer) - 2, 2) == '/i';
                    $buffer = "preg_match('" . str_replace("'", "\\'", $buffer) . "', (string)({{p}}))";
                } else if (strpos($buffer, '*') !== false) {
                    $buffer = "preg_match('/^" . str_replace(array("'", '*'), array("\\'", '(?:.*)'), $buffer) . "$/', (string)({{p}}))";
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
                $this->level = 'country';
            }
            return $buffer;
        }
        return null;
    }

    protected function push($text, $onlyIfNotEmpty = false)
    {
        if (isset($text)) {
            if (!$onlyIfNotEmpty || $this->output) {
                $this->output .= $text;
            }
            //echo "\"$this->output\"<br/>";
        }
    }
}
