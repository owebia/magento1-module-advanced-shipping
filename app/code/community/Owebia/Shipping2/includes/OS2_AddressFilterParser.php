<?php
/**
 * Copyright © 2008-2016 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

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
