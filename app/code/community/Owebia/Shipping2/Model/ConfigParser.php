<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_ConfigParser
{
    const FLOAT_REGEX = '[-]?\d+(?:[.]\d+)?';
    const COUPLE_REGEX = '(?:[0-9.]+|\*) *(?:\[|\])? *\: *[0-9.]+';

    public static $debugIndexCounter = 0;

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
        else if (is_array($value)) return 'array(size:' . count($value) . ')';
        else if (is_object($value)) return get_class($value) . '';
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
        $unitIndex = floor(log($size, 1024));
        $divisor = $unitIndex == 0 ? 1 : pow(1024, $unitIndex);
        return self::toString(round($size / $divisor, 2)) . ' ' . $unit[$unitIndex];
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
            'info'              => Mage::getModel('owebia_shipping2/Os2_Data')->setData(self::getInfos()),
            'cart'              => Mage::getModel('owebia_shipping2/Os2_Data'),
            'quote'             => Mage::getModel('owebia_shipping2/Os2_Data'),
            'selection'         => Mage::getModel('owebia_shipping2/Os2_Data'),
            'customer'          => Mage::getModel('owebia_shipping2/Os2_Data'),
            'customer_group'    => Mage::getModel('owebia_shipping2/Os2_Data'),
            'customvar'         => Mage::getModel('owebia_shipping2/Os2_Data'),
            'date'              => Mage::getModel('owebia_shipping2/Os2_Data'),
            'origin'            => Mage::getModel('owebia_shipping2/Os2_Data'),
            'shipto'            => Mage::getModel('owebia_shipping2/Os2_Data'),
            'billto'            => Mage::getModel('owebia_shipping2/Os2_Data'),
            'store'             => Mage::getModel('owebia_shipping2/Os2_Data'),
            'request'           => Mage::getModel('owebia_shipping2/Os2_Data'),
            'address_filter'    => Mage::getModel('owebia_shipping2/Os2_Data'),
        );
    }

    protected static function jsonEncodeArray($data, $beautify, $html, $level, $currentIndent)
    {
        $indent = "\t";
        $newIndent = $currentIndent . $indent;
        $lineBreak = $html ? '<br/>' : "\n";
        $outputIndexCount = 0;
        $output = array();
        foreach ($data as $key => $value) {
            if ($outputIndexCount !== null && $outputIndexCount++ !== $key) {
                $outputIndexCount = null;
            }
        }
        $isAssociative = $outputIndexCount === null;
        foreach ($data as $key => $value) {
            if ($isAssociative) {
                $classes = array();
                if ($key == 'about') {
                    $classes[] = 'json-about';
                } elseif ($key == 'conditions' || $key == 'fees') {
                    $classes[] = 'json-formula';
                }
                $propertyClasses = array('json-property');
                if ($level == 0) {
                    $propertyClasses[] = 'json-id';
                }
                $output[] = ($html && $classes ? '<span class="' . implode(' ', $classes) . '">' : '')
                    . ($html ? '<span class="' . implode(' ', $propertyClasses) . '">' : '')
                    . self::jsonEncode((string)$key)
                    . ($html ? '</span>' : '') . ':'
                    . ($beautify ? ' ' : '')
                    . self::jsonEncode($value, $beautify, $html, $level+1, $newIndent)
                    . ($html && $classes ? '</span>' : '');
            } else {
                $output[] = self::jsonEncode($value, $beautify, $html, $level+1, $currentIndent);
            }
        }
        if ($isAssociative) {
            $classes = array();
            if (isset($data['type']) && $data['type']=='meta') $classes[] = 'json-meta';
            $output = ($html && $classes ? '<span class="' . implode(' ', $classes) . '">' : '')
                .'{'
                .($beautify ? "{$lineBreak}{$newIndent}" : '')
                .implode(',' . ($beautify ? "{$lineBreak}{$newIndent}" : ''), $output)
                .($beautify ? "{$lineBreak}{$currentIndent}" : '')
                .'}'
                .($html && $classes ? '</span>' : '');
            return $output;
        } else {
            return '[' . implode(',' . ($beautify ? ' ' : ''), $output) . ']';
        }
    }

    public static function jsonEncode($data, $beautify = false, $html = false, $level = 0, $currentIndent = '')
    {
        switch ($type = self::getType($data)) {
            case 'NULL':
                return ($html ? '<span class=json-reserved>' : '') . 'null' . ($html ? '</span>' : '');
            case 'boolean':
                return ($html ? '<span class=json-reserved>' : '')
                    . ($data ? 'true' : 'false')
                    . ($html ? '</span>' : '');
            case 'integer':
            case 'double':
            case 'float':
                return ($html ? '<span class=json-numeric>' : '') . $data . ($html ? '</span>' : '');
            case 'string':
                return ($html ? '<span class=json-string>' : '')
                    . '"'
                    . str_replace(
                        array("\\", '"', "\n", "\r"),
                        array("\\\\", '\"', "\\n", "\\r"),
                        $html ? htmlspecialchars($data, ENT_COMPAT, 'UTF-8') : $data
                    )
                    . '"'
                    . ($html ? '</span>' : '');
            case 'object':
                $data = (array)$data;
            case 'array':
                return static::jsonEncodeArray($data, $beautify, $html, $level, $currentIndent);
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
                if ($error != JSON_ERROR_NONE) {
                    Mage::throwException($error);
                }
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
        $escaped = preg_replace_callback(
            '/\\\\u([0-9a-fA-F]{4})/',
            function ($match) {
                return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
            },
            $escaped
        );
        return $escaped;
    }

    protected $_input;
    protected $_config;
    protected $_messages;
    protected $_formulaCache;
    protected $_expressionCache;
    protected $_debugPrefix;
    public $debugCode = null;
    public $debugOutput = '';
    public $debugHeader = null;

    public function init($input, $autoCorrection)
    {
        $this->_config = array();
        $this->_messages = array();
        $this->_formulaCache = array();
        $this->_expressionCache = array();
        $this->_debugPrefix = '';
        $this->_input = $input;
        $this->_parseInput($autoCorrection);
        return $this;
    }

    public function addDebugIndent()
    {
        $this->_debugPrefix .= '   ';
    }

    public function removeDebugIndent()
    {
        $this->_debugPrefix = substr($this->_debugPrefix, 0, strlen($this->_debugPrefix) - 3);
    }

    public function debug($text)
    {
        $this->debugOutput .= "<p>{$this->_debugPrefix}{$text}</p>";
    }

    public function getDebug()
    {
        $index = $this->debugCode . '-' . self::$debugIndexCounter++;
        $output = "<style rel=stylesheet type=\"text/css\">"
            . ".osh-debug{background:#000;color:#bbb;-webkit-opacity:0.9;-moz-opacity:0.9;opacity:0.9;"
                . "text-align:left;white-space:pre-wrap;overflow:auto;}"
            . ".osh-debug p{margin:2px 0;}"
            . ".osh-formula{color:#f90;} .osh-key{color:#0099f7;} .osh-loop{color:#ff0;}"
            . ".osh-error{color:#f00;} .osh-warning{color:#ff0;} .osh-info{color:#7bf700;}"
            . ".osh-debug-content{padding:10px;font-family:monospace}"
            . ".osh-replacement{color:#ff3000;}"
            . "</style>"
            . "<div id=osh-debug-{$index} class=osh-debug>"
                . "<div class=osh-debug-content>"
                    . "<span class=osh-close style=\"float:right;cursor:pointer;\""
                        . " onclick=\"document.getElementById('osh-debug-{$index}').style.display = 'none';\""
                        . ">[<span style=\"padding:0 5px;color:#f00;\">X</span>]</span>"
            . "<p>{$this->debugHeader}</p>{$this->debugOutput}</div></div>";
        return $output;
    }

    protected function getVariableDisplay($variablePath, $variableValue)
    {
        return '<span class=osh-key>'
            . self::esc(str_replace('.', '</span>.<span class=osh-key>', $variablePath))
            . '</span> = <span class=osh-formula>'
            . self::esc(self::toString($variableValue))
            . '</span> (' . self::getType($variableValue) . ')<br/>';
    }

    protected function initDebugData($objectName, $data)
    {
        $header = '';
        if (is_object($data) || is_array($data)) {
            $header .= '      <span class=osh-key>'
                . self::esc(str_replace('.', '</span>.<span class=osh-key>', $objectName))
                . '</span> &gt;&gt;<br/>';
            $children = array();
            if (is_object($data)) {
                $children = $data->__sleep();
            } elseif (is_array($data)) {
                $children = array_keys($data);
            }
            foreach ($children as $name) {
                $key = $name;
                if ($key == '*') {
                    $header .= '         .<span class=osh-key>'
                        . self::esc(str_replace('.', '</span>.<span class=osh-key>', $key))
                        . '</span> = …<br/>';
                } else {
                    if (is_object($data)) {
                        $value = $data->{$name};
                    } elseif (is_array($data)) {
                        $children = $data[$name];
                    }
                    $header .= '         .' . $this->getVariableDisplay($key, $value);
                }
            }
        } else {
            $header .= '      .' . $this->getVariableDisplay($objectName, $data);
        }
        return $header;
    }

    public function initDebug($code, $process)
    {
        $header = 'DEBUG ConfigParser.php<br/>';
        foreach ($process as $index => $processOption) {
            if (in_array($index, array('data', 'options'))) {
                $header .= '   <span class=osh-key>'
                    . self::esc(str_replace('.', '</span>.<span class=osh-key>', $index))
                    . '</span> &gt;&gt;<br/>';
                foreach ($processOption as $objectName => $data) {
                    $header .= $this->initDebugData($objectName, $data);
                }
            } else {
                $header .= '   ' . $this->getVariableDisplay($index, $processOption);
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

    public function sortProperties($firstKey, $secondKey)
    {
        $firstKeyPosition = isset($this->propertiesSort[$firstKey]) ? $this->propertiesSort[$firstKey] : 1000;
        $secondKeyPosition = isset($this->propertiesSort[$secondKey]) ? $this->propertiesSort[$secondKey] : 1000;
        return $firstKeyPosition == $secondKeyPosition
            ? strcmp($firstKey, $secondKey) : $firstKeyPosition - $secondKeyPosition;
    }

    public function formatConfig($compress, $keysToRemove = array(), $html = false)
    {
        $objectArray = array();
        $this->propertiesSort = array_flip(
            array(
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
            )
        );
        foreach ($this->_config as $code => $row) {
            $object = array();
            foreach ($row as $key => $property) {
                if (substr($key, 0, 1) != '*' && !in_array($key, $keysToRemove)) {
                    $object[$key] = $property['value'];
                }
            }
            uksort($object, array($this, 'sortProperties'));
            $objectArray[$code] = $object;
        }
        return self::jsonEncode($objectArray, $beautify = !$compress, $html);
    }

    public function checkConfig()
    {
        $timestamp = (int)Mage::getModel('core/date')->timestamp();
        $process = array(
            'config' => $this->_config,
            'data' => self::getDefaultProcessData(),
            'result' => null,
        );
        foreach ($this->_config as $code => &$row) {
            $this->processRow($process, $row, $checkAllConditions = true);
            foreach ($row as $propertyName => $propertyValue) {
                if (substr($propertyName, 0, 1) != '*') {
                    $this->debug('   check ' . $propertyName);
                    $this->getRowProperty($row, $propertyName);
                }
            }
        }
    }

    protected function createResult()
    {
        return Mage::getModel('owebia_shipping2/Os2_Result')
            ->setConfigParser($this);
    }

    protected function processRowType(&$row)
    {
        $type = $this->getRowProperty($row, 'type');
        if ($type == 'data') {
            foreach ($row as $key => $data) {
                if (in_array($key, array('*id', 'code', 'type'))) continue;
                $value = isset($data['value']) ? $data['value'] : $data;
                $this->debug(
                    '         .<span class=osh-key>' . self::esc($key) . '</span>'
                    . ' = <span class=osh-formula>' . self::esc(self::toString($value)) . '</span>'
                    . ' (' . self::getType($value) . ')'
                );
            }
            return $this->createResult()
                ->setSuccess(false);
        }
        if (isset($type) && $type != 'method') {
            return $this->createResult()
                ->setSuccess(false);
        }
        return null;
    }

    protected function processRowEnabled(&$row, $isChecking)
    {
        $enabled = $this->getRowProperty($row, 'enabled');
        if (isset($enabled)) {
            if (!$isChecking && !$enabled) {
                $this->addMessage('info', $row, 'enabled', 'Configuration disabled');
                return $this->createResult()
                    ->setSuccess(false);
            }
        }
        return null;
    }

    protected function processRowConditions($process, &$row, $isChecking)
    {
        $conditions = $this->getRowProperty($row, 'conditions');
        if (isset($conditions)) {
            $result = $this->processFormula($process, $row, 'conditions', $conditions, $isChecking);
            if (!$isChecking) {
                if (!$result->success) return $result;
                if (!$result->result) {
                    $this->addMessage('info', $row, 'conditions', "The cart doesn't match conditions");
                    return $this->createResult()
                        ->setSuccess(false);
                }
            }
        }
        return null;
    }

    protected function processRowCustomerGroups($process, &$row, $isChecking)
    {
        $customerGroups = $this->getRowProperty($row, 'customer_groups');
        if (isset($customerGroups)) {
            $groups = explode(',', $customerGroups);
            $groupMatch = false;
            $customerGroup = $process['data']['customer_group'];
            foreach ($groups as $group) {
                $group = trim($group);
                if ($group == '*'
                    || $group == $customerGroup->code
                    || ctype_digit($group) && $group == $customerGroup->id
                ) {
                    $this->debug(
                        '      group <span class=osh-replacement>' . self::esc($customerGroup->code) . '</span>'
                        . ' (id:<span class=osh-replacement>' . self::esc($customerGroup->id) . '</span>) matches'
                    );
                    $groupMatch = true;
                    break;
                }
            }
            if (!$isChecking && !$groupMatch) {
                $this->addMessage(
                    'info',
                    $row,
                    'customer_groups',
                    "Customer group not allowed (%s)",
                    $customerGroup->code
                );
                return $this->createResult()
                    ->setSuccess(false);
            }
        }
        return null;
    }

    protected function processRowAddresses($process, &$row, $isChecking)
    {
        $addressProperties = array(
            'shipto' => "Shipping zone not allowed",
            'billto' => "Billing zone not allowed",
            'origin' => "Shipping origin not allowed",
        );
        foreach ($addressProperties as $propertyName => $failureMessage) {
            $propertyValue = $this->getRowProperty($row, $propertyName);
            if (isset($propertyValue)) {
                $match = $this->_addressMatch(
                    $process,
                    $row,
                    $propertyName,
                    $propertyValue,
                    $process['data'][$propertyName]
                );
                if (!$isChecking && !$match) {
                    $this->addMessage('info', $row, $propertyName, $failureMessage);
                    return $this->createResult()
                        ->setSuccess(false);
                }
            }
        }
        return null;
    }

    protected function processRowFees($process, &$row, $isChecking)
    {
        $fees = $this->getRowProperty($row, 'fees');
        if (isset($fees)) {
            $result = $this->processFormula($process, $row, 'fees', $fees, $isChecking);
            if (!$result->success) return $result;
            $this->debug(
                '    &raquo; <span class=osh-info>result</span>'
                . ' = <span class=osh-formula>' . self::esc(self::toString($result->result)) . '</span>'
            );
            return $this->createResult()
                ->setSuccess(true)
                ->setResult((float)$result->result);
        }
        return null;
    }

    public function processRow($process, &$row, $isChecking = false)
    {
        if (!isset($row['*id'])) {
            $this->debug('skip row with unknown id');
            return $this->createResult()
                ->setSuccess(false);
        }
        $this->debug('process row <span class=osh-key>' . self::esc($row['*id']) . '</span>');

        if (isset($row['about'])) { // Display on debug
            $about = $this->getRowProperty($row, 'about');
        }

        $result = $this->processRowType($row);
        if (isset($result)) {
            return $result;
        }

        if (!isset($row['label']['value'])) {
            $row['label']['value'] = '***';
        }

        $result = $this->processRowEnabled($row, $isChecking);
        if (isset($result)) {
            return $result;
        }

        $result = $this->processRowConditions($process, $row, $isChecking);
        if (isset($result)) {
            return $result;
        }

        $result = $this->processRowAddresses($process, $row, $isChecking);
        if (isset($result)) {
            return $result;
        }

        $result = $this->processRowCustomerGroups($process, $row, $isChecking);
        if (isset($result)) {
            return $result;
        }

        $result = $this->processRowFees($process, $row, $isChecking);
        if (isset($result)) {
            return $result;
        }
        return $this->createResult()
            ->setSuccess(false);
    }

    public function getRowProperty(&$row, $key, $originalRow = null, $originalKey = null)
    {
        $property = null;
        $output = null;
        if (isset($originalRow) && isset($originalKey) && $originalRow['*id'] == $row['*id'] && $originalKey == $key) {
            $this->addMessage(
                'error',
                $row,
                $key,
                'Infinite loop %s',
                "<span class=\"code\">{{$row['*id']}.{$key}}</span>"
            );
            return array('error' => 'Infinite loop');
        }
        if (isset($row[$key]['value'])) {
            $property = $row[$key]['value'];
            $output = $property;
            $this->debug(
                '   get <span class=osh-key>' . self::esc($row['*id']) . '</span>'
                . '.<span class=osh-key>' . self::esc($key) . '</span>'
                . ' = <span class=osh-formula>' . self::esc(self::toString($property)) . '</span>'
            );
            preg_match_all('/{([a-z0-9_]+)\.([a-z0-9_]+)}/i', $output, $resultSet, PREG_SET_ORDER);
            foreach ($resultSet as $result) {
                list($original, $refCode, $refKey) = $result;
                if ($refCode == $row['*id'] && $refKey == $key) {
                    $this->addMessage(
                        'error',
                        $row,
                        $key,
                        'Infinite loop %s',
                        "<span class=\"code\">{$original}</span>"
                    );
                    return null;
                }
                if (isset($this->_config[$refCode][$refKey]['value'])) {
                    $replacement = $this->getRowProperty(
                        $this->_config[$refCode],
                        $refKey,
                        isset($originalRow) ? $originalRow : $row,
                        isset($originalKey) ? $originalKey : $key
                    );
                    if (is_array($replacement) && isset($replacement['error'])) {
                        return isset($originalRow) ? $replacement : 'false';
                    }
                    $output = $this->replace('{' . $original . '}', $this->_autoEscapeStrings($replacement), $output);
                    $output = $this->replace($original, $replacement, $output);
                } else {
                    $replacement = $original;
                    $output = $this->replace($original, $replacement, $output);
                }
            }
        } else {
            $this->debug(
                '   get <span class=osh-key>' . self::esc($row['*id']) . '</span>'
                . '.<span class=osh-key>' . self::esc($key) . '</span>'
                . ' = <span class=osh-formula>null</span>'
            );
        }
        return $output;
    }

    public function evalInput($process, $row, $propertyName, $input)
    {
        $result = $this->_prepareFormula($process, $row, $propertyName, $input, $isChecking = false, $useCache = true);
        return $result->success ? $result->result : $input;
    }

    public function parseProperty($input)
    {
        if ($input === 'false') return false;
        if ($input === 'true') return true;
        if ($input === 'null') return null;
        if (is_numeric($input)) return (double)$input;
        $value = str_replace('\"', '"', preg_replace('/^(?:"|\')(.*)(?:"|\')$/s', '$1', $input));
        return $value === '' ? null : $value;
    }

    protected function replace($from, $to, $input, $className = null, $message = 'replace')
    {
        if ($from === $to) return $input;
        if (mb_strpos($input, $from) === false) return $input;
        $to = self::toString($to);
        $to = preg_replace('/[\r\n\t]+/', ' ', $to);
        $this->debug(
            '      '
            . ($className ? '<span class="osh-' . $className . '">' : '')
            . $message
            . ' <span class=osh-replacement>' . self::esc(self::toString($from)) . '</span>'
            . ' by <span class=osh-replacement>' . self::esc($to) . '</span>'
            . ' =&gt; <span class=osh-formula>' . self::esc(
                str_replace($from, '<span class=osh-replacement>' . $to . '</span>', $input)
            ) . '</span>'
            . ($className ? '</span>' : '')
        );
        return str_replace($from, $to, $input);
    }

    protected function _min()
    {
        $args = func_get_args();
        $min = null;
        foreach ($args as $arg) {
            if (isset($arg) && (!isset($min) || $min > $arg)) $min = $arg;
        }
        return $min;
    }

    protected function _max()
    {
        $args = func_get_args();
        $max = null;
        foreach ($args as $arg) {
            if (isset($arg) && (!isset($max) || $max < $arg)) $max = $arg;
        }
        return $max;
    }

    protected function _range($value = -1, $minValue = 0, $maxValue = 1, $includeMin = true, $includeMax = true)
    {
        return ($value > $minValue || $includeMin && $value == $minValue)
            && ($value < $maxValue || $includeMax && $value == $maxValue);
    }

    protected function callFunction($functionName, $args)
    {
        return call_user_func_array($functionName, $args);
    }

    protected function _array_match_any()
    {
        $args = func_get_args();
        $result = $this->callFunction('array_intersect', $args);
        return (bool)$result;
    }

    protected function _array_match_all()
    {
        $args = func_get_args();
        if (!isset($args[0]) || !isset($args[1])) return false;
        $result = $this->callFunction('array_intersect', $args);
        return count($result) == count($args[1]);
    }

    public function processFormula($process, &$row, $propertyName, $formulaString, $isChecking, $useCache = true)
    {
        $result = $this->_prepareFormula($process, $row, $propertyName, $formulaString, $isChecking, $useCache);
        if (!$result->success) return $result;

        $evalResult = $this->_evalFormula($result->result, $row, $propertyName, $isChecking);
        if (!$isChecking && !isset($evalResult)) {
            $this->addMessage('error', $row, $propertyName, 'Empty result');
            $result = $this->createResult()
                ->setSuccess(false);
            if ($useCache) $this->_setCache($formulaString, $result);
            return $result;
        }
        $result = $this->createResult()
            ->setSuccess(true)
            ->setResult($evalResult);
        if ($useCache) $this->_setCache($formulaString, $result);
        return $result;
    }

    protected function _setCache($expression, $value)
    {
        if ($value instanceof Owebia_Shipping2_Model_Os2_Result) {
            $this->_formulaCache[$expression] = $value;
            $this->debug(
                '      cache <span class=osh-replacement>' . self::esc($expression) . '</span>'
                . ' = <span class=osh-formula>' . self::esc(self::toString($value->result)) . '</span>'
                . ' (' . self::getType($value->result) . ')'
            );
        } else {
            $this->_expressionCache[$expression] = $value; // Do not use self::toString to make isset work
            $this->debug(
                '      cache <span class=osh-replacement>' . self::esc($expression) . '</span>'
                . ' = <span class=osh-formula>' . self::esc(self::toString($value)) . '</span>'
                . ' (' . self::getType($value) . ')'
            );
        }
    }

    protected function _getCachedExpression($original)
    {
        $replacement = $this->_expressionCache[$original];
        $this->debug(
            '      get cached expression <span class=osh-replacement>' . self::esc($original) . '</span>'
            . ' = <span class=osh-formula>' . self::esc(self::toString($replacement)) . '</span>'
            . ' (' . self::getType($replacement) . ')'
        );
        return $replacement;
    }

    protected function _prepare_regexp($regexp)
    {
        if (!isset($this->constants)) {
            $reflector = new ReflectionClass(get_class($this));
            $this->constants = $reflector->getConstants();
        }
        foreach ($this->constants as $name => $value) {
            $regexp = str_replace('{' . $name . '}', $value, $regexp);
        }
        return $regexp;
    }

    protected function _preg_match($regexp, $input, &$result, $debug = false)
    {
        $regexp = $this->_prepare_regexp($regexp);
        if ($debug) $this->debug('      preg_match <span class=osh-replacement>' . self::esc($regexp) . '</span>');
        return preg_match($regexp, $input, $result);
    }

    protected function _preg_match_all($regexp, $input, &$result, $debug = false)
    {
        $regexp = $this->_prepare_regexp($regexp);
        if ($debug) $this->debug('      preg_match_all <span class=osh-replacement>' . self::esc($regexp) . '</span>');
        $return = preg_match_all($regexp, $input, $result, PREG_SET_ORDER);
    }

    protected function _loadValue($process, $objectName, $attribute)
    {
        switch ($objectName) {
            case 'item':
                return isset($process['data']['cart']->items[0])
                    ? $process['data']['cart']->items[0]->{$attribute} : null;
            case 'product':
                return isset($process['data']['cart']->items[0])
                    ? $process['data']['cart']->items[0]->getProduct()->{$attribute} : null;
            default:
                return isset($process['data'][$objectName])
                    ? $process['data'][$objectName]->{$attribute} : null;
        }
    }

    protected function _prepareFormulaForeach($process, $row, $propertyName, $formula, $isChecking, $useCache)
    {
        $foreachRegexp = "#{foreach ((?:item|product|p)\.[a-z0-9_\+\-\.]+)}(.*){/foreach}#iU";
        $itemsCount = count($process['data']['cart']->items);
        while ($this->_preg_match($foreachRegexp, $formula, $result)) { // ungreedy
            $original = $result[0];
            if ($useCache && array_key_exists($original, $this->_expressionCache)) {
                $replacement = $this->_getCachedExpression($original);
            } else {
                $replacement = 0;
                $loopVar = $result[1];
                $selections = array();
                $this->debug('      foreach <span class=osh-key>' . self::esc($loopVar) . '</span>');
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
                        $this->debug(
                            '      items[<span class=osh-formula>' . self::esc((string)$item) . '</span>]'
                            . '.<span class=osh-key>' . self::esc($loopVar) . '</span>'
                            . ' = [<span class=osh-formula>' . self::esc(
                                implode('</span>, <span class=osh-formula>', $values)
                            ) . '</span>]'
                        );
                    }
                }
                $this->removeDebugIndent();
                $this->debug('      <span class=osh-loop>start foreach</span>');
                $this->addDebugIndent();
                foreach ($selections as $key => $selection) {
                    $this->debug(
                        '     <span class=osh-loop>&bull; value</span>'
                        . ' = <span class=osh-formula>' . self::esc($key) . '</span>'
                    );
                    $this->addDebugIndent();
                    $this->debug(' #### count  ' . $itemsCount);
                    $tmpProcess = $process;
                    // Important: clone to not override previous items
                    $tmpProcess['data']['cart'] = clone $tmpProcess['data']['cart'];
                    $tmpProcess['data']['cart']->items = $selection['items'];
                    $selection['qty'] = 0;
                    $selection['weight'] = 0;
                    foreach ($selection['items'] as $item) {
                        $selection['qty'] += $item->qty;
                        $selection['weight'] += $item->weight;
                    }
                    if (isset($tmpProcess['data']['selection'])) {
                        $tmpProcess['data']['selection']->set('qty', $selection['qty']);
                        $tmpProcess['data']['selection']->set('weight', $selection['weight']);
                    }
                    $processResult = $this->processFormula(
                        $tmpProcess,
                        $row,
                        $propertyName,
                        $result[2],
                        $isChecking,
                        $tmpUseCache = false
                    );
                    $replacement += $processResult->result;
                    $this->debug(
                        '    &raquo; <span class=osh-info>foreach sum result</span>'
                        . ' = <span class=osh-formula>' . self::esc(self::toString($replacement)) . '</span>'
                    );
                    $this->removeDebugIndent();
                }
                $this->removeDebugIndent();
                $this->debug('      <span class=osh-loop>end</span>');
                if ($useCache) $this->_setCache($original, $replacement);
            }
            $formula = $this->replace($original, $replacement, $formula);
        }
        return $formula;
    }

    protected function _prepareFormulaSwitch($row, $propertyName, $formula, $isChecking, $useCache)
    {
        while (preg_match("/{switch ([^}]+) in ([^}]+)}/i", $formula, $result)) {
            $original = $result[0];
            if ($useCache && array_key_exists($original, $this->_expressionCache)) {
                $replacement = $this->_getCachedExpression($original);
            } else {
                $referenceValue = $this->_evalFormula($result[1], $row, $propertyName, $isChecking);
                $feesTableString = $result[2];

                $coupleRegexp = '[^}:]+ *\: *[0-9.]+ *';
                if (!preg_match('#^ *' . $coupleRegexp . '(?:, *' . $coupleRegexp . ')*$#', $feesTableString)) {
                    $this->addMessage(
                        'error',
                        $row,
                        $propertyName,
                        'Error in switch %s',
                        '<span class=osh-formula>' . self::esc($result[0]) . '</span>'
                    );
                    $result = $this->createResult()
                        ->setSuccess(false);
                    if ($useCache) $this->_setCache($formulaString, $result);
                    return $result;
                }
                $feesTable = explode(',', $feesTableString);

                $replacement = null;
                foreach ($feesTable as $item) {
                    $feeData = explode(':', $item);

                    $fee = trim($feeData[1]);
                    $value = trim($feeData[0]);
                    $value = $value == '*' ? '*' : $this->_evalFormula($feeData[0], $row, $propertyName, $isChecking);

                    if ($value == '*' || $referenceValue === $value) {
                        $replacement = $fee;
                        $this->debug(
                            '      compare <span class=osh-formula>'
                            . self::esc($this->_autoEscapeStrings($referenceValue)) . '</span>'
                            . ' == <span class=osh-formula>' . self::esc($this->_autoEscapeStrings($value)) . '</span>'
                        );
                        break;
                    }
                    $this->debug(
                        '      compare <span class=osh-formula>'
                        . self::esc($this->_autoEscapeStrings($referenceValue)) . '</span>'
                        . ' != <span class=osh-formula>' . self::esc($this->_autoEscapeStrings($value)) . '</span>'
                    );
                }
                if ($useCache) $this->_setCache($original, $replacement);
            }
            $formula = $this->replace($original, $replacement, $formula);
        }
        return $formula;
    }

    protected function _prepareFormulaTableIncludeMaxValue($lastChar)
    {
        if ($lastChar == '[') {
            return false;
        } elseif ($lastChar == ']') {
            return true;
        } else {
            return true;
        }
    }

    protected function _prepareFormulaTable($row, $propertyName, $formula, $isChecking, $useCache)
    {
        while (preg_match("/{table ([^}]+) in ([0-9\.:,\*\[\] ]+)}/i", $formula, $result)) {
            $original = $result[0];
            if ($useCache && array_key_exists($original, $this->_expressionCache)) {
                $replacement = $this->_getCachedExpression($original);
            } else {
                $referenceValue = $this->_evalFormula($result[1], $row, $propertyName, $isChecking);
                $replacement = null;
                if (isset($referenceValue)) {
                    $feesTableString = $result[2];
                    $feesTableRegexp = '#^' . self::COUPLE_REGEX . '(?:, *' . self::COUPLE_REGEX . ')*$#';
                    if (!preg_match($feesTableRegexp, $feesTableString)) {
                        $this->addMessage(
                            'error',
                            $row,
                            $propertyName,
                            'Error in table %s',
                            '<span class=osh-formula>' . self::esc($result[0]) . '</span>'
                        );
                        $result = $this->createResult()
                            ->setSuccess(false);
                        if ($useCache) $this->_setCache($formulaString, $result);
                        return $result;
                    }
                    $feesTable = explode(',', $feesTableString);
                    foreach ($feesTable as $item) {
                        $feeData = explode(':', $item);

                        $fee = trim($feeData[1]);
                        $maxValue = trim($feeData[0]);

                        $includingMaxValue = $this->_prepareFormulaTableIncludeMaxValue(
                            $maxValue{strlen($maxValue) - 1}
                        );

                        $maxValue = str_replace(array('[', ']'), '', $maxValue);

                        if ($maxValue == '*'
                            || $includingMaxValue && $referenceValue <= $maxValue
                            || !$includingMaxValue && $referenceValue < $maxValue
                        ) {
                            $replacement = $fee;
                            break;
                        }
                    }
                }
                if ($useCache) $this->_setCache($original, $replacement);
            }
            $formula = $this->replace($original, $replacement, $formula);
        }
        return $formula;
    }

    protected function _prepareFormula($process, $row, $propertyName, $formulaString, $isChecking, $useCache = true)
    {
        if ($useCache && isset($this->_formulaCache[$formulaString])) {
            $result = $this->_formulaCache[$formulaString];
            $this->debug(
                '      get cached formula <span class=osh-replacement>' . self::esc($formulaString) . '</span>'
                . ' = <span class=osh-formula>' . self::esc(self::toString($result->result)) . '</span>'
            );
            return $result;
        }

        $formula = $formulaString;

        $formula = $this->_prepareFormulaForeach($process, $row, $propertyName, $formula, $isChecking, $useCache);

        if (isset($process['data']['selection'])) {
            if ($process['data']['selection']->weight == null) {
                $process['data']['selection']->set('weight', $process['data']['cart']->weight);
            }
            if ($process['data']['selection']->qty == null) {
                $process['data']['selection']->set('qty', $process['data']['cart']->qty);
            }
        }

        // data
        $aliases = array(
            'p' => 'product',
            'c' => 'cart',
            's' => 'selection',
        );
        $formula = $this->_replaceData($process, $formula, 'item|product|p|c|s', $aliases);

        // count, sum, min, max
        $countRegexp = "/{(count)\s+items\s*(?:\s+where\s+((?:[^\"'}]|'[^']+'|\"[^\"]+\")+))?}/i";
        $sumMinMaxCountRegexp = "/{(sum|min|max|count distinct) ((?:item|product|p)\.[a-z0-9_\+\-\.]+)"
            . "(?: where ((?:[^\"'}]|'[^']+'|\"[^\"]+\")+))?}/i";
        while ($this->_preg_match($countRegexp, $formula, $result)
            || $this->_preg_match($sumMinMaxCountRegexp, $formula, $result)
        ) {
            $original = $result[0];
            if ($useCache && array_key_exists($original, $this->_expressionCache)) {
                $replacement = $this->_getCachedExpression($original);
            } else {
                $replacement = $this->_processProduct(
                    $process['data']['cart']->items,
                    $result,
                    $row,
                    $propertyName,
                    $isChecking
                );
                if ($useCache) $this->_setCache($result[0], $replacement);
            }
            $formula = $this->replace($original, $replacement, $formula);
        }

        $formula = $this->_prepareFormulaSwitch($row, $propertyName, $formula, $isChecking, $useCache);
        $formula = $this->_prepareFormulaTable($row, $propertyName, $formula, $isChecking, $useCache);

        $result = $this->createResult()
            ->setSuccess(true)
            ->setResult($formula);
        return $result;
    }

    protected function _evalFormula($formula, &$row, $propertyName = null, $isChecking = false)
    {
        if (is_bool($formula)) return $formula;
        if (
            !preg_match(
                '/^(?:'
                . '\b(?:'
                    . 'E|e|int|float|string|boolean|object|array|true|false|null|and|or|in'
                    . '|floor|ceil|round|rand|pow|pi|sqrt|log|exp|abs|substr|strtolower|preg_match|in_array'
                    . '|max|min|range|array_match_any|array_match_all'
                    . '|date|strtotime'
                . ')\b'
                . '|\'[^\']*\'|\"[^\"]*\"|[0-9,\'\.\-\(\)\*\/\?\:\+\<\>\=\&\|%!]|\s)*$/',
                $formula
            )
        ) {
            $errors = array(
                PREG_NO_ERROR => 'PREG_NO_ERROR',
                PREG_INTERNAL_ERROR => 'PREG_INTERNAL_ERROR',
                PREG_BACKTRACK_LIMIT_ERROR => 'PREG_BACKTRACK_LIMIT_ERROR',
                PREG_RECURSION_LIMIT_ERROR => 'PREG_RECURSION_LIMIT_ERROR',
                PREG_BAD_UTF8_ERROR => 'PREG_BAD_UTF8_ERROR',
                defined('PREG_BAD_UTF8_OFFSET_ERROR') ? PREG_BAD_UTF8_OFFSET_ERROR : 'PREG_BAD_UTF8_OFFSET_ERROR'
                    => 'PREG_BAD_UTF8_OFFSET_ERROR',
            );
            $error = preg_last_error();
            if (isset($errors[$error])) $error = $errors[$error];
            if ($isChecking) {
                $this->addMessage('error', $row, $propertyName, $error . ' (' . $formula . ')');
            }
            $this->debug('      eval <span class=osh-formula>' . self::esc($formula) . '</span>');
            $this->debug('      doesn\'t match (' . self::esc($error) . ')');
            return null;
        }
        $formula = preg_replace('@\b(min|max|range|array_match_any|array_match_all)\(@', '\$this->_\1(', $formula);
        $evalResult = null;
        @eval('$evalResult = (' . $formula . ');');
        $this->debug(
            '      evaluate <span class=osh-formula>' . self::esc($formula) . '</span>'
            . ' = <span class=osh-replacement>' . self::esc($this->_autoEscapeStrings($evalResult)) . '</span>'
        );
        return $evalResult;
    }

    protected function _parseInputIsValidConfig($config)
    {
        if ($config) {
            foreach ($config as $code => $object) {
                if (!is_object($object)) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    protected function _parseInputIgnore($toIgnore, &$json, &$autoCorrectionWarnings)
    {
        foreach ($toIgnore as $toIgnoreItem) {
            $toIgnoreItem = trim($toIgnoreItem);
            if (!$toIgnoreItem) continue;
            $autoCorrectionWarnings[] = 'JSON: ignored lines (<span class=osh-formula>'
                . self::toString($toIgnoreItem) . '</span>)';
            $n = 0;
            do {
                $key = 'meta' . $n;
                $n++;
            } while (isset($json[$key]));
            $json[$key] = array(
                'type' => 'meta',
                'ignored' => $toIgnoreItem,
            );
        }
    }

    protected function getChar($charCode)
    {
        return utf8_encode(chr($charCode));
    }

    protected function _parseInputPrepareInput($input)
    {
        $openingQuote = $this->getChar(147);
        $closingQuote = $this->getChar(148);
        $input = str_replace(
            array('&gt;', '&lt;', '“', '”', $openingQuote, $closingQuote, '&laquo;', '&raquo;', "\r\n", "\t"),
            array('>', '<', '"', '"', '"', '"', '"', '"', "\n", ' '),
            $input
        );
        return $input;
    }

    protected function _parseInputParseJsonObject($object, &$autoCorrectionWarnings, &$missingEnquoteOfPropertyName)
    {
        $jsonObject = array();
        $propertyRegexp = $this->getPropertyRegexp();
        preg_match_all('/' . $propertyRegexp . '/i', $object[0], $propertySet, PREG_SET_ORDER);
        $propertiesCount = count($propertySet);
        foreach ($propertySet as $j => $property) {
            $name = $property['property_name'];
            if ($name{0} != '"' || $name{strlen($name) - 1} != '"') {
                $autoCorrectionWarnings['missing_enquote_of_property_name'] =
                    'JSON: missing enquote of property name: %s';
                $missingEnquoteOfPropertyName[] = self::toString(trim($name, '"'));
            }
            $propertySeparator = isset($property['property_separator'])
                ? $property['property_separator'] : null;
            $isLastProperty = ( $j == $propertiesCount - 1 );
            if (!$isLastProperty && $propertySeparator != ',') {
                $autoCorrectionWarnings[] = 'JSON: missing property separator (comma)';
            } else if ($isLastProperty && $propertySeparator == ',') {
                $autoCorrectionWarnings[] = 'JSON: no trailing property separator (comma) allowed';
            }
            $jsonObject[trim($name, '"')] = $this->parseProperty($property['property_value']);
        }
        return $jsonObject;
    }

    protected function getPropertyRegexp()
    {
        return '\\s*(?<property_name>"?[a-z0-9_]+"?)\\s*:\\s*'
            . '(?<property_value>"(?:(?:[^"]|\\\\")*[^\\\\])?"|' . self::FLOAT_REGEX . '|false|true|null)'
            . '\\s*(?<property_separator>,)?\\s*(?:\\n)?';
    }

    protected function getObjectRegexp()
    {
        return '(?:(?<object_name>"?[a-z0-9_]+"?)\\s*:\\s*)?{\\s*'
            . '(' . $this->getPropertyRegexp() . ')+\\s*}\\s*(?<object_separator>,)?\\s*';
    }

    protected function _parseInputParseJsonObjectSet(
        &$configString,
        &$autoCorrectionWarnings,
        &$missingEnquoteOfPropertyName
    )
    {
        $objectRegexp = $this->getObjectRegexp();
        preg_match_all('/(' . $objectRegexp . ')/is', $configString, $objectSet, PREG_SET_ORDER);
        $json = array();
        $objectsCount = count($objectSet);
        $toIgnoreCounter = -1;
        foreach ($objectSet as $i => $object) {
            $pos = strpos($configString, $object[0]);
            $toIgnore = trim(substr($configString, 0, $pos));
            if ($toIgnore) {
                $toIgnoreCounter++;
                if ($toIgnoreCounter == 0) {
                    $bracketPosition = strpos($toIgnore, '{');
                    if ($bracketPosition !== false) {
                        $toIgnore = explode('{', $toIgnore, 2);
                    }
                }
                $this->_parseInputIgnore((array)$toIgnore, $json, $autoCorrectionWarnings);
                $configString = substr($configString, $pos, strlen($configString));
            }
            $configString = str_replace($object[0], '', $configString);
            $objectName = isset($object['object_name']) ? $object['object_name'] : null;
            $objectSeparator = isset($object['object_separator']) ? $object['object_separator'] : null;
            $isLastObject = ( $i == $objectsCount - 1 );
            if (!$isLastObject && $objectSeparator != ',') {
                $autoCorrectionWarnings[] = 'JSON: missing object separator (comma)';
            } else if ($isLastObject && $objectSeparator == ',') {
                $autoCorrectionWarnings[] = 'JSON: no trailing object separator (comma) allowed';
            }
            $jsonObject = $this->_parseInputParseJsonObject(
                $object,
                $autoCorrectionWarnings,
                $missingEnquoteOfPropertyName
            );
            if ($objectName) {
                $json[trim($objectName, '"')] = $jsonObject;
            } elseif (isset($jsonObject['code'])) {
                $code = $jsonObject['code'];
                unset($jsonObject['code']);
                $json[$code] = $jsonObject;
            } else {
                $json[] = $jsonObject;
            }
        }
        return $json;
    }

    protected function _parseInputParseComments(&$configString, &$autoCorrectionWarnings)
    {
        if (preg_match_all('/((?:#+[^{\\n]*\\s+)+)\\s*{/s', $configString, $result, PREG_SET_ORDER)) {
            $autoCorrectionWarnings[] = 'JSON: usage of incompatible comments';
            foreach ($result as $set) {
                $commentLines = explode("\n", $set[1]);
                foreach ($commentLines as $i => $line) {
                    $commentLines[$i] = preg_replace('/^#+\\s/', '', $line);
                }
                $comment = trim(implode("\n", $commentLines));
                $configString = str_replace(
                    $set[0],
                    '{"about": "' . str_replace('"', '\\"', $comment) . '",',
                    $configString
                );
            }
        }
    }

    protected function _parseInput($autoCorrection)
    {
        $configString = $this->_parseInputPrepareInput($this->_input);
        $this->debug('parse config (auto correction = ' . self::esc(self::toString($autoCorrection)) . ')');
        $config = null;
        $lastJsonError = null;
        try {
            $config = self::json_decode($configString);
        } catch (Exception $e) {
            $lastJsonError = $e;
        }
        if (!$this->_parseInputIsValidConfig($config)) {
            $config = null;
        }
        $autoCorrectionWarnings = array();
        $missingEnquoteOfPropertyName = array();
        if ($autoCorrection && !$config && $configString != '[]') {
            $this->_parseInputParseComments($configString, $autoCorrectionWarnings);
            $json = $this->_parseInputParseJsonObjectSet(
                $configString,
                $autoCorrectionWarnings,
                $missingEnquoteOfPropertyName
            );
            $toIgnore = trim($configString);
            if ($toIgnore) {
                $bracketPosition = strpos($toIgnore, '}');
                if ($bracketPosition !== false) {
                    $toIgnore = explode('}', $toIgnore, 2);
                }
                $this->_parseInputIgnore((array)$toIgnore, $json, $autoCorrectionWarnings);
            }
            $configString = $this->jsonEncode($json);
            $configString = str_replace(array("\n"), array("\\n"), $configString);

            $lastJsonError = null;
            try {
                $config = self::json_decode($configString);
            } catch (Exception $e) {
                $lastJsonError = $e;
            }
        }
        if ($lastJsonError) {
            $autoCorrectionWarnings[] = 'JSON: unable to parse config (' . $lastJsonError->getMessage() . ')';
        }

        $row = null;
        $autoCorrectionWarnings = array_unique($autoCorrectionWarnings);
        foreach ($autoCorrectionWarnings as $key => $warning) {
            if ($key == 'missing_enquote_of_property_name') {
                $missingEnquoteOfPropertyName = array_unique($missingEnquoteOfPropertyName);
                $warning = str_replace(
                    '%s',
                    '<span class=osh-key>'
                        . self::esc(implode('</span>, <span class=osh-key>', $missingEnquoteOfPropertyName))
                        . '</span>',
                    $warning
                );
            }
            $this->addMessage('warning', $row, null, $warning);
        }
        $config = (array)$config;

        $this->_parseInputAddRows($config, $autoCorrection);
    }

    protected function _parseInputAddRowsDetectDeprecatedProperties($autoCorrection, &$object, &$deprecatedProperties)
    {
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
    }

    protected function _parseInputAddRowsPrepareRow(
        $autoCorrection,
        $object,
        &$deprecatedProperties,
        &$unknownProperties
    )
    {
        $this->_parseInputAddRowsDetectDeprecatedProperties($autoCorrection, $object, $deprecatedProperties);

        $reservedKeys = array('*id');
        $availableKeys = array(
            'type', 'about', 'label', 'enabled', 'description', 'fees', 'conditions',
            'shipto', 'billto', 'origin', 'customer_groups', 'tracking_url',
        );
        if ($autoCorrection) {
            $availableKeys = array_merge(
                $availableKeys,
                array('destination', 'code')
            );
        }

        $row = array();
        $i = 1;
        foreach ($object as $propertyName => $propertyValue) {
            if (in_array($propertyName, $reservedKeys)) {
                continue;
            }
            if (in_array($propertyName, $availableKeys)
                || substr($propertyName, 0, 1) == '_'
                || in_array($object['type'], array('data', 'meta'))
            ) {
                if (isset($propertyValue)) {
                    $row[$propertyName] = array('value' => $propertyValue, 'original_value' => $propertyValue);
                    if ($autoCorrection) $this->cleanProperty($row, $propertyName);
                }
            } else {
                if (!in_array($propertyName, $unknownProperties)) $unknownProperties[] = $propertyName;
            }
            $i++;
        }
        return $row;
    }

    protected function _parseInputAddRows($config, $autoCorrection)
    {
        $this->_config = array();

        $deprecatedProperties = array();
        $unknownProperties = array();

        foreach ($config as $code => $object) {
            $row = $this->_parseInputAddRowsPrepareRow(
                $autoCorrection,
                (array)$object,
                $deprecatedProperties,
                $unknownProperties
            );
            $this->addRow($code, $row);
        }
        $row = null;
        if (count($unknownProperties)>0) {
            $this->addMessage(
                'error',
                $row,
                null,
                'Usage of unknown properties %s',
                ': <span class=osh-key>' . implode('</span>, <span class=osh-key>', $unknownProperties) . '</span>'
            );
        }
        if (count($deprecatedProperties)>0) {
            $this->addMessage(
                'warning',
                $row,
                null,
                'Usage of deprecated properties %s',
                ': <span class=osh-key>' . implode('</span>, <span class=osh-key>', $deprecatedProperties) . '</span>'
            );
        }
    }

    public function addRow($code, &$row)
    {
        if ($code) {
            if (isset($this->_config[$code])) {
                $this->addMessage('error', $row, 'code', 'The id must be unique, `%s` has been found twice', $code);
            }
            while (isset($this->_config[$code])) {
                $code .= rand(0, 9);
            }
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
        $message = array_shift($args);
        $message = Mage::getModel('owebia_shipping2/Os2_Message')
            ->setType($type)
            ->setMessage($message)
            ->setArgs($args);
        if (isset($row)) {
            if (isset($property)) {
                $row[$property]['messages'][] = $message;
            } else {
                $row['*messages'][] = $message;
            }
        }
        $this->_messages[] = $message;
        $this->debug('   => <span class=osh-' . $message->type . '>' . self::esc((string)$message) . '</span>');
    }

    protected function _replaceVariable(&$process, $input, $original, $replacement)
    {
        if (mb_strpos($input, '{' . $original . '}') !== false) {
            $input = $this->replace('{' . $original . '}', $this->_autoEscapeStrings($replacement), $input);
        }
        if (mb_strpos($input, $original) !== false) {
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
        $keys = ($keys ? $keys . '|' : '') . implode('|', array_keys($process['data']));
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
        $addressFilter = $this->_replaceData($process, $addressFilter);
        $addressFilterParser = Mage::getModel('owebia_shipping2/AddressFilterParser', $this);
        $addressFilter = $addressFilterParser->parse($addressFilter);

        $this->debug('      address filter = <span class=osh-formula>' . self::esc($addressFilter) . '</span>');
        $data = array(
            '{c}' => $address->getData('country_id'),
            '{p}' => $address->getData('postcode'),
            '{r}' => $address->getData('region_code'),
        );
        foreach ($data as $original => $replacement) {
            $addressFilter = $this->_replaceVariable($process, $addressFilter, $original, $replacement);
        }
        return (bool)$this->_evalFormula($addressFilter, $row, $propertyName, $isChecking = false);
    }

    protected function _getItemProperty($item, $propertyName)
    {
        $elems = explode('.', $propertyName, $limit = 2);
        switch ($elems[0]) {
            case 'p':
            case 'product':
                return $item->getProduct()->getData($elems[1]);
            case 'item':
                return $item->getData($elems[1]);
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
            return 'array(' . join(',', $items) . ')';
        } else {
            return isset($input) && (is_string($input))
                ? self::escapeString($input) : self::toString($input);
        }
    }

    protected function _processProductGetConditions($operation, $regexpResult)
    {
        if ($operation == 'sum'
            || $operation == 'min'
            || $operation == 'max'
            || $operation == 'count distinct'
        ) {
            return isset($regexpResult[3]) ? $regexpResult[3] : null;
        } elseif ($operation == 'count') {
            return isset($regexpResult[2]) ? $regexpResult[2] : null;
        }
        return null;
    }

    protected function _processProductGetReference($operation, $regexpResult)
    {
        if ($operation == 'sum'
            || $operation == 'min'
            || $operation == 'max'
            || $operation == 'count distinct'
        ) {
            return $regexpResult[2];
        } elseif ($operation == 'count') {
            return 'items';
        }
        return null;
    }

    protected function _processProductGetInitialReturnValue($operation)
    {
        if ($operation == 'sum'
            || $operation == 'count distinct'
            || $operation == 'count'
        ) {
            return 0;
        }
        return null;
    }

    protected function _processProductGetReturnValue($operation, $returnValue, $value, $item, &$distinctValues)
    {
        switch ($operation) {
            case 'min':
                if (!isset($returnValue) || $value < $returnValue) {
                    $returnValue = $value;
                }
                break;
            case 'max':
                if (!isset($returnValue) || $value > $returnValue) {
                    $returnValue = $value;
                }
                break;
            case 'sum':
                $returnValue = (isset($returnValue) ? $returnValue : 0) + $value * $item->qty;
                break;
            case 'count distinct':
                if (!isset($returnValue)) {
                    $returnValue = 0;
                }
                if (!in_array($value, $distinctValues)) {
                    $distinctValues[] = $value;
                    $returnValue++;
                }
                break;
        }
        return $returnValue;
    }

    protected function _processProduct($items, $regexpResult, &$row, $propertyName, $isChecking)
    {
        // count, sum, min, max, count distinct
        $operation = strtolower($regexpResult[1]);
        $returnValue = $this->_processProductGetInitialReturnValue($operation);
        $reference = $this->_processProductGetReference($operation, $regexpResult);
        $conditions = $this->_processProductGetConditions($operation, $regexpResult);

        $this->debug(
            '      <span class=osh-loop>start <span class=osh-replacement>' . self::esc($operation) . '</span> '
            . '<span class=osh-key>' . self::esc($reference) . '</span>'
            . (isset($conditions)
                ? ' where <span class=osh-replacement>' . self::esc($conditions) . '</span></span>' : '')
        );
        $this->addDebugIndent();

        $properties = array();
        $this->_preg_match_all('#(?:item|product|p)\.([a-z0-9_\+\-\.]+)#i', $conditions, $propertiesRegexpResult);
        foreach ($propertiesRegexpResult as $propertyRegexpResult) {
            if (!isset($properties[$propertyRegexpResult[0]])) {
                $properties[$propertyRegexpResult[0]] = $propertyRegexpResult;
            }
        }
        krsort($properties); // To avoid shorter replace

        if ($items) {
            $distinctValues = array();
            foreach ($items as $item) {
                $this->debug(
                    '     <span class=osh-loop>&bull; item</span>'
                    . ' = <span class=osh-formula>' . self::esc((string)$item) . '</span>'
                );
                $this->addDebugIndent();
                if (isset($conditions) && $conditions != '') {
                    $formula = $conditions;
                    foreach ($properties as $property) {
                        $value = $this->_getItemProperty($item, $property[0]);
                        $from = $property[0];
                        $to = $this->_autoEscapeStrings($value);
                        $this->debug(
                            '      replace <span class=osh-replacement>' . self::esc($from) . '</span>'
                            . ' by <span class=osh-replacement>' . self::esc($to) . '</span>'
                            . ' =&gt; <span class=osh-formula>' . self::esc(
                                str_replace($from, '<span class=osh-replacement>' . $to . '</span>', $formula)
                            ) . '</span>'
                        );
                        $formula = str_replace($from, $to, $formula);
                    }
                    $evalResult = $this->_evalFormula($formula, $row, $propertyName, $isChecking);
                    if (!isset($evalResult)) $returnValue = 'null';
                }
                else $evalResult = true;

                if ($evalResult == true) {
                    if ($operation == 'count') {
                        $returnValue = (isset($returnValue) ? $returnValue : 0) + $item->qty;
                    } else {
                        $value = $this->_getItemProperty($item, $reference);
                        $this->debug(
                            '    &raquo; <span class=osh-key>' . self::esc($reference) . '</span>'
                            . ' = <span class=osh-formula>' . self::esc($value) . '</span>'
                            . ($operation == 'sum' ? ' x <span class=osh-formula>' . $item->qty . '</span>' : '')
                        );
                        $returnValue = $this->_processProductGetReturnValue(
                            $operation,
                            $returnValue,
                            $value,
                            $item,
                            $distinctValues
                        );
                    }
                }
                $this->debug(
                    '    &raquo; <span class=osh-info>' . self::esc($operation) . ' result</span>'
                    . ' = <span class=osh-formula>' . self::esc($returnValue) . '</span>'
                );
                $this->removeDebugIndent();
            }
        }

        $this->removeDebugIndent();
        $this->debug('      <span class=osh-loop>end</span>');

        return $returnValue;
    }

    protected static function getType($variable)
    {
        return gettype($variable);
    }

    protected function cleanPropertyReplaceDeprecatedCustomVarSyntax($context)
    {
        $input = &$context->input;
        while (preg_match('/{{customVar code=([a-zA-Z0-9_-]+)}}/', $input, $resi)) {
            $input = $this->replace(
                $resi[0],
                '{customvar.' . $resi[1] . '}',
                $input,
                'warning',
                'replace deprecated'
            );
        }
        return $input;
    }

    protected function cleanPropertyReplaceDeprecatedVariables($context)
    {
        $input = &$context->input;
        $regex = "{(weight|products_quantity|price_including_tax|price_excluding_tax|country)}";
        if (preg_match('/' . $regex . '/', $input, $resi)) {
            $this->addMessage(
                'warning',
                $context->row,
                $context->key,
                'Usage of deprecated syntax %s',
                '<span class=osh-formula>' . $resi[0] . '</span>'
            );
            while (preg_match('/' . $regex . '/', $input, $resi)) {
                switch ($resi[1]) {
                    case 'price_including_tax':
                        $to = "{cart.price+tax+discount}";
                        break;
                    case 'price_excluding_tax':
                        $to = "{cart.price-tax+discount}";
                        break;
                    case 'weight':
                        $to = "{cart.{$resi[1]}}";
                        break;
                    case 'products_quantity':
                        $to = "{cart.qty}";
                        break;
                    case 'country':
                        $to = "{shipto.country_name}";
                        break;
                }
                $input = str_replace($resi[0], $to, $input);
            }
        }
    }

    protected function cleanPropertyReplaceDeprecatedCopySyntax($context)
    {
        $input = &$context->input;
        $copyRegexp = "{copy '([a-zA-Z0-9_]+)'\.'([a-zA-Z0-9_]+)'}";
        if (preg_match('/' . $copyRegexp . '/', $input, $resi)) {
            $this->addMessage(
                'warning',
                $context->row,
                $context->key,
                'Usage of deprecated syntax %s',
                '<span class=osh-formula>' . $resi[0] . '</span>'
            );
            while (preg_match('/' . $copyRegexp . '/', $input, $resi)) {
                $input = str_replace($resi[0], '{' . $resi[1] . '.' . $resi[2] . '}', $input);
            }
        }
    }

    protected function cleanPropertyReplaceDeprecatedFunctionsSyntax($context)
    {
        $input = &$context->input;
        $countAllAnyRegexp = "{(count|all|any) (attribute|option) '([^'\)]+)'"
            . " ?((?:==|<=|>=|<|>|!=) ?(?:" . self::FLOAT_REGEX . "|true|false|'[^'\)]*'))}";
        $sumRegexp = "{(sum) (attribute|option) '([^'\)]+)'}";
        if (preg_match('/' . $countAllAnyRegexp . '/', $input, $resi)
            || preg_match('/' . $sumRegexp . '/', $input, $resi)
        ) {
            $this->addMessage(
                'warning',
                $context->row,
                $context->key,
                'Usage of deprecated syntax %s',
                '<span class=osh-formula>' . $resi[0] . '</span>'
            );
            while (preg_match('/' . $countAllAnyRegexp . '/', $input, $resi)
                || preg_match('/' . $sumRegexp . '/', $input, $resi)
            ) {
                switch ($resi[1]) {
                    case 'count':
                        $to = "{count items where product.{$resi[2]}.{$resi[3]}{$resi[4]}}";
                        break;
                    case 'all':
                        $to = "{count items where product.{$resi[2]}.{$resi[3]}{$resi[4]}}=={cart.qty}";
                        break;
                    case 'any':
                        $to = "{count items where product.{$resi[2]}.{$resi[3]}{$resi[4]}}>0";
                        break;
                    case 'sum':
                        $to = "{sum product.{$resi[2]}.{$resi[3]}}";
                        break;
                }
                $input = str_replace($resi[0], $to, $input);
            }
        }
    }

    protected function cleanPropertyReplaceDeprecatedProductSyntax($context)
    {
        $input = &$context->input;
        $regex = "((?:{| )product.(?:attribute|option))s.";
        if (preg_match('/' . $regex . '/', $input, $resi)) {
            $this->addMessage(
                'warning',
                $context->row,
                $context->key,
                'Usage of deprecated syntax %s',
                '<span class=osh-formula>' . $resi[0] . '</span>'
            );
            while (preg_match('/' . $regex . '/', $input, $resi)) {
                $input = str_replace($resi[0], $resi[1] . '.', $input);
            }
        }
    }

    protected function cleanPropertyReplaceDeprecatedTableSyntax($context)
    {
        $input = &$context->input;
        $regex = "{table '([^']+)' (" . self::COUPLE_REGEX . "(?:, *" . self::COUPLE_REGEX . ")*)}";
        if (preg_match('/' . $regex . '/', $input, $resi)) {
            $this->addMessage(
                'warning',
                $context->row,
                $context->key,
                'Usage of deprecated syntax %s',
                '<span class=osh-formula>' . $resi[0] . '</span>'
            );
            while (preg_match('/' . $regex . '/', $input, $resi)) {
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
    }

    /* For auto correction */
    public function cleanProperty(&$row, $key)
    {
        $input = $row[$key]['value'];
        if (is_string($input)) {
            $context = new stdClass();
            $context->row = &$row;
            $context->key = &$key;
            $context->input = &$input;
            $this->cleanPropertyReplaceDeprecatedCustomVarSyntax($context);
            $this->cleanPropertyReplaceDeprecatedVariables($context);
            $this->cleanPropertyReplaceDeprecatedCopySyntax($context);
            $this->cleanPropertyReplaceDeprecatedFunctionsSyntax($context);
            $this->cleanPropertyReplaceDeprecatedProductSyntax($context);
            $this->cleanPropertyReplaceDeprecatedTableSyntax($context);

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
                if (mb_strpos($input, $from) !== false) {
                    $input = $this->replace($from, $to, $input, 'warning', 'replace deprecated');
                }
            }
        }
        $row[$key]['value'] = $input;
    }
}
