<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Block_Adminhtml_Os2_Editor extends Mage_Adminhtml_Block_Abstract
{
    protected $_config;
    protected $_openedRowIds;

    public function __construct($attributes)
    {
        $attributes = $attributes + array(
            'config' => '',
            'opened_row_ids' => array(),
        );
        $this->_config = $attributes['config'];
        $this->_openedRowIds = $attributes['opened_row_ids'];
    }

    public function __()
    {
        $args = func_get_args();
        return Mage::helper('owebia_shipping2')->__($args);
    }

    protected function _getPropertyInput($propertyName, $property)
    {
        if (is_array($property)) { // Compatibility PHP 5.2
            $value = isset($property['original_value'])
                ? $property['original_value']
                : (isset($property['value']) ? $property['value'] : (isset($property) ? $property : ''));
        } else {
            $value = $property;
        }

        $toolbar = "<span class=\"os2-field-btn os2-field-help\" data-property=\"{$propertyName}\"></span>";
        switch ($propertyName) {
            case 'enabled':
                $enabled = $value !== false;
                $input = "<select class=field name=\"{$propertyName}\">"
                        . "<option value=\"1\"" . ($enabled ? ' selected="selected"' : '') . ">"
                            . $this->__('Enabled (default)') . "</option>"
                        . "<option value=\"0\"" . ($enabled ? '' : ' selected="selected"') . ">"
                            . $this->__('Disabled') . "</option>"
                    . "</select>";
                break;
            case 'type':
                $input = "<select class=field name=\"{$propertyName}\">"
                        . "<option value=method" . ($value=='method' || !$value ? '' : ' selected="selected"') . ">"
                            . $this->__('Shipping Method (default)') . "</option>"
                        . "<option value=data" . ($value=='data' ? ' selected="selected"' : '') . ">"
                            . $this->__('Data') . "</option>"
                        . "<option value=meta" . ($value=='meta' ? ' selected="selected"' : '') . ">"
                            . $this->__('Meta') . "</option>"
                    . "</select>";
                break;
            case 'shipto':
            case 'billto':
            case 'origin':
                $toolbar = "<span class=\"os2-field-btn os2-field-edit\"></span>" . $toolbar;
            default:
                $input = "<input class=field name=\"{$propertyName}\""
                    . " value=\"" . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . "\"/>";
                break;
        }
        return $input;
    }

    public function insertBtn($controller, $title, $variable)
    {
        return $controller->button__(
            $title,
            "os2editor.insertAtCaret(this,'$variable');",
            'os2-insert'
        );
    }

    public function getPropertyTools($controller, $propertyName)
    {
        $after = '';
        if ($propertyName == 'label' || $propertyName == 'description') {
            $after = "<fieldset class=buttons-set><legend>" . $this->__('Insert') . "</legend>"
                . "<p>"
                    . $this->insertBtn($controller, 'Shipping country', '{shipto.country_name}')
                    . $this->insertBtn($controller, 'Cart weight', '{cart.weight}')
                    . $this->insertBtn($controller, 'Products quantity', '{cart.qty}')
                    . $this->insertBtn($controller, 'Price incl. tax', '{cart.price+tax+discount}')
                    . $this->insertBtn($controller, 'Price excl. tax', '{cart.price-tax+discount}')
                . "</p>"
                . "</fieldset>";
        } elseif ($propertyName == 'fees') {
            $after = "<fieldset class=buttons-set><legend>" . $this->__('Insert') . "</legend>"
                . "<p>"
                    . $this->insertBtn($controller, 'Weight', '{cart.weight}')
                    . $this->insertBtn($controller, 'Products quantity', '{cart.qty}')
                    . $this->insertBtn($controller, 'Price incl. tax', '{cart.price+tax+discount}')
                    . $this->insertBtn($controller, 'Price excl. tax', '{cart.price-tax+discount}')
                . "</p>"
                . "</fieldset>";
        } elseif ($propertyName == 'conditions') {
            $after = "<fieldset class=buttons-set><legend>" . $this->__('Insert') . "</legend>"
                . "<p>"
                    . $this->insertBtn($controller, 'Weight', '{cart.weight}')
                    . $this->insertBtn($controller, 'Products quantity', '{cart.qty}')
                    . $this->insertBtn($controller, 'Price incl. tax', '{cart.price+tax+discount}')
                    . $this->insertBtn($controller, 'Price excl. tax', '{cart.price-tax+discount}')
                . "</p>"
                . "</fieldset>";
        } elseif ($propertyName == 'customer_groups') {
            $model = Mage::getModel('owebia_shipping2/Os2_Data_CustomerGroup');
            $groups = (array)$model->getCollection();
            $output = '';
            foreach ($groups as $id => $name) {
                $output .= $this->insertBtn(
                    $controller,
                    $this->esc($name . ' (' . $id . ')'),
                    $this->jsEscape($id)
                );
            }
            $after = "<fieldset class=buttons-set><legend>" . $this->__('Tools') . "</legend>"
                . "<p>"
                    . $controller->button__('Human readable version', "os2editor.getReadableSelection(this);")
                . "</p><div id=os2-output></div>"
                . "</fieldset>"
                . "<fieldset class=buttons-set><legend>" . $this->__('Insert') . "</legend>"
                . "<p>{$output}</p>"
                . "</fieldset>"
            ;
        } elseif ($propertyName == 'tracking_url') {
            $after = "<fieldset class=buttons-set><legend>" . $this->__('Insert') . "</legend>"
                . "<p>"
                    . $this->insertBtn($controller, 'Tracking number', '{tracking_number}')
                . "</p>"
                . "</fieldset>";
        } elseif ($propertyName == 'shipto' ||$propertyName == 'billto' || $propertyName == 'origin') {
            $after = "<fieldset class=buttons-set><legend>" . $this->__('Tools') . "</legend>"
                . "<p>"
                    . $controller->button__('Human readable version', "os2editor.getReadableSelection(this);")
                . "</p><div id=os2-output></div>"
                . "</fieldset>"
            ;
        } elseif ($propertyName == 'about') {
            $after = '';
        }
        return $after;
    }

    public function sortProperties($firstKey, $secondKey)
    {
        $firstKeyPosition = isset($this->propertiesSort[$firstKey]) ? $this->propertiesSort[$firstKey] : 1000;
        $secondKeyPosition = isset($this->propertiesSort[$secondKey]) ? $this->propertiesSort[$secondKey] : 1000;
        return $firstKeyPosition == $secondKeyPosition
            ? strcmp($firstKey, $secondKey) : $firstKeyPosition - $secondKeyPosition;
    }

    protected function _getRowUI(&$row)
    {
        $properties = array('*id', 'type', 'about', 'enabled');
        $type = isset($row['type']['value']) ? $row['type']['value'] : null;
        switch ($type) {
            case 'meta':
                $rowLabel = $this->__('[meta] %s', $row['*id']);
                break;
            case 'data':
                $rowLabel = $this->__('[data] %s', $row['*id']);
                break;
            default:
                if (!isset($row['label'])) {
                    $row['label']['value'] = $this->__('New shipping method');
                }
                $rowLabel = $row['label']['value'];
                $properties = array_merge(
                    $properties,
                    array(
                        'label', 'description', 'shipto', 'billto', 'origin',
                        'conditions', 'fees', 'customer_groups', 'tracking_url',
                    )
                );
        }

        $propertiesLabel = array(
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
        foreach ($properties as $propertyName) {
            if (!isset($row[$propertyName])) $row[$propertyName] = null;
        }
        $this->propertiesSort = array_flip($properties);
        uksort($row, array($this, 'sortProperties'));
        $list = '';
        $content = '';
        $j = 0;
        foreach ($row as $propertyName => $property) {
            $propertyLabel = isset($propertiesLabel[$propertyName]) ? $propertiesLabel[$propertyName] : $propertyName;
            $error = array();
            if (isset($property['messages'])) {
                foreach ($property['messages'] as $message) {
                    $error[] = $this->__($message);
                }
            }
            $content .= "<tr class=\"os2-p-container" . ($error ? ' os2-error' : ''). "\""
                . ($error ? ' title="' . $this->esc(implode(', ', $error)) . '"' : '')
                . "><th>" . $this->__($propertyLabel) . "</th>"
                . "<td>" . $this->_getPropertyInput($propertyName, $property, $big = false) . "</td>"
                . "</tr>";
            $j++;
        }
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
        foreach ($row as $propertyName => $property) {
            if (is_array($property) /*Compatibility*/ && isset($property['messages'])) {
                $error = true;
                break;
            }
        }
        return "<li data-id=\"{$row['*id']}\"" . ($error ? ' class=os2-error' : '') . ">"
            . "<h5><button class=\"os2-remove-row-btn\" title=\"{$this->__('Remove')}\"></button>" . $label . "</h5>"
            . "<div class=\"row-ui" . ($opened ? ' opened' : '') . "\">{$content}</div></li>";
    }

    protected function esc($input)
    {
        return htmlspecialchars($input, ENT_COMPAT, 'UTF-8');
    }

    protected function jsEscape($input)
    {
        return str_replace(array("\r\n", "\r", "\n", "'"), array("\\n", "\\n", "\\n", "\\'"), $input);
    }

    public function getRowUI(&$row)
    {
        return $this->_getRowUI($row);
    }

    public function getHtml()
    {
        $config = $this->getData('config');
        $openedRowIds = $this->getData('opened_row_ids');
        $output = '';
        $i = 0;
        if (!$config) {
            $output .= "<p style=\"padding:10px;\">Configuration vide</p>";
        } else {
            $output .= "<ul id=os2-editor-elems-container>";
            foreach ($config as $rowId => &$row) {
                $opened = in_array($rowId, $openedRowIds) || !$openedRowIds && $i==0;
                $output .= $this->_getRowItem($row, $opened);
                $i++;
            }
            $output .= "</ul>";
        }
        return $output;
    }
}
