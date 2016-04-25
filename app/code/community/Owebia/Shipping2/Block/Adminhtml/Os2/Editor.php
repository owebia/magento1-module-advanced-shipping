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

class Owebia_Shipping2_Block_Adminhtml_Os2_Editor extends Mage_Adminhtml_Block_Abstract
{
    protected $_config;
    protected $_opened_row_ids;

    public function __construct($attributes)
    {
        $attributes = $attributes + array(
            'config' => '',
            'opened_row_ids' => array(),
        );
        $this->_config = $attributes['config'];
        $this->_opened_row_ids = $attributes['opened_row_ids'];
    }

    public function __()
    {
        $args = func_get_args();
        return Mage::helper('owebia_shipping2')->__($args);
    }

    private function _getPropertyInput($property_name, $property)
    {
        if (is_array($property)) { // Compatibility PHP 5.2
            $value = isset($property['original_value']) ? $property['original_value'] : (isset($property['value']) ? $property['value'] : (isset($property) ? $property : ''));
        } else {
            $value = $property;
        }

        $toolbar = "<span class=\"os2-field-btn os2-field-help\" data-property=\"{$property_name}\"></span>";
        switch ($property_name) {
            case 'enabled':
                $enabled = $value!==false;
                $input = "<select class=field name=\"{$property_name}\">"
                        ."<option value=\"1\"".($enabled ? ' selected="selected"' : '').">".$this->__('Enabled (default)')."</option>"
                        ."<option value=\"0\"".($enabled ? '' : ' selected="selected"').">".$this->__('Disabled')."</option>"
                    ."</select>";
                break;
            case 'type':
                $input = "<select class=field name=\"{$property_name}\">"
                        ."<option value=method".($value=='method' || !$value ? '' : ' selected="selected"').">".$this->__('Shipping Method (default)')."</option>"
                        ."<option value=data".($value=='data' ? ' selected="selected"' : '').">".$this->__('Data')."</option>"
                        ."<option value=meta".($value=='meta' ? ' selected="selected"' : '').">".$this->__('Meta')."</option>"
                    ."</select>";
                break;
            case 'shipto':
            case 'billto':
            case 'origin':
                $toolbar = "<span class=\"os2-field-btn os2-field-edit\"></span>".$toolbar;
            default:
                $input = "<input class=field name=\"{$property_name}\" value=\"".htmlspecialchars($value, ENT_COMPAT, 'UTF-8')."\"/>";
                break;
        }
        return $input;
    }
    
    public function getPropertyTools($controller, $property_name)
    {
        $after = '';
        switch ($property_name) {
            case 'label':
            case 'description':
                $after = "<fieldset class=buttons-set><legend>".$this->__('Insert')."</legend>"
                    ."<p>"
                        .$controller->button__('Shipping country',"os2editor.insertAtCaret(this,'{shipto.country_name}');",'os2-insert')
                        .$controller->button__('Cart weight',"os2editor.insertAtCaret(this,'{cart.weight}');",'os2-insert')
                        .$controller->button__('Products quantity',"os2editor.insertAtCaret(this,'{cart.qty}');",'os2-insert')
                        .$controller->button__('Price incl. tax',"os2editor.insertAtCaret(this,'{cart.price+tax+discount}');",'os2-insert')
                        .$controller->button__('Price excl. tax',"os2editor.insertAtCaret(this,'{cart.price-tax+discount}');",'os2-insert')
                    ."</p>"
                    ."</fieldset>";
                break;
            case 'fees':
                $after = "<fieldset class=buttons-set><legend>".$this->__('Insert')."</legend>"
                    ."<p>"
                        .$controller->button__('Weight',"os2editor.insertAtCaret(this,'{cart.weight}');",'os2-insert')
                        .$controller->button__('Products quantity',"os2editor.insertAtCaret(this,'{cart.qty}');",'os2-insert')
                        .$controller->button__('Price incl. tax',"os2editor.insertAtCaret(this,'{cart.price+tax+discount}');",'os2-insert')
                        .$controller->button__('Price excl. tax',"os2editor.insertAtCaret(this,'{cart.price-tax+discount}');",'os2-insert')
                    ."</p>"
                    ."</fieldset>";
                break;
            case 'conditions':
                $after = "<fieldset class=buttons-set><legend>".$this->__('Insert')."</legend>"
                    ."<p>"
                        .$controller->button__('Weight',"os2editor.insertAtCaret(this,'{cart.weight}');",'os2-insert')
                        .$controller->button__('Products quantity',"os2editor.insertAtCaret(this,'{cart.qty}');",'os2-insert')
                        .$controller->button__('Price incl. tax',"os2editor.insertAtCaret(this,'{cart.price+tax+discount}');",'os2-insert')
                        .$controller->button__('Price excl. tax',"os2editor.insertAtCaret(this,'{cart.price-tax+discount}');",'os2-insert')
                    ."</p>"
                    ."</fieldset>";
                break;
            case 'customer_groups':
                $model = Mage::getModel('owebia_shipping2/Os2_Data_CustomerGroup');
                $groups = (array)$model->getCollection();
                $output = '';
                foreach ($groups as $id => $name) {
                    $output .= $controller->button($this->esc($name.' ('.$id.')'),"os2editor.insertAtCaret(this,'".$this->jsEscape($id)."');",'os2-insert');
                }
                $after = "<fieldset class=buttons-set><legend>".$this->__('Tools')."</legend>"
                    ."<p>"
                        .$controller->button__('Human readable version',"os2editor.getReadableSelection(this);")
                    ."</p><div id=os2-output></div>"
                    ."</fieldset>"
                    ."<fieldset class=buttons-set><legend>".$this->__('Insert')."</legend>"
                    ."<p>{$output}</p>"
                    ."</fieldset>"
                ;
                break;
            case 'tracking_url':
                $after = "<fieldset class=buttons-set><legend>".$this->__('Insert')."</legend>"
                    ."<p>"
                        .$controller->button__('Tracking number',"os2editor.insertAtCaret(this,'{tracking_number}');",'os2-insert')
                    ."</p>"
                    ."</fieldset>";
                break;
            case 'shipto':
            case 'billto':
            case 'origin':
                $after = "<fieldset class=buttons-set><legend>".$this->__('Tools')."</legend>"
                    ."<p>"
                        .$controller->button__('Human readable version',"os2editor.getReadableSelection(this);")
                    ."</p><div id=os2-output></div>"
                    ."</fieldset>"
                ;
                break;
            case 'about':
                break;
        }
        return $after;
    }

    public function sortProperties($k1, $k2)
    {
        $i1 = isset($this->properties_sort[$k1]) ? $this->properties_sort[$k1] : 1000;
        $i2 = isset($this->properties_sort[$k2]) ? $this->properties_sort[$k2] : 1000;
        return $i1==$i2 ? strcmp($k1, $k2) : $i1-$i2;
    }

    protected function _getRowUI(&$row)
    {
        $properties = array('*id', 'type', 'about', 'enabled');
        $type = isset($row['type']['value']) ? $row['type']['value'] : null;
        switch ($type) {
            case 'meta':
                $row_label = $this->__('[meta] %s', $row['*id']);
                break;
            case 'data':
                $row_label = $this->__('[data] %s', $row['*id']);
                break;
            default:
                if (!isset($row['label'])) {
                    $row['label']['value'] = $this->__('New shipping method');
                }
                $row_label = $row['label']['value'];
                $properties = array_merge($properties, array('label', 'description', 'shipto', 'billto', 'origin', 'conditions', 'fees', 'customer_groups', 'tracking_url'));
        }

        $properties_label = array(
            '*id' => 'ID',
            'type' => 'Type',
            'about' => 'About',
            'enabled' => 'Enabled',
            'label' => 'Label',
            'description' => 'Description',
            'shipto' => 'Shipping address',
            'billto' => 'Billing address',
            'origin' => 'Origin address',
            'conditions' => 'Conditions',
            'fees' => 'Fees',
            'customer_groups' => 'Customer groups',
            'tracking_url' => 'Tracking url',
        );
        foreach ($properties as $property_name) {
            if (!isset($row[$property_name])) $row[$property_name] = null;
        }
        $this->properties_sort = array_flip($properties);
        uksort($row, array($this, 'sortProperties'));
        $list = '';
        $content = '';
        $j = 0;
        foreach ($row as $property_name => $property) {
            $property_label = isset($properties_label[$property_name]) ? $properties_label[$property_name] : $property_name;
            $error = array();
            if (isset($property['messages'])) {
                foreach ($property['messages'] as $message) {
                    $error[] = $this->__($message);
                }
            }
            $content .= "<tr class=\"os2-p-container".($error ? ' os2-error' : '')."\"".($error ? ' title="'.$this->esc(implode(', ', $error)).'"' : '')."><th>".$this->__($property_label)."</th><td>".$this->_getPropertyInput($property_name, $property, $big = false)."</td></tr>";
            $j++;
        }
        //$output = "<ul class=\"properties-list ui-layout-west\">{$list}</ul><div class=\"properties-container ui-layout-center\">{$content}</div>";
        $output = "<table class=properties-container>{$content}</table>";
        return $output;
    }

    protected function _getRowItem($row, $opened)
    {
        $type = isset($row['type']['value']) ? $row['type']['value'] : null;
        switch ($type) {
            case 'meta':
                $label = $this->__('[meta] %s', $row['*id']);
                break;
            case 'data':
                $label = $this->__('[data] %s', $row['*id']);
                break;
            default:
                $label = isset($row['label']['value']) ? $row['label']['value'] : $this->__('New shipping method');
                break;
        }
        $content = '';
        if ($opened) {
            $content = $this->_getRowUI($row);
        }
        $error = false;
        foreach ($row as $property_name => $property) {
            if (is_array($property) /*Compatibility*/ && isset($property['messages'])) {
                $error = true;
                break;
            }
        }
        return "<li data-id=\"{$row['*id']}\"".($error ? ' class=os2-error' : '')."><h5><button class=\"os2-remove-row-btn\" title=\"{$this->__('Remove')}\"></button>".$label."</h5><div class=\"row-ui".($opened ? ' opened' : '')."\">{$content}</div></li>";
    }

    protected function esc($input)
    {
        return htmlspecialchars($input, ENT_COMPAT, 'UTF-8');
    }

    protected function jsEscape($input)
    {
        return str_replace(array("\r\n","\r","\n","'"),array("\\n","\\n","\\n","\\'"),$input);
    }

    public function getRowUI(&$row)
    {
        return $this->_getRowUI($row);
    }

    public function getHtml()
    {
        $config = $this->getData('config');
        $opened_row_ids = $this->getData('opened_row_ids');
        $output = /*"<pre>".print_r($config, true)."</pre>".*/"";
        $i = 0;
        if (!$config) {
            $output .= "<p style=\"padding:10px;\">Configuration vide</p>";
        } else {
            $output .= "<ul id=os2-editor-elems-container>";
            foreach ($config as $row_id => &$row) {
                $opened = in_array($row_id, $opened_row_ids) || !$opened_row_ids && $i==0;
                $output .= $this->_getRowItem($row, $opened);
                $i++;
            }
            $output .= "</ul>";
        }
        return $output;
    }
}
