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

// moved in app/code/community/Owebia/Shipping2/Model/Carrier/Abstract.php
//require_once dirname(__FILE__).'/OS2_AddressFilterParser.php';

class OwebiaShippingHelper
{
    const FLOAT_REGEX = '[-]?\d+(?:[.]\d+)?';
    const COUPLE_REGEX = '(?:[0-9.]+|\*) *(?:\[|\])? *\: *[0-9.]+';

    public static $DEBUG_INDEX_COUNTER = 0;
    public static $UNCOMPRESSED_STRINGS = array(
        ' product.attribute.',
        ' item.option.',
        '{product.attribute.',
        '{item.option.',
        '{product.',
        '{cart.',
        '{selection.',
    );
    public static $COMPRESSED_STRINGS = array(
        ' p.a.',
        ' item.o.',
        '{p.a.',
        '{item.o.',
        '{p.',
        '{c.',
        '{s.',
    );

    public static function esc($input)
    {
        $input = htmlspecialchars($input, ENT_NOQUOTES, 'UTF-8');
        return preg_replace('/&lt;(\/?)span([^&]*)&gt;/', '<\1span\2>', $input);
    }

    public static function toString($value)
    {
        if (!isset($value)) return 'null';
        else if (is_bool($value)) return $value ? 'true' : 'false';
        else if (is_float($value)) return str_replace(',', '.', (string)$value); // To avoid locale problems
        else if (is_array($value)) return 'array(size:'.count($value).')';
        else if (is_object($value)) return get_class($value).'';
        else return $value;
    }

    public static function parseSize($size)
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size)-1]);
        switch ($last) {
            case 'g': $size *= 1024;
            case 'm': $size *= 1024;
            case 'k': $size *= 1024;
        }
        return (float)$size;
    }

    public static function formatSize($size)
    {
        $unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        return self::toString(@round($size/pow(1024, ($i=floor(log($size, 1024)))), 2)).' '.$unit[$i];
    }

    public static function getInfos()
    {
        $properties = array(
            'server_os' => PHP_OS,
            'server_software' => $_SERVER['SERVER_SOFTWARE'],
            'php_version' => PHP_VERSION,
            'memory_limit' => self::formatSize(self::parseSize(ini_get('memory_limit'))),
            'memory_usage' => self::formatSize(memory_get_usage(true)),
        );
        return $properties;
    }
    
    public static function getDefaultProcessData()
    {
        return array(
            'info'                => new OS2_Data(self::getInfos()),
            'cart'                => new OS2_Data(),
            'quote'                => new OS2_Data(),
            'selection'            => new OS2_Data(),
            'customer'            => new OS2_Data(),
            'customer_group'    => new OS2_Data(),
            'customvar'            => new OS2_Data(),
            'date'                => new OS2_Data(),
            'origin'            => new OS2_Data(),
            'shipto'            => new OS2_Data(),
            'billto'            => new OS2_Data(),
            'store'                => new OS2_Data(),
            'request'            => new OS2_Data(),
            'address_filter'    => new OS2_Data(),
        );
    }

    public static function jsonEncode($data, $beautify = false, $html = false, $level = 0, $current_indent = '')
    {
        //$html = true;
        $indent = "\t";//$html ? '&nbsp;&nbsp;&nbsp;&nbsp;' : "\t";//
        $line_break = $html ? '<br/>' : "\n";
        $new_indent = $current_indent.$indent;
        switch ($type = gettype($data)) {
            case 'NULL':
                return ($html ? '<span class=json-reserved>' : '').'null'.($html ? '</span>' : '');
            case 'boolean':
                return ($html ? '<span class=json-reserved>' : '').($data ? 'true' : 'false').($html ? '</span>' : '');
            case 'integer':
            case 'double':
            case 'float':
                return ($html ? '<span class=json-numeric>' : '').$data.($html ? '</span>' : '');
            case 'string':
                return ($html ? '<span class=json-string>' : '').'"'.str_replace(array("\\", '"', "\n", "\r"), array("\\\\", '\"', "\\n", "\\r"), $html ? htmlspecialchars($data, ENT_COMPAT, 'UTF-8') : $data).'"'.($html ? '</span>' : '');
            case 'object':
                $data = (array)$data;
            case 'array':
                $output_index_count = 0;
                $output = array();
                foreach ($data as $key => $value) {
                    if ($output_index_count!==null && $output_index_count++!==$key) {
                        $output_index_count = null;
                    }
                }
                $is_associative = $output_index_count===null;
                foreach ($data as $key => $value) {
                    if ($is_associative) {
                        $classes = array();
                        if ($key=='about') $classes[] = 'json-about';
                        if ($key=='conditions' || $key=='fees') $classes[] = 'json-formula';
                        $property_classes = array('json-property');
                        if ($level==0) $property_classes[] = 'json-id';
                        $output[] = ($html && $classes ? '<span class="'.implode(' ', $classes).'">' : '')
                            .($html ? '<span class="'.implode(' ', $property_classes).'">' : '')
                            .self::jsonEncode((string)$key)
                            .($html ? '</span>' : '').':'
                            .($beautify ? ' ' : '')
                            .self::jsonEncode($value, $beautify, $html, $level+1, $new_indent)
                            .($html && $classes ? '</span>' : '');
                    } else {
                        $output[] = self::jsonEncode($value, $beautify, $html, $level+1, $current_indent);
                    }
                }
                if ($is_associative) {
                    $classes = array();
                    if (isset($data['type']) && $data['type']=='meta') $classes[] = 'json-meta';
                    $output = ($html && $classes ? '<span class="'.implode(' ', $classes).'">' : '')
                        .'{'
                        .($beautify ? "{$line_break}{$new_indent}" : '')
                        .implode(','.($beautify ? "{$line_break}{$new_indent}" : ''), $output)
                        .($beautify ? "{$line_break}{$current_indent}" : '')
                        .'}'
                        .($html && $classes ? '</span>' : '');
                    //echo $output;
                    return $output;
                } else {
                    return '['.implode(','.($beautify ? ' ' : ''), $output).']';
                }
            default:
                return ''; // Not supported
        }
    }

    protected static function json_decode($input)
    {
        if (function_exists('json_decode')) { // PHP >= 5.2.0
            $output = json_decode($input);
            if (function_exists('json_last_error')) { // PHP >= 5.3.0
                $error = json_last_error();
                if ($error!=JSON_ERROR_NONE) throw new Exception($error);
            }
            return $output;
        } else {
            return Zend_Json::decode($input);
        }
    }

    protected static function json_encode($input)
    {
        if (function_exists('json_encode')) {
            return json_encode($input);
        } else {
            return Zend_Json::encode($input);
        }
    }

    public static function escapeString($input)
    {
        $escaped = self::json_encode($input);
        $escaped = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $escaped);
        return $escaped;
    }

    protected $_input;
    protected $_config = array();
    protected $_messages = array();
    protected $_formula_cache = array();
    protected $_expression_cache = array();
    public $debug_code = null;
    public $debug_output = '';
    public $debug_header = null;
    protected $debug_prefix = '';

    public function __construct($input, $auto_correction)
    {
        $this->_input = $input;
        $this->_parseInput($auto_correction);
    }

    public function addDebugIndent()
    {
        $this->debug_prefix .= '   ';
    }

    public function removeDebugIndent()
    {
        $this->debug_prefix = substr($this->debug_prefix, 0, strlen($this->debug_prefix)-3);
    }

    public function debug($text)
    {
        $this->debug_output .= "<p>{$this->debug_prefix}{$text}</p>";
    }

    public function getDebug()
    {
        $index = $this->debug_code.'-'.self::$DEBUG_INDEX_COUNTER++;
        $output = "<style rel=stylesheet type=\"text/css\">"
        .".osh-debug{background:#000;color:#bbb;-webkit-opacity:0.9;-moz-opacity:0.9;opacity:0.9;text-align:left;white-space:pre-wrap;overflow:auto;}"
        .".osh-debug p{margin:2px 0;}"
        .".osh-formula{color:#f90;} .osh-key{color:#0099f7;} .osh-loop{color:#ff0;}"
        .".osh-error{color:#f00;} .osh-warning{color:#ff0;} .osh-info{color:#7bf700;}"
        .".osh-debug-content{padding:10px;font-family:monospace}"
        .".osh-replacement{color:#ff3000;}"
        ."</style>"
        ."<div id=osh-debug-{$index} class=osh-debug><div class=osh-debug-content><span class=osh-close style=\"float:right;cursor:pointer;\" onclick=\"document.getElementById('osh-debug-{$index}').style.display = 'none';\">[<span style=\"padding:0 5px;color:#f00;\">X</span>]</span>"
        ."<p>{$this->debug_header}</p>{$this->debug_output}</div></div>";
        return $output;
    }

    public function initDebug($code, $process)
    {
        $header = 'DEBUG OwebiaShippingHelper.php<br/>';
        foreach ($process as $index => $process_option) {
            if (in_array($index, array('data', 'options'))) {
                $header .= '   <span class=osh-key>'.self::esc(str_replace('.', '</span>.<span class=osh-key>', $index)).'</span> &gt;&gt;<br/>';
                foreach ($process_option as $object_name => $data) {
                    if (is_object($data) || is_array($data)) {
                        $header .= '      <span class=osh-key>'.self::esc(str_replace('.', '</span>.<span class=osh-key>', $object_name)).'</span> &gt;&gt;<br/>';
                        $children = array();
                        if (is_object($data)) $children = $data->__sleep();
                        else if (is_array($data)) $children = array_keys($data);
                        foreach ($children as $name) {
                            $key = $name;
                            if ($key=='*') {
                                $header .= '         .<span class=osh-key>'.self::esc(str_replace('.', '</span>.<span class=osh-key>', $key)).'</span> = …<br/>';
                            } else {
                                if (is_object($data)) $value = $data->{$name};
                                else if (is_array($data)) $children = $data[$name];
                                $header .= '         .<span class=osh-key>'.self::esc(str_replace('.', '</span>.<span class=osh-key>', $key)).'</span> = <span class=osh-formula>'.self::esc(self::toString($value)).'</span> ('.gettype($value).')<br/>';
                            }
                        }
                    } else {
                        $header .= '      .<span class=osh-key>'.self::esc(str_replace('.', '</span>.<span class=osh-key>', $object_name)).'</span> = <span class=osh-formula>'.self::esc(self::toString($data)).'</span> ('.gettype($data).')<br/>';
                    }
                }
            } else {
                $header .= '   <span class=osh-key>'.self::esc(str_replace('.', '</span>.<span class=osh-key>', $index)).'</span> = <span class=osh-formula>'.self::esc(self::toString($process_option)).'</span> ('.gettype($process_option).')<br/>';
            }
        }
        $this->debug_code = $code;
        $this->debug_header = $header;
    }

    public function getConfig()
    {
        return $this->_config;
    }
    
    public function getConfigRow($id)
    {
        return isset($this->_config[$id]) ? $this->_config[$id] : null;
    }
    
    public function setConfig($config)
    {
        return $this->_config = $config;
    }
    
    public function getMessages()
    {
        $messages = $this->_messages;
        $this->_messages = array();
        return $messages;
    }

    public function sortProperties($k1, $k2)
    {
        $i1 = isset($this->properties_sort[$k1]) ? $this->properties_sort[$k1] : 1000;
        $i2 = isset($this->properties_sort[$k2]) ? $this->properties_sort[$k2] : 1000;
        return $i1==$i2 ? strcmp($k1, $k2) : $i1-$i2;
    }

    public function formatConfig($compress, $keys_to_remove=array(), $html = false)
    {
        $object_array = array();
        $this->properties_sort = array_flip(array(
            'type',
            'about',
            'enabled',
            'label',
            'description',
            'shipto',
            'billto',
            'origin',
            'conditions',
            'fees',
            'tracking_url',
        ));
        foreach ($this->_config as $code => $row) {
            $object = array();
            foreach ($row as $key => $property) {
                if (substr($key, 0, 1)!='*' && !in_array($key, $keys_to_remove)) {
                    $object[$key] = $property['value'];
                }
            }
            uksort($object, array($this, 'sortProperties'));
            $object_array[$code] = $object;
        }
        $output = self::jsonEncode($object_array, $beautify = !$compress, $html);
        return $compress ? $this->compress($output) : $this->uncompress($output);
    }

    public function checkConfig()
    {
        $timestamp = time();
        $process = array(
            'config' => $this->_config,
            'data' => self::getDefaultProcessData(),
            'result' => null,
        );
        foreach ($this->_config as $code => &$row) {
            $this->processRow($process, $row, $check_all_conditions=true);
            foreach ($row as $property_name => $property_value) {
                if (substr($property_name, 0, 1)!='*') {
                    $this->debug('   check '.$property_name);
                    $this->getRowProperty($row, $property_name);
                }
            }
        }
    }

    public function processRow($process, &$row, $is_checking=false)
    {
        if (!isset($row['*id'])) {
            $this->debug('skip row with unknown id');
            return new OS_Result(false);
        }
        $this->debug('process row <span class=osh-key>'.self::esc($row['*id']).'</span>');

        if (isset($row['about'])) { // Display on debug
            $about = $this->getRowProperty($row, 'about');
        }
        
        $type = $this->getRowProperty($row, 'type');
        if ($type=='data') {
            foreach ($row as $key => $data) {
                if (in_array($key, array('*id', 'code', 'type'))) continue;
                $value = isset($data['value']) ? $data['value'] : $data;
                $this->debug('         .<span class=osh-key>'.self::esc($key).'</span> = <span class=osh-formula>'.self::esc(self::toString($value)).'</span> ('.gettype($value).')');
            }
            return new OS_Result(false);
        }
        if (isset($type) && $type!='method') return new OS_Result(false);

        if (!isset($row['label']['value'])) $row['label']['value'] = '***';
        
        $enabled = $this->getRowProperty($row, 'enabled');
        if (isset($enabled)) {
            if (!$is_checking && !$enabled) {
                $this->addMessage('info', $row, 'enabled', 'Configuration disabled');
                return new OS_Result(false);
            }
        }

        $conditions = $this->getRowProperty($row, 'conditions');
        if (isset($conditions)) {
            $result = $this->processFormula($process, $row, 'conditions', $conditions, $is_checking);
            if (!$is_checking) {
                if (!$result->success) return $result;
                if (!$result->result) {
                    $this->addMessage('info', $row, 'conditions', "The cart doesn't match conditions");
                    return new OS_Result(false);
                }
            }
        }

        $address_properties = array(
            'shipto' => "Shipping zone not allowed",
            'billto' => "Billing zone not allowed",
            'origin' => "Shipping origin not allowed",
        );
        foreach ($address_properties as $property_name => $failure_message) {
            $property_value = $this->getRowProperty($row, $property_name);
            if (isset($property_value)) {
                $match = $this->_addressMatch($process, $row, $property_name, $property_value, $process['data'][$property_name]);
                if (!$is_checking && !$match) {
                    $this->addMessage('info', $row, $property_name, $failure_message);
                    return new OS_Result(false);
                }
            }
        }

        $customer_groups = $this->getRowProperty($row, 'customer_groups');
        if (isset($customer_groups)) {
            $groups = explode(',', $customer_groups);
            $group_match = false;
            $customer_group = $process['data']['customer_group'];
            foreach ($groups as $group) {
                $group = trim($group);
                if ($group=='*' || $group==$customer_group->code || ctype_digit($group) && $group==$customer_group->id) {
                    $this->debug('      group <span class=osh-replacement>'.self::esc($customer_group->code).'</span> (id:<span class=osh-replacement>'.self::esc($customer_group->id).'</span>) matches');
                    $group_match = true;
                    break;
                }
            }
            if (!$is_checking && !$group_match) {
                $this->addMessage('info', $row, 'customer_groups', "Customer group not allowed (%s)", $customer_group->code);
                return new OS_Result(false);
            }
        }

        $fees = $this->getRowProperty($row, 'fees');
        if (isset($fees)) {
            $result = $this->processFormula($process, $row, 'fees', $fees, $is_checking);
            if (!$result->success) return $result;
            $this->debug('    &raquo; <span class=osh-info>result</span> = <span class=osh-formula>'.self::esc(self::toString($result->result)).'</span>');
            return new OS_Result(true, (float)$result->result);
        }
        return new OS_Result(false);
    }

    public function getRowProperty(&$row, $key, $original_row=null, $original_key=null)
    {
        $property = null;
        $output = null;
        if (isset($original_row) && isset($original_key) && $original_row['*id']==$row['*id'] && $original_key==$key) {
            $this->addMessage('error', $row, $key, 'Infinite loop %s', "<span class=\"code\">{{$row['*id']}.{$key}}</span>");
            return array('error' => 'Infinite loop');
        }
        if (isset($row[$key]['value'])) {
            $property = $row[$key]['value'];
            $output = $property;
            $this->debug('   get <span class=osh-key>'.self::esc($row['*id']).'</span>.<span class=osh-key>'.self::esc($key).'</span> = <span class=osh-formula>'.self::esc(self::toString($property)).'</span>');
            preg_match_all('/{([a-z0-9_]+)\.([a-z0-9_]+)}/i', $output, $result_set, PREG_SET_ORDER);
            foreach ($result_set as $result) {
                list($original, $ref_code, $ref_key) = $result;
                if ($ref_code==$row['*id'] && $ref_key==$key) {
                    $this->addMessage('error', $row, $key, 'Infinite loop %s', "<span class=\"code\">{$original}</span>");
                    return null;
                }
                if (isset($this->_config[$ref_code][$ref_key]['value'])) {
                    $replacement = $this->getRowProperty($this->_config[$ref_code], $ref_key,
                        isset($original_row) ? $original_row : $row, isset($original_key) ? $original_key : $key);
                    if (is_array($replacement) && isset($replacement['error'])) {
                        return isset($original_row) ? $replacement : 'false';
                    }
                    $output = $this->replace('{'.$original.'}', $this->_autoEscapeStrings($replacement), $output);
                    $output = $this->replace($original, $replacement, $output);
                } else {
                    $replacement = $original;
                    $output = $this->replace($original, $replacement, $output);
                }
                //$this->addMessage('error', $row, $key, $original.' => '.$replacement.' = '.$output);
            }
        } else {
            $this->debug('   get <span class=osh-key>'.self::esc($row['*id']).'</span>.<span class=osh-key>'.self::esc($key).'</span> = <span class=osh-formula>null</span>');
        }
        return $output;
    }
    
    public function evalInput($process, $row, $property_name, $input)
    {
        $result = $this->_prepareFormula($process, $row, $property_name, $input, $is_checking=false, $use_cache=true);
        return $result->success ? $result->result : $input;
    }

    public function compress($input)
    {
        $input = str_replace(
            self::$UNCOMPRESSED_STRINGS,
            self::$COMPRESSED_STRINGS,
            $input
        );
        if (function_exists('gzcompress') && function_exists('base64_encode')) {
            $input = 'gz64'.base64_encode(gzcompress($input));
        }
        return '$$'.$input;
    }
    
    public function uncompress($input)
    {
        if (substr($input, 0, 4)=='gz64' && function_exists('gzuncompress') && function_exists('base64_decode')) {
            $input = gzuncompress(base64_decode(substr($input, 4, strlen($input))));
        }
        return str_replace(
            self::$COMPRESSED_STRINGS,
            self::$UNCOMPRESSED_STRINGS,
            $input
        );
    }

    public function parseProperty($input)
    {
        if ($input==='false') return false;
        if ($input==='true') return true;
        if ($input==='null') return null;
        if (is_numeric($input)) return (double)$input;
        $value = str_replace('\"', '"', preg_replace('/^(?:"|\')(.*)(?:"|\')$/s', '$1', $input));
        return $value==='' ? null : $value;
    }

    protected function replace($from, $to, $input, $class_name=null, $message='replace')
    {
        if ($from===$to) return $input;
        if (mb_strpos($input, $from)===false) return $input;
        $to = self::toString($to);
        $to = preg_replace('/[\r\n\t]+/', ' ', $to);
        $this->debug('      '
            .($class_name ? '<span class="osh-'.$class_name.'">' : '')
            .$message.' <span class=osh-replacement>'.self::esc(self::toString($from)).'</span> by <span class=osh-replacement>'.self::esc($to).'</span>'
            .' =&gt; <span class=osh-formula>'.self::esc(str_replace($from, '<span class=osh-replacement>'.$to.'</span>', $input)).'</span>'
            .($class_name ? '</span>' : ''));
        return str_replace($from, $to, $input);
    }

    protected function _min()
    {
        $args = func_get_args();
        $min = null;
        foreach ($args as $arg) {
            if (isset($arg) && (!isset($min) || $min>$arg)) $min = $arg;
        }
        return $min;
    }

    protected function _max()
    {
        $args = func_get_args();
        $max = null;
        foreach ($args as $arg) {
            if (isset($arg) && (!isset($max) || $max<$arg)) $max = $arg;
        }
        return $max;
    }

    protected function _range($value=-1, $min_value=0, $max_value=1, $include_min_value=true, $include_max_value=true)
    {
        return ($value>$min_value || $include_min_value && $value==$min_value) && ($value<$max_value || $include_max_value && $value==$max_value);
    }

    protected function _array_match_any()
    {
        $args = func_get_args();
        $result = call_user_func_array('array_intersect', $args);
        return (bool)$result;
    }

    protected function _array_match_all()
    {
        $args = func_get_args();
        if (!isset($args[0])) return false;
        $result = call_user_func_array('array_intersect', $args);
        return count($result)==count($args[0]);
    }

    public function processFormula($process, &$row, $property_name, $formula_string, $is_checking, $use_cache=true)
    {
        $result = $this->_prepareFormula($process, $row, $property_name, $formula_string, $is_checking, $use_cache);
        if (!$result->success) return $result;

        $eval_result = $this->_evalFormula($result->result, $row, $property_name, $is_checking);
        if (!$is_checking && !isset($eval_result)) {
            $this->addMessage('error', $row, $property_name, 'Empty result');
            $result = new OS_Result(false);
            if ($use_cache) $this->_setCache($formula_string, $result);
            return $result;
        }
        $result = new OS_Result(true, $eval_result);
        if ($use_cache) $this->_setCache($formula_string, $result);
        return $result;
    }

    protected function _setCache($expression, $value)
    {
        if ($value instanceof OS_Result) {
            $this->_formula_cache[$expression] = $value;
            $this->debug('      cache <span class=osh-replacement>'.self::esc($expression).'</span> = <span class=osh-formula>'.self::esc(self::toString($value->result)).'</span> ('.gettype($value->result).')');
        } else {
            $this->_expression_cache[$expression] = $value; //self::toString($value); // In order to make isset work
            $this->debug('      cache <span class=osh-replacement>'.self::esc($expression).'</span> = <span class=osh-formula>'.self::esc(self::toString($value)).'</span> ('.gettype($value).')');
        }
    }

    protected function _getCachedExpression($original)
    {
        $replacement = $this->_expression_cache[$original];
        $this->debug('      get cached expression <span class=osh-replacement>'.self::esc($original).'</span> = <span class=osh-formula>'.self::esc(self::toString($replacement)).'</span> ('.gettype($replacement).')');
        return $replacement;
    }

    protected function _prepare_regexp($regexp)
    {
        if (!isset($this->constants)) {
            $reflector = new ReflectionClass(get_class($this));
            $this->constants = $reflector->getConstants();
        }
        foreach ($this->constants as $name => $value) {
            $regexp = str_replace('{'.$name.'}', $value, $regexp);
        }
        return $regexp;
    }

    protected function _preg_match($regexp, $input, &$result, $debug=false)
    {
        $regexp = $this->_prepare_regexp($regexp);
        if ($debug) $this->debug('      preg_match <span class=osh-replacement>'.self::esc($regexp).'</span>');
        return preg_match($regexp, $input, $result);
    }

    protected function _preg_match_all($regexp, $input, &$result, $debug=false)
    {
        $regexp = $this->_prepare_regexp($regexp);
        if ($debug) $this->debug('      preg_match_all <span class=osh-replacement>'.self::esc($regexp).'</span>');
        $return = preg_match_all($regexp, $input, $result, PREG_SET_ORDER);
    }
    
    protected function _loadValue($process, $object_name, $attribute)
    {
        switch ($object_name) {
            case 'item':        return isset($process['data']['cart']->items[0]) ? $process['data']['cart']->items[0]->{$attribute} : null;
            case 'product':        return isset($process['data']['cart']->items[0]) ? $process['data']['cart']->items[0]->getProduct()->{$attribute} : null;
            default:            return isset($process['data'][$object_name]) ? $process['data'][$object_name]->{$attribute} : null;
        }
    }

    protected function _prepareFormula($process, $row, $property_name, $formula_string, $is_checking, $use_cache=true)
    {
        if ($use_cache && isset($this->_formula_cache[$formula_string])) {
            $result = $this->_formula_cache[$formula_string];
            $this->debug('      get cached formula <span class=osh-replacement>'.self::esc($formula_string).'</span> = <span class=osh-formula>'.self::esc(self::toString($result->result)).'</span>');
            return $result;
        }
    
        $formula = $formula_string;
        //$this->debug('      formula = <span class=osh-formula>'.self::esc($formula).'</span>');

        // foreach
        while ($this->_preg_match("#{foreach ((?:item|product|p)\.[a-z0-9_\+\-\.]+)}(.*){/foreach}#iU", $formula, $result)) { // ungreedy
            $original = $result[0];
            if ($use_cache && array_key_exists($original, $this->_expression_cache)) {
                $replacement = $this->_getCachedExpression($original);
            } else {
                $replacement = 0;
                $loop_var = $result[1];
                $selections = array();
                $this->debug('      foreach <span class=osh-key>'.self::esc($loop_var).'</span>');
                $this->addDebugIndent();
                $items = $process['data']['cart']->items;
                if ($items) {
                    foreach ($items as $item) {
                        $tmp_value = $this->_getItemProperty($item, $loop_var);
                        $values = (array)$tmp_value;
                        foreach ($values as $value_i) {
                            $key = self::_autoEscapeStrings($value_i);
                            $sel = isset($selections[$key]) ? $selections[$key] : null;
                            $selections[$key]['items'][] = $item;
                        }
                        $this->debug('      items[<span class=osh-formula>'.self::esc((string)$item).'</span>].<span class=osh-key>'.self::esc($loop_var).'</span> = [<span class=osh-formula>'.self::esc(implode('</span>, <span class=osh-formula>', $values)).'</span>]');
                    }
                }
                $this->removeDebugIndent();
                $this->debug('      <span class=osh-loop>start foreach</span>');
                $this->addDebugIndent();
                foreach ($selections as $key => $selection) {
                    $this->debug('     <span class=osh-loop>&bull; value</span> = <span class=osh-formula>'.self::esc($key).'</span>');
                    $this->addDebugIndent();
                    $this->debug(' #### count  '.count($process['data']['cart']->items));
                    $process2 = $process;
                    $process2['data']['cart'] = clone $process2['data']['cart']; // Important to not override previous items
                    $process2['data']['cart']->items = $selection['items'];
                    $selection['qty'] = 0;
                    $selection['weight'] = 0;
                    foreach ($selection['items'] as $item) {
                        $selection['qty'] += $item->qty;
                        $selection['weight'] += $item->weight;
                    }
                    if (isset($process2['data']['selection'])) {
                        $process2['data']['selection']->set('qty', $selection['qty']);
                        $process2['data']['selection']->set('weight', $selection['weight']);
                    }
                    $process_result = $this->processFormula($process2, $row, $property_name, $result[2], $is_checking, $tmp_use_cache=false);
                    $replacement += $process_result->result;
                    $this->debug('    &raquo; <span class=osh-info>foreach sum result</span> = <span class=osh-formula>'.self::esc(self::toString($replacement)).'</span>');
                    $this->removeDebugIndent();
                }
                $this->removeDebugIndent();
                $this->debug('      <span class=osh-loop>end</span>');
                if ($use_cache) $this->_setCache($original, $replacement);
            }
            $formula = $this->replace($original, $replacement, $formula);
        }

        if (isset($process['data']['selection'])) {
            if ($process['data']['selection']->weight==null) $process['data']['selection']->set('weight', $process['data']['cart']->weight);
            if ($process['data']['selection']->qty==null) $process['data']['selection']->set('qty', $process['data']['cart']->qty);
        }

        // data
        $aliases = array(
            'p' => 'product',
            'c' => 'cart',
            's' => 'selection',
        );
        $formula = $this->_replaceData($process, $formula, 'item|product|p|c|s', $aliases);

        // count, sum, min, max
        //while ($this->_preg_match("/{(count) products(?: where ([^}]+))?}/i", $formula, $result)
        //    || $this->_preg_match("/{(sum|min|max|count distinct) {PRODUCT_REGEX}\.({ATTRIBUTE_REGEX}|{OPTION_REGEX}|stock)\.([a-z0-9_+-]+)(?: where ([^}]+))?}/i", $formula, $result)
        //    || $this->_preg_match("/{(sum|min|max|count distinct) {PRODUCT_REGEX}\.(quantity)()(?: where ([^}]+))?}/i", $formula, $result)
        while ($this->_preg_match("/{(count)\s+items\s*(?:\s+where\s+((?:[^\"'}]|'[^']+'|\"[^\"]+\")+))?}/i", $formula, $result)
            || $this->_preg_match("/{(sum|min|max|count distinct) ((?:item|product|p)\.[a-z0-9_\+\-\.]+)(?: where ((?:[^\"'}]|'[^']+'|\"[^\"]+\")+))?}/i", $formula, $result)
                ) {
            $original = $result[0];
            if ($use_cache && array_key_exists($original, $this->_expression_cache)) {
                $replacement = $this->_getCachedExpression($original);
            } else {
                $replacement = $this->_processProduct($process['data']['cart']->items, $result, $row, $property_name, $is_checking);
                if ($use_cache) $this->_setCache($result[0], $replacement);
            }
            $formula = $this->replace($original, $replacement, $formula);
        }
        
        // switch
        while (preg_match("/{switch ([^}]+) in ([^}]+)}/i", $formula, $result)) {
            $original = $result[0];
            if ($use_cache && array_key_exists($original, $this->_expression_cache)) {
                $replacement = $this->_getCachedExpression($original);
            } else {
                $reference_value = $this->_evalFormula($result[1], $row, $property_name, $is_checking);
                $fees_table_string = $result[2];
                
                $couple_regex = '[^}:]+ *\: *[0-9.]+ *';
                if (!preg_match('#^ *'.$couple_regex.'(?:, *'.$couple_regex.')*$#', $fees_table_string)) {
                    $this->addMessage('error', $row, $property_name, 'Error in switch %s', '<span class=osh-formula>'.self::esc($result[0]).'</span>');
                    $result = new OS_Result(false);
                    if ($use_cache) $this->_setCache($formula_string, $result);
                    return $result;
                }
                $fees_table = explode(',', $fees_table_string);
                
                $replacement = null;
                foreach ($fees_table as $item) {
                    $fee_data = explode(':', $item);

                    $fee = trim($fee_data[1]);
                    $value = trim($fee_data[0]);
                    $value = $value=='*' ? '*' : $this->_evalFormula($fee_data[0], $row, $property_name, $is_checking);

                    if ($value=='*' || $reference_value===$value) {
                        $replacement = $fee;
                        $this->debug('      compare <span class=osh-formula>'.self::esc($this->_autoEscapeStrings($reference_value)).'</span> == <span class=osh-formula>'.self::esc($this->_autoEscapeStrings($value)).'</span>');
                        break;
                    }
                    $this->debug('      compare <span class=osh-formula>'.self::esc($this->_autoEscapeStrings($reference_value)).'</span> != <span class=osh-formula>'.self::esc($this->_autoEscapeStrings($value)).'</span>');
                }
                //$replacement = self::toString($replacement);
                if ($use_cache) $this->_setCache($original, $replacement);
            }
            $formula = $this->replace($original, $replacement, $formula);
        }

        // range table
        while (preg_match("/{table ([^}]+) in ([0-9\.:,\*\[\] ]+)}/i", $formula, $result)) {
            $original = $result[0];
            if ($use_cache && array_key_exists($original, $this->_expression_cache)) {
                $replacement = $this->_getCachedExpression($original);
            } else {
                $reference_value = $this->_evalFormula($result[1], $row, $property_name, $is_checking);
                $replacement = null;
                if (isset($reference_value)) {
                    $fees_table_string = $result[2];
                    
                    if (!preg_match('#^'.self::COUPLE_REGEX.'(?:, *'.self::COUPLE_REGEX.')*$#', $fees_table_string)) {
                        $this->addMessage('error', $row, $property_name, 'Error in table %s', '<span class=osh-formula>'.self::esc($result[0]).'</span>');
                        $result = new OS_Result(false);
                        if ($use_cache) $this->_setCache($formula_string, $result);
                        return $result;
                    }
                    $fees_table = explode(',', $fees_table_string);
                    foreach ($fees_table as $item) {
                        $fee_data = explode(':', $item);

                        $fee = trim($fee_data[1]);
                        $max_value = trim($fee_data[0]);

                        $last_char = $max_value{strlen($max_value)-1};
                        if ($last_char=='[') $including_max_value = false;
                        else if ($last_char==']') $including_max_value = true;
                        else $including_max_value = true;

                        $max_value = str_replace(array('[', ']'), '', $max_value);

                        if ($max_value=='*' || $including_max_value && $reference_value<=$max_value || !$including_max_value && $reference_value<$max_value) {
                            $replacement = $fee;
                            break;
                        }
                    }
                }
                //$replacement = self::toString($replacement);
                if ($use_cache) $this->_setCache($original, $replacement);
            }
            $formula = $this->replace($original, $replacement, $formula);
        }
        $result = new OS_Result(true, $formula);
        return $result;
    }

    protected function _evalFormula($formula, &$row, $property_name=null, $is_checking=false)
    {
        if (is_bool($formula)) return $formula;
        if (!preg_match('/^(?:'
                .'\b(?:'
                    .'E|e|int|float|string|boolean|object|array|true|false|null|and|or|in'
                    .'|floor|ceil|round|rand|pow|pi|sqrt|log|exp|abs|substr|strtolower|preg_match|in_array'
                    .'|max|min|range|array_match_any|array_match_all'
                    .'|date|strtotime'
                .')\b'
                .'|\'[^\']*\'|\"[^\"]*\"|[0-9,\'\.\-\(\)\*\/\?\:\+\<\>\=\&\|%!]|\s)*$/', $formula)) {
            $errors = array(
                PREG_NO_ERROR => 'PREG_NO_ERROR',
                PREG_INTERNAL_ERROR => 'PREG_INTERNAL_ERROR',
                PREG_BACKTRACK_LIMIT_ERROR => 'PREG_BACKTRACK_LIMIT_ERROR',
                PREG_RECURSION_LIMIT_ERROR => 'PREG_RECURSION_LIMIT_ERROR',
                PREG_BAD_UTF8_ERROR => 'PREG_BAD_UTF8_ERROR',
                defined('PREG_BAD_UTF8_OFFSET_ERROR') ? PREG_BAD_UTF8_OFFSET_ERROR : 'PREG_BAD_UTF8_OFFSET_ERROR' => 'PREG_BAD_UTF8_OFFSET_ERROR',
            );
            $error = preg_last_error();
            if (isset($errors[$error])) $error = $errors[$error];
            if ($is_checking) $this->addMessage('error', $row, $property_name, $error.' ('.$formula.')');
            $this->debug('      eval <span class=osh-formula>'.self::esc($formula).'</span>');
            $this->debug('      doesn\'t match ('.self::esc($error).')');
            return null;
        }
        $formula = preg_replace('@\b(min|max|range|array_match_any|array_match_all)\(@', '\$this->_\1(', $formula);
        $eval_result = null;
        //echo $formula.'<br/>';
        @eval('$eval_result = ('.$formula.');');
        $this->debug('      evaluate <span class=osh-formula>'.self::esc($formula).'</span> = <span class=osh-replacement>'.self::esc($this->_autoEscapeStrings($eval_result)).'</span>');
        return $eval_result;
    }

    protected function _parseInput($auto_correction)
    {
        $config_string = str_replace(
            array('&gt;', '&lt;', '“', '”', utf8_encode(chr(147)), utf8_encode(chr(148)), '&laquo;', '&raquo;', "\r\n", "\t"),
            array('>', '<', '"', '"', '"', '"', '"', '"', "\n", ' '),
            $this->_input
        );
        
        if (substr($config_string, 0, 2)=='$$') $config_string = $this->uncompress(substr($config_string, 2, strlen($config_string)));
        
        //echo ini_get('pcre.backtrack_limit');
        //exit;

        $this->debug('parse config (auto correction = '.self::esc(self::toString($auto_correction)).')');
        $config = null;
        $last_json_error = null;
        try {
            $config = self::json_decode($config_string);
        } catch (Exception $e) {
            $last_json_error = $e;
        }
        $auto_correction_warnings = array();
        $missing_enquote_of_property_name = array();
        if ($config) {
            foreach ($config as $code => $object) {
                if (!is_object($object)) {
                    $config = null;
                    break;
                }
            }
        }
        if ($auto_correction && !$config && $config_string!='[]') {
            if (preg_match_all('/((?:#+[^{\\n]*\\s+)+)\\s*{/s', $config_string, $result, PREG_SET_ORDER)) {
                $auto_correction_warnings[] = 'JSON: usage of incompatible comments';
                foreach ($result as $set) {
                    $comment_lines = explode("\n", $set[1]);
                    foreach ($comment_lines as $i => $line) {
                        $comment_lines[$i] = preg_replace('/^#+\\s/', '', $line);
                    }
                    $comment = trim(implode("\n", $comment_lines));
                    $config_string = str_replace($set[0], '{"about": "'.str_replace('"', '\\"', $comment).'",', $config_string);
                }
            }
            $property_regex = '\\s*(?<property_name>"?[a-z0-9_]+"?)\\s*:\\s*(?<property_value>"(?:(?:[^"]|\\\\")*[^\\\\])?"|'.self::FLOAT_REGEX.'|false|true|null)\\s*(?<property_separator>,)?\\s*(?:\\n)?';
            $object_regex = '(?:(?<object_name>"?[a-z0-9_]+"?)\\s*:\\s*)?{\\s*('.$property_regex.')+\\s*}\\s*(?<object_separator>,)?\\s*';
            preg_match_all('/('.$object_regex.')/is', $config_string, $object_set, PREG_SET_ORDER);
            //print_r($object_set);
            $json = array();
            $objects_count = count($object_set);
            $to_ignore_counter = -1;
            foreach ($object_set as $i => $object) {
                $pos = strpos($config_string, $object[0]);
                $to_ignore = trim(substr($config_string, 0, $pos));
                if ($to_ignore) {
                    $to_ignore_counter++;
                    if ($to_ignore_counter==0) {
                        $bracket_pos = strpos($to_ignore, '{');
                        if ($bracket_pos!==false) {
                            $to_ignore = explode('{', $to_ignore, 2);
                        }
                    }
                    $to_ignore = (array)$to_ignore;
                    foreach ($to_ignore as $to_ignore_i) {
                        $to_ignore_i = trim($to_ignore_i);
                        if (!$to_ignore_i) continue;
                        $auto_correction_warnings[] = 'JSON: ignored lines (<span class=osh-formula>'.self::toString($to_ignore_i).'</span>)';
                        $n = 0;
                        do {
                            $key = 'meta'.$n;
                            $n++;
                        } while(isset($json[$key]));
                        $json[$key] = array(
                            'type' => 'meta',
                            'ignored' => $to_ignore_i,
                        );
                    }
                    $config_string = substr($config_string, $pos, strlen($config_string));
                }
                $config_string = str_replace($object[0], '', $config_string);
                $object_name = isset($object['object_name']) ? $object['object_name'] : null;
                $object_separator = isset($object['object_separator']) ? $object['object_separator'] : null;
                $is_last_object = ($i==$objects_count-1);
                if (!$is_last_object && $object_separator!=',') {
                    $auto_correction_warnings[] = 'JSON: missing object separator (comma)';
                } else if ($is_last_object && $object_separator==',') {
                    $auto_correction_warnings[] = 'JSON: no trailing object separator (comma) allowed';
                }
                $json_object = array();
                preg_match_all('/'.$property_regex.'/i', $object[0], $property_set, PREG_SET_ORDER);
                $properties_count = count($property_set);
                foreach ($property_set as $j => $property) {
                    $name = $property['property_name'];
                    if ($name{0}!='"' || $name{strlen($name)-1}!='"') {
                        $auto_correction_warnings['missing_enquote_of_property_name'] = 'JSON: missing enquote of property name: %s';
                        $missing_enquote_of_property_name[] = self::toString(trim($name, '"'));
                    }
                    $property_separator = isset($property['property_separator']) ? $property['property_separator'] : null;
                    $is_last_property = ($j==$properties_count-1);
                    if (!$is_last_property && $property_separator!=',') {
                        $auto_correction_warnings[] = 'JSON: missing property separator (comma)';
                    } else if ($is_last_property && $property_separator==',') {
                        $auto_correction_warnings[] = 'JSON: no trailing property separator (comma) allowed';
                    }
                    $json_object[trim($name, '"')] = $this->parseProperty($property['property_value']);
                }
                if ($object_name) $json[trim($object_name, '"')] = $json_object;
                else if (isset($json_object['code'])) {
                    $code = $json_object['code'];
                    unset($json_object['code']);
                    $json[$code] = $json_object;
                } else $json[] = $json_object;
            }
            $to_ignore = trim($config_string);
            if ($to_ignore) {
                $bracket_pos = strpos($to_ignore, '}');
                if ($bracket_pos!==false) {
                    $to_ignore = explode('}', $to_ignore, 2);
                }
                $to_ignore = (array)$to_ignore;
                foreach ($to_ignore as $to_ignore_i) {
                    $to_ignore_i = trim($to_ignore_i);
                    if (!$to_ignore_i) continue;
                    $auto_correction_warnings[] = 'JSON: ignored lines (<span class=osh-formula>'.self::toString($to_ignore_i).'</span>)';
                    $n = 0;
                    do {
                        $key = 'meta'.$n;
                        $n++;
                    } while(isset($json[$key]));
                    $json[$key] = array(
                        'type' => 'meta',
                        'ignored' => $to_ignore_i,
                    );
                }
            }
            $config_string = $this->jsonEncode($json);//'['.$config_string2.']';
            $config_string = str_replace(array("\n"), array("\\n"), $config_string);
            //echo $config_string;

            $last_json_error = null;
            try {
                $config = self::json_decode($config_string);
            } catch (Exception $e) {
                $last_json_error = $e;
            }
        }
        if ($last_json_error) {
            $auto_correction_warnings[] = 'JSON: unable to parse config ('.$last_json_error->getMessage().')';
        }

        $row = null;
        $auto_correction_warnings = array_unique($auto_correction_warnings);
        foreach ($auto_correction_warnings as $key => $warning) {
            if ($key=='missing_enquote_of_property_name') {
                $missing_enquote_of_property_name = array_unique($missing_enquote_of_property_name);
                $warning = str_replace('%s', '<span class=osh-key>'.self::esc(implode('</span>, <span class=osh-key>', $missing_enquote_of_property_name)).'</span>', $warning);
            }
            $this->addMessage('warning', $row, null, $warning);
        }
        $config = (array)$config;
        
        $this->_config = array();
        $available_keys = array('type', 'about', 'label', 'enabled', 'description', 'fees', 'conditions', 'shipto', 'billto', 'origin', 'customer_groups', 'tracking_url');
        $reserved_keys = array('*id');
        if ($auto_correction) {
            $available_keys = array_merge($available_keys, array(
                'destination', 'code', 
            ));
        }

        $deprecated_properties = array();
        $unknown_properties = array();

        foreach ($config as $code => $object) {
            $object = (array)$object;
            if ($auto_correction) {
                if (isset($object['destination'])) {
                    if (!in_array('destination', $deprecated_properties)) $deprecated_properties[] = 'destination';
                    $object['shipto'] = $object['destination'];
                    unset($object['destination']);
                }
                if (isset($object['code'])) {
                    if (!in_array('code', $deprecated_properties)) $deprecated_properties[] = 'code';
                    $code = $object['code'];
                    unset($object['code']);
                }
            }

            $row = array();
            $i = 1;
            foreach ($object as $property_name => $property_value) {
                if (in_array($property_name, $reserved_keys)) {
                    continue;
                }
                if (in_array($property_name, $available_keys)
                    || substr($property_name, 0, 1)=='_'
                    || in_array($object['type'], array('data', 'meta'))) {
                    if (isset($property_value)) {
                        $row[$property_name] = array('value' => $property_value, 'original_value' => $property_value);
                        if ($auto_correction) $this->cleanProperty($row, $property_name);
                    }
                } else {
                    if (!in_array($property_name, $unknown_properties)) $unknown_properties[] = $property_name;
                }
                $i++;
            }
            $this->addRow($code, $row);
        }
        $row = null;
        if (count($unknown_properties)>0) $this->addMessage('error', $row, null, 'Usage of unknown properties %s', ': <span class=osh-key>'.implode('</span>, <span class=osh-key>', $unknown_properties).'</span>');
        if (count($deprecated_properties)>0) $this->addMessage('warning', $row, null, 'Usage of deprecated properties %s', ': <span class=osh-key>'.implode('</span>, <span class=osh-key>', $deprecated_properties).'</span>');
    }
    
    public function addRow($code, &$row)
    {
        if ($code) {
            if (isset($this->_config[$code])) $this->addMessage('error', $row, 'code', 'The id must be unique, `%s` has been found twice', $code);
            while (isset($this->_config[$code])) $code .= rand(0, 9);
        }
        $row['*id'] = $code;
        $this->_config[$code] = $row;
    }
    
    public function addMessage($type, &$row, $property)
    {
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        array_shift($args);
        $message = new OS_Message($type, $args);
        if (isset($row)) {
            if (isset($property)) {
                $row[$property]['messages'][] = $message;
            } else {
                $row['*messages'][] = $message;
            }
        }
        $this->_messages[] = $message;
        $this->debug('   => <span class=osh-'.$message->type.'>'.self::esc((string)$message).'</span>');
    }

    protected function _replaceVariable(&$process, $input, $original, $replacement)
    {
        if (mb_strpos($input, '{'.$original.'}')!==false) {
            $input = $this->replace('{'.$original.'}', $this->_autoEscapeStrings($replacement), $input);
        }
        if (mb_strpos($input, $original)!==false) {
            if (!isset($process['options']->auto_escaping) || $process['options']->auto_escaping) {
                $input = $this->replace($original, $this->_autoEscapeStrings($replacement), $input);
            } else {
                $input = $this->replace($original, $replacement, $input);
            }
        }
        return $input;
    }

    protected function _replaceData(&$process, $input, $keys = '', $aliases = array())
    {
        $keys = ($keys ? $keys.'|' : '').implode('|', array_keys($process['data']));
        $keys = preg_replace('/[^a-z_\|]/', '_', $keys);
        // data
        while ($this->_preg_match("#{({$keys})\.([a-z0-9_\+\-\.]+)}#i", $input, $result)) {
            $original = $result[0];
            $object_name = isset($aliases[$result[1]]) ? $aliases[$result[1]] : $result[1];
            $replacement = $this->_loadValue($process, $object_name, $result[2]);
            $input = $this->_replaceVariable($process, $input, $original, $replacement);
        }
        return $input;
    }

    protected function _addressMatch(&$process, &$row, $property_name, $address_filter, $address)
    {
        //$address_filter = '(* - ( europe (FR-(25,26),DE(40,42) ))';
        //echo '<pre>';
        $address_filter = $this->_replaceData($process, $address_filter);
        $parser = new OS2_AddressFilterParser();
        $address_filter = $parser->parse($address_filter);
        
        $this->debug('      address filter = <span class=osh-formula>'.self::esc($address_filter).'</span>');
        $data = array(
            '{c}' => $address->country_id,
            '{p}' => $address->postcode,
            '{r}' => $address->region_code,
        );
        foreach ($data as $original => $replacement) {
            $address_filter = $this->_replaceVariable($process, $address_filter, $original, $replacement);
        }
        return (bool)$this->_evalFormula($address_filter, $row, $property_name, $is_checking=false);
    }

    protected function _getItemProperty($item, $property_name)
    {
        $elems = explode('.', $property_name, $limit=2);
        switch ($elems[0]) {
            case 'p':
            case 'product': return $item->getProduct()->{$elems[1]};
            case 'item': return $item->{$elems[1]};
        }
        return null;
    }

    protected function _autoEscapeStrings($input)
    {
        if (is_array($input)) {
            $items = array();
            foreach ($input as $v) {
                $items[] = isset($v) && (is_string($v) || empty($v)) ? self::escapeString($v) : self::toString($v);
            }
            return 'array('.join(',', $items).')';
        } else {
            return isset($input) && (is_string($input)/* || empty($input)*/) ? self::escapeString($input) : self::toString($input);
        }
    }

    protected function _processProduct($items, $regex_result, &$row, $property_name, $is_checking)
    {
        // count, sum, min, max, count distinct
        $operation = strtolower($regex_result[1]);
        $return_value = null;
        $reference = 'items';
        switch ($operation) {
            case 'sum':
            case 'min':
            case 'max':
            case 'count distinct':
                $reference = $regex_result[2];
                $conditions = isset($regex_result[3]) ? $regex_result[3] : null;
                break;
            case 'count':
                $conditions = isset($regex_result[2]) ? $regex_result[2] : null;
                break;
        }
        switch ($operation) {
            case 'sum':
            case 'count distinct':
            case 'count':
                $return_value = 0;
                break;
        }
        
        $this->debug('      <span class=osh-loop>start <span class=osh-replacement>'.self::esc($operation).'</span> '
            .'<span class=osh-key>'.self::esc($reference).'</span>'
            .(isset($conditions) ? ' where <span class=osh-replacement>'.self::esc($conditions).'</span></span>' : '')
        );
        $this->addDebugIndent();

        $properties = array();
        $this->_preg_match_all('#(?:item|product|p)\.([a-z0-9_\+\-\.]+)#i', $conditions, $properties_regex_result);
        foreach ($properties_regex_result as $property_regex_result) {
            if (!isset($properties[$property_regex_result[0]])) $properties[$property_regex_result[0]] = $property_regex_result;
        }
        krsort($properties); // To avoid shorter replace

        if ($items) {
            foreach ($items as $item) {
                $this->debug('     <span class=osh-loop>&bull; item</span> = <span class=osh-formula>'.self::esc((string)$item).'</span>');
                $this->addDebugIndent();
                if (isset($conditions) && $conditions!='') {
                    $formula = $conditions;
                    foreach ($properties as $property) {
                        $value = $this->_getItemProperty($item, $property[0]);
                        $from = $property[0];
                        $to = $this->_autoEscapeStrings($value);
                        $this->debug('      replace <span class=osh-replacement>'.self::esc($from).'</span> by <span class=osh-replacement>'.self::esc($to).'</span> =&gt; <span class=osh-formula>'.self::esc(str_replace($from, '<span class=osh-replacement>'.$to.'</span>', $formula)).'</span>');
                        $formula = str_replace($from, $to, $formula);
                    }
                    $eval_result = $this->_evalFormula($formula, $row, $property_name, $is_checking);
                    if (!isset($eval_result)) $return_value = 'null';
                }
                else $eval_result = true;

                if ($eval_result==true) {
                    if ($operation=='count') {
                        $return_value = (isset($return_value) ? $return_value : 0) + $item->qty;
                    } else {
                        $value = $this->_getItemProperty($item, $reference);
                        $this->debug('    &raquo; <span class=osh-key>'.self::esc($reference).'</span> = <span class=osh-formula>'.self::esc($value).'</span>'
                            .($operation=='sum' ? ' x <span class=osh-formula>'.$item->qty.'</span>' : ''));
                        switch ($operation) {
                            case 'min': if (!isset($return_value) || $value<$return_value) $return_value = $value; break;
                            case 'max': if (!isset($return_value) || $value>$return_value) $return_value = $value; break;
                            case 'sum':
                                //$this->debug(self::esc($item->getProduct()->sku).'.'.self::esc($reference).' = "'.self::esc($value).'" x '.self::esc($item->qty));
                                $return_value = (isset($return_value) ? $return_value : 0) + $value*$item->qty;
                                break;
                            case 'count distinct':
                                if (!isset($return_value)) $return_value = 0;
                                if (!isset($distinct_values)) $distinct_values = array();
                                if (!in_array($value, $distinct_values)) {
                                    $distinct_values[] = $value;
                                    $return_value++;
                                }
                                break;
                        }
                    }
                }
                $this->debug('    &raquo; <span class=osh-info>'.self::esc($operation).' result</span> = <span class=osh-formula>'.self::esc($return_value).'</span>');
                $this->removeDebugIndent();
            }
        }

        $this->removeDebugIndent();
        $this->debug('      <span class=osh-loop>end</span>');

        return $return_value;
    }
    
    /* For auto correction */
    public function cleanProperty(&$row, $key)
    {
        $input = $row[$key]['value'];
        if (is_string($input)) {
            while (preg_match('/{{customVar code=([a-zA-Z0-9_-]+)}}/', $input, $resi)) {
                $input = $this->replace($resi[0], '{customvar.'.$resi[1].'}', $input, 'warning', 'replace deprecated');
            }

            $regex = "{(weight|products_quantity|price_including_tax|price_excluding_tax|country)}";
            if (preg_match('/'.$regex.'/', $input, $resi)) {
                $this->addMessage('warning', $row, $key, 'Usage of deprecated syntax %s', '<span class=osh-formula>'.$resi[0].'</span>');
                while (preg_match('/'.$regex.'/', $input, $resi)) {
                    switch ($resi[1]) {
                        case 'price_including_tax': $to = "{cart.price+tax+discount}"; break;
                        case 'price_excluding_tax': $to = "{cart.price-tax+discount}"; break;
                        case 'weight': $to = "{cart.{$resi[1]}}"; break;
                        case 'products_quantity': $to = "{cart.qty}"; break;
                        case 'country': $to = "{shipto.country_name}"; break;
                    }
                    $input = str_replace($resi[0], $to, $input);
                }
            }

            $regex1 = "{copy '([a-zA-Z0-9_]+)'\.'([a-zA-Z0-9_]+)'}";
            if (preg_match('/'.$regex1.'/', $input, $resi)) {
                $this->addMessage('warning', $row, $key, 'Usage of deprecated syntax %s', '<span class=osh-formula>'.$resi[0].'</span>');
                while (preg_match('/'.$regex1.'/', $input, $resi)) $input = str_replace($resi[0], '{'.$resi[1].'.'.$resi[2].'}', $input);
            }

            $regex1 = "{(count|all|any) (attribute|option) '([^'\)]+)' ?((?:==|<=|>=|<|>|!=) ?(?:".self::FLOAT_REGEX."|true|false|'[^'\)]*'))}";
            $regex2 = "{(sum) (attribute|option) '([^'\)]+)'}";
            if (preg_match('/'.$regex1.'/', $input, $resi) || preg_match('/'.$regex2.'/', $input, $resi)) {
                $this->addMessage('warning', $row, $key, 'Usage of deprecated syntax %s', '<span class=osh-formula>'.$resi[0].'</span>');
                while (preg_match('/'.$regex1.'/', $input, $resi) || preg_match('/'.$regex2.'/', $input, $resi)) {
                    switch ($resi[1]) {
                        case 'count':    $to = "{count items where product.{$resi[2]}.{$resi[3]}{$resi[4]}}"; break;
                        case 'all':        $to = "{count items where product.{$resi[2]}.{$resi[3]}{$resi[4]}}=={cart.qty}"; break;
                        case 'any':        $to = "{count items where product.{$resi[2]}.{$resi[3]}{$resi[4]}}>0"; break;
                        case 'sum':        $to = "{sum product.{$resi[2]}.{$resi[3]}}"; break;
                    }
                    $input = str_replace($resi[0], $to, $input);
                }
            }

            $regex = "((?:{| )product.(?:attribute|option))s.";
            if (preg_match('/'.$regex.'/', $input, $resi)) {
                $this->addMessage('warning', $row, $key, 'Usage of deprecated syntax %s', '<span class=osh-formula>'.$resi[0].'</span>');
                while (preg_match('/'.$regex.'/', $input, $resi)) {
                    $input = str_replace($resi[0], $resi[1].'.', $input);
                }
            }

            $regex = "{table '([^']+)' (".self::COUPLE_REGEX."(?:, *".self::COUPLE_REGEX.")*)}";
            if (preg_match('/'.$regex.'/', $input, $resi)) {
                $this->addMessage('warning', $row, $key, 'Usage of deprecated syntax %s', '<span class=osh-formula>'.$resi[0].'</span>');
                while (preg_match('/'.$regex.'/', $input, $resi)) {
                    switch ($resi[1]) {
                        case 'products_quantity':
                            $input = str_replace($resi[0], "{table {cart.weight} in {$resi[2]}}*{cart.qty}", $input);
                            break;
                        default:
                            $input = str_replace($resi[0], "{table {cart.{$resi[1]}} in {$resi[2]}}", $input);
                            break;
                    }
                }
            }

            $aliases = array(
                '{destination.country.code}' => '{shipto.country_id}',
                '{destination.country.name}' => '{shipto.country_name}',
                '{destination.region.code}' => '{shipto.region_code}',
                '{destination.postcode}' => '{shipto.postcode}',
                '.destination}' => '.shipto}',
                '{cart.price_excluding_tax}' => '{cart.price-tax+discount}',
                '{cart.price_including_tax}' => '{cart.price+tax+discount}',
                '{cart.weight.unit}' => '{cart.weight_unit}',
                '{cart.coupon}' => '{cart.coupon_code}',
                '{cart.weight.for-charge}' => '{cart.weight_for_charge}',
                '{c.price_excluding_tax}' => '{c.price-tax+discount}',
                '{c.price_including_tax}' => '{c.price+tax+discount}',
                '{c.weight.unit}' => '{c.weight_unit}',
                '{c.coupon}' => '{c.coupon_code}',
                '{free_shipping}' => '{cart.free_shipping}',
                '{c.weight.for-charge}' => '{c.weight_for_charge}',
                '{customer.group.id}' => '{customer_group.id}',
                '{customer.group.code}' => '{customer_group.code}',
                '{origin.country.code}' => '{origin.country_id}',
                '{origin.country.name}' => '{origin.country_name}',
                '{origin.region.code}' => '{origin.region_id}',
                '{selection.quantity}' => '{selection.qty}',
                'product.quantity' => 'item.qty',
                'product.stock.quantity' => 'product.stock.qty',
                'product.options.' => 'item.option.',
                'product.option.' => 'item.option.',
                'product.o.' => 'item.o.',
                'p.quantity' => 'item.qty',
                'p.stock.quantity' => 'p.stock.qty',
                'p.options.' => 'item.option.',
                'p.option.' => 'item.option.',
                'p.o.' => 'item.o.',
                'count products ' => 'count items ',
                'product.attribute.price+tax+discount' => 'item.price+tax+discount',
                'product.attribute.price+tax-discount' => 'item.price+tax-discount',
                'product.attribute.price-tax+discount' => 'item.price-tax+discount',
                'product.attribute.price-tax-discount' => 'item.price-tax-discount',
            );
            foreach ($aliases as $from => $to) {
                if (mb_strpos($input, $from)!==false) {
                    $input = $this->replace($from, $to, $input, 'warning', 'replace deprecated');
                }
            }
        }
        $row[$key]['value'] = $input;
    }

}

class OS2_Data
{
    protected $_data;

    public function __construct($data=null)
    {
        $this->_data = (array)$data;
    }

    public function __sleep()
    {
        return array_keys($this->_data);
    }

    public function __get($name)
    {
        return isset($this->_data[$name]) ? $this->_data[$name] : null;
    }

    public function set($name, $value)
    {
        $this->_data[$name] = $value;
    }
}

class OS_Message
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

class OS_Result
{
    public $success;
    public $result;

    public function __construct($success, $result=null)
    {
        $this->success = $success;
        $this->result = $result;
    }

    public function __toString()
    {
        return OwebiaShippingHelper::toString($this->result);
    }
}

