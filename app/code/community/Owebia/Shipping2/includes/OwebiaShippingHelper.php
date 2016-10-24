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

    public static $debugIndexCounter = 0;
    public static $uncompressedStrings = array(
        ' product.attribute.',
        ' item.option.',
        '{product.attribute.',
        '{item.option.',
        '{product.',
        '{cart.',
        '{selection.',
    );
    public static $compressedStrings = array(
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

    public static function jsonEncode($data, $beautify = false, $html = false, $level = 0, $currentIndent = '')
    {
        //$html = true;
        $indent = "\t";//$html ? '&nbsp;&nbsp;&nbsp;&nbsp;' : "\t";//
        $lineBreak = $html ? '<br/>' : "\n";
        $newIndent = $currentIndent.$indent;
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
                $outputIndexCount = 0;
                $output = array();
                foreach ($data as $key => $value) {
                    if ($outputIndexCount!==null && $outputIndexCount++!==$key) {
                        $outputIndexCount = null;
                    }
                }
                $isAssociative = $outputIndexCount===null;
                foreach ($data as $key => $value) {
                    if ($isAssociative) {
                        $classes = array();
                        if ($key=='about') $classes[] = 'json-about';
                        if ($key=='conditions' || $key=='fees') $classes[] = 'json-formula';
                        $propertyClasses = array('json-property');
                        if ($level==0) $propertyClasses[] = 'json-id';
                        $output[] = ($html && $classes ? '<span class="'.implode(' ', $classes).'">' : '')
                            .($html ? '<span class="'.implode(' ', $propertyClasses).'">' : '')
                            .self::jsonEncode((string)$key)
                            .($html ? '</span>' : '').':'
                            .($beautify ? ' ' : '')
                            .self::jsonEncode($value, $beautify, $html, $level+1, $newIndent)
                            .($html && $classes ? '</span>' : '');
                    } else {
                        $output[] = self::jsonEncode($value, $beautify, $html, $level+1, $currentIndent);
                    }
                }
                if ($isAssociative) {
                    $classes = array();
                    if (isset($data['type']) && $data['type']=='meta') $classes[] = 'json-meta';
                    $output = ($html && $classes ? '<span class="'.implode(' ', $classes).'">' : '')
                        .'{'
                        .($beautify ? "{$lineBreak}{$newIndent}" : '')
                        .implode(','.($beautify ? "{$lineBreak}{$newIndent}" : ''), $output)
                        .($beautify ? "{$lineBreak}{$currentIndent}" : '')
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
    protected $_formulaCache = array();
    protected $_expressionCache = array();
    public $debugCode = null;
    public $debugOutput = '';
    public $debugHeader = null;
    protected $debugPrefix = '';

    public function __construct($input, $autoCorrection)
    {
        $this->_input = $input;
        $this->_parseInput($autoCorrection);
    }

    public function addDebugIndent()
    {
        $this->debugPrefix .= '   ';
    }

    public function removeDebugIndent()
    {
        $this->debugPrefix = substr($this->debugPrefix, 0, strlen($this->debugPrefix)-3);
    }

    public function debug($text)
    {
        $this->debugOutput .= "<p>{$this->debugPrefix}{$text}</p>";
    }

    public function getDebug()
    {
        $index = $this->debugCode.'-'.self::$debugIndexCounter++;
        $output = "<style rel=stylesheet type=\"text/css\">"
        .".osh-debug{background:#000;color:#bbb;-webkit-opacity:0.9;-moz-opacity:0.9;opacity:0.9;text-align:left;white-space:pre-wrap;overflow:auto;}"
        .".osh-debug p{margin:2px 0;}"
        .".osh-formula{color:#f90;} .osh-key{color:#0099f7;} .osh-loop{color:#ff0;}"
        .".osh-error{color:#f00;} .osh-warning{color:#ff0;} .osh-info{color:#7bf700;}"
        .".osh-debug-content{padding:10px;font-family:monospace}"
        .".osh-replacement{color:#ff3000;}"
        ."</style>"
        ."<div id=osh-debug-{$index} class=osh-debug><div class=osh-debug-content><span class=osh-close style=\"float:right;cursor:pointer;\" onclick=\"document.getElementById('osh-debug-{$index}').style.display = 'none';\">[<span style=\"padding:0 5px;color:#f00;\">X</span>]</span>"
        ."<p>{$this->debugHeader}</p>{$this->debugOutput}</div></div>";
        return $output;
    }

    public function initDebug($code, $process)
    {
        $header = 'DEBUG OwebiaShippingHelper.php<br/>';
        foreach ($process as $index => $processOption) {
            if (in_array($index, array('data', 'options'))) {
                $header .= '   <span class=osh-key>'.self::esc(str_replace('.', '</span>.<span class=osh-key>', $index)).'</span> &gt;&gt;<br/>';
                foreach ($processOption as $objectName => $data) {
                    if (is_object($data) || is_array($data)) {
                        $header .= '      <span class=osh-key>'.self::esc(str_replace('.', '</span>.<span class=osh-key>', $objectName)).'</span> &gt;&gt;<br/>';
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
                        $header .= '      .<span class=osh-key>'.self::esc(str_replace('.', '</span>.<span class=osh-key>', $objectName)).'</span> = <span class=osh-formula>'.self::esc(self::toString($data)).'</span> ('.gettype($data).')<br/>';
                    }
                }
            } else {
                $header .= '   <span class=osh-key>'.self::esc(str_replace('.', '</span>.<span class=osh-key>', $index)).'</span> = <span class=osh-formula>'.self::esc(self::toString($processOption)).'</span> ('.gettype($processOption).')<br/>';
            }
        }
        $this->debugCode = $code;
        $this->debugHeader = $header;
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
        $i1 = isset($this->propertiesSort[$k1]) ? $this->propertiesSort[$k1] : 1000;
        $i2 = isset($this->propertiesSort[$k2]) ? $this->propertiesSort[$k2] : 1000;
        return $i1==$i2 ? strcmp($k1, $k2) : $i1-$i2;
    }

    public function formatConfig($compress, $keysToRemove=array(), $html = false)
    {
        $objectArray = array();
        $this->propertiesSort = array_flip(array(
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
                if (substr($key, 0, 1)!='*' && !in_array($key, $keysToRemove)) {
                    $object[$key] = $property['value'];
                }
            }
            uksort($object, array($this, 'sortProperties'));
            $objectArray[$code] = $object;
        }
        $output = self::jsonEncode($objectArray, $beautify = !$compress, $html);
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
            $this->processRow($process, $row, $checkAllConditions=true);
            foreach ($row as $propertyName => $propertyValue) {
                if (substr($propertyName, 0, 1)!='*') {
                    $this->debug('   check '.$propertyName);
                    $this->getRowProperty($row, $propertyName);
                }
            }
        }
    }

    public function processRow($process, &$row, $isChecking=false)
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
            if (!$isChecking && !$enabled) {
                $this->addMessage('info', $row, 'enabled', 'Configuration disabled');
                return new OS_Result(false);
            }
        }

        $conditions = $this->getRowProperty($row, 'conditions');
        if (isset($conditions)) {
            $result = $this->processFormula($process, $row, 'conditions', $conditions, $isChecking);
            if (!$isChecking) {
                if (!$result->success) return $result;
                if (!$result->result) {
                    $this->addMessage('info', $row, 'conditions', "The cart doesn't match conditions");
                    return new OS_Result(false);
                }
            }
        }

        $addressProperties = array(
            'shipto' => "Shipping zone not allowed",
            'billto' => "Billing zone not allowed",
            'origin' => "Shipping origin not allowed",
        );
        foreach ($addressProperties as $propertyName => $failureMessage) {
            $propertyValue = $this->getRowProperty($row, $propertyName);
            if (isset($propertyValue)) {
                $match = $this->_addressMatch($process, $row, $propertyName, $propertyValue, $process['data'][$propertyName]);
                if (!$isChecking && !$match) {
                    $this->addMessage('info', $row, $propertyName, $failureMessage);
                    return new OS_Result(false);
                }
            }
        }

        $customerGroups = $this->getRowProperty($row, 'customer_groups');
        if (isset($customerGroups)) {
            $groups = explode(',', $customerGroups);
            $groupMatch = false;
            $customerGroup = $process['data']['customer_group'];
            foreach ($groups as $group) {
                $group = trim($group);
                if ($group=='*' || $group==$customerGroup->code || ctype_digit($group) && $group==$customerGroup->id) {
                    $this->debug('      group <span class=osh-replacement>'.self::esc($customerGroup->code).'</span> (id:<span class=osh-replacement>'.self::esc($customerGroup->id).'</span>) matches');
                    $groupMatch = true;
                    break;
                }
            }
            if (!$isChecking && !$groupMatch) {
                $this->addMessage('info', $row, 'customer_groups', "Customer group not allowed (%s)", $customerGroup->code);
                return new OS_Result(false);
            }
        }

        $fees = $this->getRowProperty($row, 'fees');
        if (isset($fees)) {
            $result = $this->processFormula($process, $row, 'fees', $fees, $isChecking);
            if (!$result->success) return $result;
            $this->debug('    &raquo; <span class=osh-info>result</span> = <span class=osh-formula>'.self::esc(self::toString($result->result)).'</span>');
            return new OS_Result(true, (float)$result->result);
        }
        return new OS_Result(false);
    }

    public function getRowProperty(&$row, $key, $originalRow=null, $originalKey=null)
    {
        $property = null;
        $output = null;
        if (isset($originalRow) && isset($originalKey) && $originalRow['*id']==$row['*id'] && $originalKey==$key) {
            $this->addMessage('error', $row, $key, 'Infinite loop %s', "<span class=\"code\">{{$row['*id']}.{$key}}</span>");
            return array('error' => 'Infinite loop');
        }
        if (isset($row[$key]['value'])) {
            $property = $row[$key]['value'];
            $output = $property;
            $this->debug('   get <span class=osh-key>'.self::esc($row['*id']).'</span>.<span class=osh-key>'.self::esc($key).'</span> = <span class=osh-formula>'.self::esc(self::toString($property)).'</span>');
            preg_match_all('/{([a-z0-9_]+)\.([a-z0-9_]+)}/i', $output, $resultSet, PREG_SET_ORDER);
            foreach ($resultSet as $result) {
                list($original, $refCode, $refKey) = $result;
                if ($refCode==$row['*id'] && $refKey==$key) {
                    $this->addMessage('error', $row, $key, 'Infinite loop %s', "<span class=\"code\">{$original}</span>");
                    return null;
                }
                if (isset($this->_config[$refCode][$refKey]['value'])) {
                    $replacement = $this->getRowProperty($this->_config[$refCode], $refKey,
                        isset($originalRow) ? $originalRow : $row, isset($originalKey) ? $originalKey : $key);
                    if (is_array($replacement) && isset($replacement['error'])) {
                        return isset($originalRow) ? $replacement : 'false';
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
    
    public function evalInput($process, $row, $propertyName, $input)
    {
        $result = $this->_prepareFormula($process, $row, $propertyName, $input, $isChecking=false, $useCache=true);
        return $result->success ? $result->result : $input;
    }

    public function compress($input)
    {
        $input = str_replace(
            self::$uncompressedStrings,
            self::$compressedStrings,
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
            self::$compressedStrings,
            self::$uncompressedStrings,
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

    protected function replace($from, $to, $input, $className=null, $message='replace')
    {
        if ($from===$to) return $input;
        if (mb_strpos($input, $from)===false) return $input;
        $to = self::toString($to);
        $to = preg_replace('/[\r\n\t]+/', ' ', $to);
        $this->debug('      '
            .($className ? '<span class="osh-'.$className.'">' : '')
            .$message.' <span class=osh-replacement>'.self::esc(self::toString($from)).'</span> by <span class=osh-replacement>'.self::esc($to).'</span>'
            .' =&gt; <span class=osh-formula>'.self::esc(str_replace($from, '<span class=osh-replacement>'.$to.'</span>', $input)).'</span>'
            .($className ? '</span>' : ''));
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

    protected function _range($value=-1, $minValue=0, $maxValue=1, $includeMinValue=true, $includeMaxValue=true)
    {
        return ($value>$minValue || $includeMinValue && $value==$minValue) && ($value<$maxValue || $includeMaxValue && $value==$maxValue);
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

    public function processFormula($process, &$row, $propertyName, $formulaString, $isChecking, $useCache=true)
    {
        $result = $this->_prepareFormula($process, $row, $propertyName, $formulaString, $isChecking, $useCache);
        if (!$result->success) return $result;

        $evalResult = $this->_evalFormula($result->result, $row, $propertyName, $isChecking);
        if (!$isChecking && !isset($evalResult)) {
            $this->addMessage('error', $row, $propertyName, 'Empty result');
            $result = new OS_Result(false);
            if ($useCache) $this->_setCache($formulaString, $result);
            return $result;
        }
        $result = new OS_Result(true, $evalResult);
        if ($useCache) $this->_setCache($formulaString, $result);
        return $result;
    }

    protected function _setCache($expression, $value)
    {
        if ($value instanceof OS_Result) {
            $this->_formulaCache[$expression] = $value;
            $this->debug('      cache <span class=osh-replacement>'.self::esc($expression).'</span> = <span class=osh-formula>'.self::esc(self::toString($value->result)).'</span> ('.gettype($value->result).')');
        } else {
            $this->_expressionCache[$expression] = $value; //self::toString($value); // In order to make isset work
            $this->debug('      cache <span class=osh-replacement>'.self::esc($expression).'</span> = <span class=osh-formula>'.self::esc(self::toString($value)).'</span> ('.gettype($value).')');
        }
    }

    protected function _getCachedExpression($original)
    {
        $replacement = $this->_expressionCache[$original];
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
    
    protected function _loadValue($process, $objectName, $attribute)
    {
        switch ($objectName) {
            case 'item':        return isset($process['data']['cart']->items[0]) ? $process['data']['cart']->items[0]->{$attribute} : null;
            case 'product':        return isset($process['data']['cart']->items[0]) ? $process['data']['cart']->items[0]->getProduct()->{$attribute} : null;
            default:            return isset($process['data'][$objectName]) ? $process['data'][$objectName]->{$attribute} : null;
        }
    }

    protected function _prepareFormula($process, $row, $propertyName, $formulaString, $isChecking, $useCache=true)
    {
        if ($useCache && isset($this->_formulaCache[$formulaString])) {
            $result = $this->_formulaCache[$formulaString];
            $this->debug('      get cached formula <span class=osh-replacement>'.self::esc($formulaString).'</span> = <span class=osh-formula>'.self::esc(self::toString($result->result)).'</span>');
            return $result;
        }
    
        $formula = $formulaString;
        //$this->debug('      formula = <span class=osh-formula>'.self::esc($formula).'</span>');

        // foreach
        while ($this->_preg_match("#{foreach ((?:item|product|p)\.[a-z0-9_\+\-\.]+)}(.*){/foreach}#iU", $formula, $result)) { // ungreedy
            $original = $result[0];
            if ($useCache && array_key_exists($original, $this->_expressionCache)) {
                $replacement = $this->_getCachedExpression($original);
            } else {
                $replacement = 0;
                $loopVar = $result[1];
                $selections = array();
                $this->debug('      foreach <span class=osh-key>'.self::esc($loopVar).'</span>');
                $this->addDebugIndent();
                $items = $process['data']['cart']->items;
                if ($items) {
                    foreach ($items as $item) {
                        $tmpValue = $this->_getItemProperty($item, $loopVar);
                        $values = (array)$tmpValue;
                        foreach ($values as $valueItem) {
                            $key = self::_autoEscapeStrings($valueItem);
                            $sel = isset($selections[$key]) ? $selections[$key] : null;
                            $selections[$key]['items'][] = $item;
                        }
                        $this->debug('      items[<span class=osh-formula>'.self::esc((string)$item).'</span>].<span class=osh-key>'.self::esc($loopVar).'</span> = [<span class=osh-formula>'.self::esc(implode('</span>, <span class=osh-formula>', $values)).'</span>]');
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
                    $processResult = $this->processFormula($process2, $row, $propertyName, $result[2], $isChecking, $tmpUseCache=false);
                    $replacement += $processResult->result;
                    $this->debug('    &raquo; <span class=osh-info>foreach sum result</span> = <span class=osh-formula>'.self::esc(self::toString($replacement)).'</span>');
                    $this->removeDebugIndent();
                }
                $this->removeDebugIndent();
                $this->debug('      <span class=osh-loop>end</span>');
                if ($useCache) $this->_setCache($original, $replacement);
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
            if ($useCache && array_key_exists($original, $this->_expressionCache)) {
                $replacement = $this->_getCachedExpression($original);
            } else {
                $replacement = $this->_processProduct($process['data']['cart']->items, $result, $row, $propertyName, $isChecking);
                if ($useCache) $this->_setCache($result[0], $replacement);
            }
            $formula = $this->replace($original, $replacement, $formula);
        }
        
        // switch
        while (preg_match("/{switch ([^}]+) in ([^}]+)}/i", $formula, $result)) {
            $original = $result[0];
            if ($useCache && array_key_exists($original, $this->_expressionCache)) {
                $replacement = $this->_getCachedExpression($original);
            } else {
                $referenceValue = $this->_evalFormula($result[1], $row, $propertyName, $isChecking);
                $feesTableString = $result[2];
                
                $coupleRegexp = '[^}:]+ *\: *[0-9.]+ *';
                if (!preg_match('#^ *'.$coupleRegexp.'(?:, *'.$coupleRegexp.')*$#', $feesTableString)) {
                    $this->addMessage('error', $row, $propertyName, 'Error in switch %s', '<span class=osh-formula>'.self::esc($result[0]).'</span>');
                    $result = new OS_Result(false);
                    if ($useCache) $this->_setCache($formulaString, $result);
                    return $result;
                }
                $feesTable = explode(',', $feesTableString);
                
                $replacement = null;
                foreach ($feesTable as $item) {
                    $feeData = explode(':', $item);

                    $fee = trim($feeData[1]);
                    $value = trim($feeData[0]);
                    $value = $value=='*' ? '*' : $this->_evalFormula($feeData[0], $row, $propertyName, $isChecking);

                    if ($value=='*' || $referenceValue===$value) {
                        $replacement = $fee;
                        $this->debug('      compare <span class=osh-formula>'.self::esc($this->_autoEscapeStrings($referenceValue)).'</span> == <span class=osh-formula>'.self::esc($this->_autoEscapeStrings($value)).'</span>');
                        break;
                    }
                    $this->debug('      compare <span class=osh-formula>'.self::esc($this->_autoEscapeStrings($referenceValue)).'</span> != <span class=osh-formula>'.self::esc($this->_autoEscapeStrings($value)).'</span>');
                }
                //$replacement = self::toString($replacement);
                if ($useCache) $this->_setCache($original, $replacement);
            }
            $formula = $this->replace($original, $replacement, $formula);
        }

        // range table
        while (preg_match("/{table ([^}]+) in ([0-9\.:,\*\[\] ]+)}/i", $formula, $result)) {
            $original = $result[0];
            if ($useCache && array_key_exists($original, $this->_expressionCache)) {
                $replacement = $this->_getCachedExpression($original);
            } else {
                $referenceValue = $this->_evalFormula($result[1], $row, $propertyName, $isChecking);
                $replacement = null;
                if (isset($referenceValue)) {
                    $feesTableString = $result[2];
                    
                    if (!preg_match('#^'.self::COUPLE_REGEX.'(?:, *'.self::COUPLE_REGEX.')*$#', $feesTableString)) {
                        $this->addMessage('error', $row, $propertyName, 'Error in table %s', '<span class=osh-formula>'.self::esc($result[0]).'</span>');
                        $result = new OS_Result(false);
                        if ($useCache) $this->_setCache($formulaString, $result);
                        return $result;
                    }
                    $feesTable = explode(',', $feesTableString);
                    foreach ($feesTable as $item) {
                        $feeData = explode(':', $item);

                        $fee = trim($feeData[1]);
                        $maxValue = trim($feeData[0]);

                        $lastChar = $maxValue{strlen($maxValue)-1};
                        if ($lastChar=='[') $includingMaxValue = false;
                        else if ($lastChar==']') $includingMaxValue = true;
                        else $includingMaxValue = true;

                        $maxValue = str_replace(array('[', ']'), '', $maxValue);

                        if ($maxValue=='*' || $includingMaxValue && $referenceValue<=$maxValue || !$includingMaxValue && $referenceValue<$maxValue) {
                            $replacement = $fee;
                            break;
                        }
                    }
                }
                //$replacement = self::toString($replacement);
                if ($useCache) $this->_setCache($original, $replacement);
            }
            $formula = $this->replace($original, $replacement, $formula);
        }
        $result = new OS_Result(true, $formula);
        return $result;
    }

    protected function _evalFormula($formula, &$row, $propertyName=null, $isChecking=false)
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
            if ($isChecking) $this->addMessage('error', $row, $propertyName, $error.' ('.$formula.')');
            $this->debug('      eval <span class=osh-formula>'.self::esc($formula).'</span>');
            $this->debug('      doesn\'t match ('.self::esc($error).')');
            return null;
        }
        $formula = preg_replace('@\b(min|max|range|array_match_any|array_match_all)\(@', '\$this->_\1(', $formula);
        $evalResult = null;
        //echo $formula.'<br/>';
        @eval('$evalResult = ('.$formula.');');
        $this->debug('      evaluate <span class=osh-formula>'.self::esc($formula).'</span> = <span class=osh-replacement>'.self::esc($this->_autoEscapeStrings($evalResult)).'</span>');
        return $evalResult;
    }

    protected function _parseInput($autoCorrection)
    {
        $configString = str_replace(
            array('&gt;', '&lt;', '“', '”', utf8_encode(chr(147)), utf8_encode(chr(148)), '&laquo;', '&raquo;', "\r\n", "\t"),
            array('>', '<', '"', '"', '"', '"', '"', '"', "\n", ' '),
            $this->_input
        );
        
        if (substr($configString, 0, 2)=='$$') $configString = $this->uncompress(substr($configString, 2, strlen($configString)));
        
        //echo ini_get('pcre.backtrack_limit');
        //exit;

        $this->debug('parse config (auto correction = '.self::esc(self::toString($autoCorrection)).')');
        $config = null;
        $lastJsonError = null;
        try {
            $config = self::json_decode($configString);
        } catch (Exception $e) {
            $lastJsonError = $e;
        }
        $autoCorrectionWarnings = array();
        $missingEnquoteOfPropertyName = array();
        if ($config) {
            foreach ($config as $code => $object) {
                if (!is_object($object)) {
                    $config = null;
                    break;
                }
            }
        }
        if ($autoCorrection && !$config && $configString!='[]') {
            if (preg_match_all('/((?:#+[^{\\n]*\\s+)+)\\s*{/s', $configString, $result, PREG_SET_ORDER)) {
                $autoCorrectionWarnings[] = 'JSON: usage of incompatible comments';
                foreach ($result as $set) {
                    $commentLines = explode("\n", $set[1]);
                    foreach ($commentLines as $i => $line) {
                        $commentLines[$i] = preg_replace('/^#+\\s/', '', $line);
                    }
                    $comment = trim(implode("\n", $commentLines));
                    $configString = str_replace($set[0], '{"about": "'.str_replace('"', '\\"', $comment).'",', $configString);
                }
            }
            $propertyRegexp = '\\s*(?<property_name>"?[a-z0-9_]+"?)\\s*:\\s*(?<property_value>"(?:(?:[^"]|\\\\")*[^\\\\])?"|'.self::FLOAT_REGEX.'|false|true|null)\\s*(?<property_separator>,)?\\s*(?:\\n)?';
            $objectRegexp = '(?:(?<object_name>"?[a-z0-9_]+"?)\\s*:\\s*)?{\\s*('.$propertyRegexp.')+\\s*}\\s*(?<object_separator>,)?\\s*';
            preg_match_all('/('.$objectRegexp.')/is', $configString, $objectSet, PREG_SET_ORDER);
            //print_r($objectSet);
            $json = array();
            $objectsCount = count($objectSet);
            $toIgnoreCounter = -1;
            foreach ($objectSet as $i => $object) {
                $pos = strpos($configString, $object[0]);
                $toIgnore = trim(substr($configString, 0, $pos));
                if ($toIgnore) {
                    $toIgnoreCounter++;
                    if ($toIgnoreCounter==0) {
                        $bracketPosition = strpos($toIgnore, '{');
                        if ($bracketPosition!==false) {
                            $toIgnore = explode('{', $toIgnore, 2);
                        }
                    }
                    $toIgnore = (array)$toIgnore;
                    foreach ($toIgnore as $toIgnoreItem) {
                        $toIgnoreItem = trim($toIgnoreItem);
                        if (!$toIgnoreItem) continue;
                        $autoCorrectionWarnings[] = 'JSON: ignored lines (<span class=osh-formula>'.self::toString($toIgnoreItem).'</span>)';
                        $n = 0;
                        do {
                            $key = 'meta'.$n;
                            $n++;
                        } while(isset($json[$key]));
                        $json[$key] = array(
                            'type' => 'meta',
                            'ignored' => $toIgnoreItem,
                        );
                    }
                    $configString = substr($configString, $pos, strlen($configString));
                }
                $configString = str_replace($object[0], '', $configString);
                $objectName = isset($object['object_name']) ? $object['object_name'] : null;
                $objectSeparator = isset($object['object_separator']) ? $object['object_separator'] : null;
                $isLastObject = ($i==$objectsCount-1);
                if (!$isLastObject && $objectSeparator!=',') {
                    $autoCorrectionWarnings[] = 'JSON: missing object separator (comma)';
                } else if ($isLastObject && $objectSeparator==',') {
                    $autoCorrectionWarnings[] = 'JSON: no trailing object separator (comma) allowed';
                }
                $jsonObject = array();
                preg_match_all('/'.$propertyRegexp.'/i', $object[0], $propertySet, PREG_SET_ORDER);
                $propertiesCount = count($propertySet);
                foreach ($propertySet as $j => $property) {
                    $name = $property['property_name'];
                    if ($name{0}!='"' || $name{strlen($name)-1}!='"') {
                        $autoCorrectionWarnings['missing_enquote_of_property_name'] = 'JSON: missing enquote of property name: %s';
                        $missingEnquoteOfPropertyName[] = self::toString(trim($name, '"'));
                    }
                    $propertySeparator = isset($property['property_separator']) ? $property['property_separator'] : null;
                    $isLastProperty = ($j==$propertiesCount-1);
                    if (!$isLastProperty && $propertySeparator!=',') {
                        $autoCorrectionWarnings[] = 'JSON: missing property separator (comma)';
                    } else if ($isLastProperty && $propertySeparator==',') {
                        $autoCorrectionWarnings[] = 'JSON: no trailing property separator (comma) allowed';
                    }
                    $jsonObject[trim($name, '"')] = $this->parseProperty($property['property_value']);
                }
                if ($objectName) $json[trim($objectName, '"')] = $jsonObject;
                else if (isset($jsonObject['code'])) {
                    $code = $jsonObject['code'];
                    unset($jsonObject['code']);
                    $json[$code] = $jsonObject;
                } else $json[] = $jsonObject;
            }
            $toIgnore = trim($configString);
            if ($toIgnore) {
                $bracketPosition = strpos($toIgnore, '}');
                if ($bracketPosition!==false) {
                    $toIgnore = explode('}', $toIgnore, 2);
                }
                $toIgnore = (array)$toIgnore;
                foreach ($toIgnore as $toIgnoreItem) {
                    $toIgnoreItem = trim($toIgnoreItem);
                    if (!$toIgnoreItem) continue;
                    $autoCorrectionWarnings[] = 'JSON: ignored lines (<span class=osh-formula>'.self::toString($toIgnoreItem).'</span>)';
                    $n = 0;
                    do {
                        $key = 'meta'.$n;
                        $n++;
                    } while(isset($json[$key]));
                    $json[$key] = array(
                        'type' => 'meta',
                        'ignored' => $toIgnoreItem,
                    );
                }
            }
            $configString = $this->jsonEncode($json);//'['.$configString2.']';
            $configString = str_replace(array("\n"), array("\\n"), $configString);
            //echo $configString;

            $lastJsonError = null;
            try {
                $config = self::json_decode($configString);
            } catch (Exception $e) {
                $lastJsonError = $e;
            }
        }
        if ($lastJsonError) {
            $autoCorrectionWarnings[] = 'JSON: unable to parse config ('.$lastJsonError->getMessage().')';
        }

        $row = null;
        $autoCorrectionWarnings = array_unique($autoCorrectionWarnings);
        foreach ($autoCorrectionWarnings as $key => $warning) {
            if ($key=='missing_enquote_of_property_name') {
                $missingEnquoteOfPropertyName = array_unique($missingEnquoteOfPropertyName);
                $warning = str_replace('%s', '<span class=osh-key>'.self::esc(implode('</span>, <span class=osh-key>', $missingEnquoteOfPropertyName)).'</span>', $warning);
            }
            $this->addMessage('warning', $row, null, $warning);
        }
        $config = (array)$config;
        
        $this->_config = array();
        $availableKeys = array('type', 'about', 'label', 'enabled', 'description', 'fees', 'conditions', 'shipto', 'billto', 'origin', 'customer_groups', 'tracking_url');
        $reservedKeys = array('*id');
        if ($autoCorrection) {
            $availableKeys = array_merge($availableKeys, array(
                'destination', 'code', 
            ));
        }

        $deprecatedProperties = array();
        $unknownProperties = array();

        foreach ($config as $code => $object) {
            $object = (array)$object;
            if ($autoCorrection) {
                if (isset($object['destination'])) {
                    if (!in_array('destination', $deprecatedProperties)) $deprecatedProperties[] = 'destination';
                    $object['shipto'] = $object['destination'];
                    unset($object['destination']);
                }
                if (isset($object['code'])) {
                    if (!in_array('code', $deprecatedProperties)) $deprecatedProperties[] = 'code';
                    $code = $object['code'];
                    unset($object['code']);
                }
            }

            $row = array();
            $i = 1;
            foreach ($object as $propertyName => $propertyValue) {
                if (in_array($propertyName, $reservedKeys)) {
                    continue;
                }
                if (in_array($propertyName, $availableKeys)
                    || substr($propertyName, 0, 1)=='_'
                    || in_array($object['type'], array('data', 'meta'))) {
                    if (isset($propertyValue)) {
                        $row[$propertyName] = array('value' => $propertyValue, 'original_value' => $propertyValue);
                        if ($autoCorrection) $this->cleanProperty($row, $propertyName);
                    }
                } else {
                    if (!in_array($propertyName, $unknownProperties)) $unknownProperties[] = $propertyName;
                }
                $i++;
            }
            $this->addRow($code, $row);
        }
        $row = null;
        if (count($unknownProperties)>0) $this->addMessage('error', $row, null, 'Usage of unknown properties %s', ': <span class=osh-key>'.implode('</span>, <span class=osh-key>', $unknownProperties).'</span>');
        if (count($deprecatedProperties)>0) $this->addMessage('warning', $row, null, 'Usage of deprecated properties %s', ': <span class=osh-key>'.implode('</span>, <span class=osh-key>', $deprecatedProperties).'</span>');
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
            $objectName = isset($aliases[$result[1]]) ? $aliases[$result[1]] : $result[1];
            $replacement = $this->_loadValue($process, $objectName, $result[2]);
            $input = $this->_replaceVariable($process, $input, $original, $replacement);
        }
        return $input;
    }

    protected function _addressMatch(&$process, &$row, $propertyName, $addressFilter, $address)
    {
        //$addressFilter = '(* - ( europe (FR-(25,26),DE(40,42) ))';
        //echo '<pre>';
        $addressFilter = $this->_replaceData($process, $addressFilter);
        $parser = new OS2_AddressFilterParser();
        $addressFilter = $parser->parse($addressFilter);
        
        $this->debug('      address filter = <span class=osh-formula>'.self::esc($addressFilter).'</span>');
        $data = array(
            '{c}' => $address->getData('country_id'),
            '{p}' => $address->getData('postcode'),
            '{r}' => $address->getData('region_code'),
        );
        foreach ($data as $original => $replacement) {
            $addressFilter = $this->_replaceVariable($process, $addressFilter, $original, $replacement);
        }
        return (bool)$this->_evalFormula($addressFilter, $row, $propertyName, $isChecking=false);
    }

    protected function _getItemProperty($item, $propertyName)
    {
        $elems = explode('.', $propertyName, $limit=2);
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

    protected function _processProduct($items, $regexpResult, &$row, $propertyName, $isChecking)
    {
        // count, sum, min, max, count distinct
        $operation = strtolower($regexpResult[1]);
        $returnValue = null;
        $reference = 'items';
        switch ($operation) {
            case 'sum':
            case 'min':
            case 'max':
            case 'count distinct':
                $reference = $regexpResult[2];
                $conditions = isset($regexpResult[3]) ? $regexpResult[3] : null;
                break;
            case 'count':
                $conditions = isset($regexpResult[2]) ? $regexpResult[2] : null;
                break;
        }
        switch ($operation) {
            case 'sum':
            case 'count distinct':
            case 'count':
                $returnValue = 0;
                break;
        }
        
        $this->debug('      <span class=osh-loop>start <span class=osh-replacement>'.self::esc($operation).'</span> '
            .'<span class=osh-key>'.self::esc($reference).'</span>'
            .(isset($conditions) ? ' where <span class=osh-replacement>'.self::esc($conditions).'</span></span>' : '')
        );
        $this->addDebugIndent();

        $properties = array();
        $this->_preg_match_all('#(?:item|product|p)\.([a-z0-9_\+\-\.]+)#i', $conditions, $propertiesRegexpResult);
        foreach ($propertiesRegexpResult as $propertyRegexpResult) {
            if (!isset($properties[$propertyRegexpResult[0]])) $properties[$propertyRegexpResult[0]] = $propertyRegexpResult;
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
                    $evalResult = $this->_evalFormula($formula, $row, $propertyName, $isChecking);
                    if (!isset($evalResult)) $returnValue = 'null';
                }
                else $evalResult = true;

                if ($evalResult==true) {
                    if ($operation=='count') {
                        $returnValue = (isset($returnValue) ? $returnValue : 0) + $item->qty;
                    } else {
                        $value = $this->_getItemProperty($item, $reference);
                        $this->debug('    &raquo; <span class=osh-key>'.self::esc($reference).'</span> = <span class=osh-formula>'.self::esc($value).'</span>'
                            .($operation=='sum' ? ' x <span class=osh-formula>'.$item->qty.'</span>' : ''));
                        switch ($operation) {
                            case 'min': if (!isset($returnValue) || $value<$returnValue) $returnValue = $value; break;
                            case 'max': if (!isset($returnValue) || $value>$returnValue) $returnValue = $value; break;
                            case 'sum':
                                //$this->debug(self::esc($item->getProduct()->sku).'.'.self::esc($reference).' = "'.self::esc($value).'" x '.self::esc($item->qty));
                                $returnValue = (isset($returnValue) ? $returnValue : 0) + $value*$item->qty;
                                break;
                            case 'count distinct':
                                if (!isset($returnValue)) $returnValue = 0;
                                if (!isset($distinctValues)) $distinctValues = array();
                                if (!in_array($value, $distinctValues)) {
                                    $distinctValues[] = $value;
                                    $returnValue++;
                                }
                                break;
                        }
                    }
                }
                $this->debug('    &raquo; <span class=osh-info>'.self::esc($operation).' result</span> = <span class=osh-formula>'.self::esc($returnValue).'</span>');
                $this->removeDebugIndent();
            }
        }

        $this->removeDebugIndent();
        $this->debug('      <span class=osh-loop>end</span>');

        return $returnValue;
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

