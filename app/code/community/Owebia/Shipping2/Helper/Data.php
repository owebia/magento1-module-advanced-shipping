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

class Owebia_Shipping2_Helper_Data extends Mage_Core_Helper_Data
{
    protected $_translate_inline;

    public function __()
    {
        $args = func_get_args();
        if (isset($args[0]) && is_array($args[0]) && count($args)==1) {
            $args = $args[0];
        }
        $message = array_shift($args);
        if ($message instanceof OS_Message) {
            $args = $message->args;
            $message = $message->message;
        }
        
        $output = parent::__($message);
        
        /*if (true) {
            $translations = @file_get_contents('translations.os2');
            $translations = eval('return '.$translations.';');
            if (!is_array($translations)) $translations = array();

            $file = 'NC';
            $line = 'NC';
            $backtrace = debug_backtrace();
            foreach ($backtrace as $trace) {
                if (!isset($trace['function'])) continue;
                if (substr($trace['function'], strlen($trace['function'])-2, strlen($trace['function']))=='__') {
                    $file = ltrim(str_replace(Mage::getBaseDir(), '', $trace['file']), '/');
                    $line = $trace['line'];
                    continue;
                }
                //$file = ltrim(str_replace(Mage::getBaseDir(), '', $trace['file']), '/');
                //echo $file.', '.$trace['function'].'(), '.$line.', '.$message.'<br/>';
                break;
            }

            $translations[Mage::app()->getLocale()->getLocaleCode()][$file][$message] = $output;
            ksort($translations[Mage::app()->getLocale()->getLocaleCode()]);
            file_put_contents('translations.os2', var_export($translations, true));
        }*/

        if (count($args)==0) {
            $result = $output;
        } else {
            if (!isset($this->_translate_inline)) $this->_translate_inline = Mage::getSingleton('core/translate')->getTranslateInline();
            if ($this->_translate_inline) {
                $parts = explode('}}{{', $output);
                $parts[0] = vsprintf($parts[0], $args);
                $result = implode('}}{{', $parts);
            } else  {
                $result = vsprintf($output, $args);
            }
        }
        return $result;
    }

    public function getMethodText($helper, $process, $row, $property)
    {
        if (!isset($row[$property])) return '';

        $output = '';
        $cart = $process['data']['cart'];
        return $helper->evalInput($process, $row, $property, str_replace(
            array(
                '{cart.weight}',
                '{cart.price-tax+discount}',
                '{cart.price-tax-discount}',
                '{cart.price+tax+discount}',
                '{cart.price+tax-discount}',
            ),
            array(
                $cart->weight . $cart->weight_unit,
                $this->currency($cart->{'price-tax+discount'}),
                $this->currency($cart->{'price-tax-discount'}),
                $this->currency($cart->{'price+tax+discount'}),
                $this->currency($cart->{'price+tax-discount'}),
            ),
            $helper->getRowProperty($row, $property)
        ));
    }
    
    public function getDataModelMap($helper, $carrier_code, $request)
    {
        $mage_config = Mage::getConfig();
        return array(
            'info' => Mage::getModel('owebia_shipping2/Os2_Data_Info', array_merge($helper->getInfos(), array(
                'magento_version' => Mage::getVersion(),
                'module_version' => (string)$mage_config->getNode('modules/Owebia_Shipping2/version'),
                'carrier_code' => $carrier_code,
            ))),
            'cart' => Mage::getModel('owebia_shipping2/Os2_Data_Cart', array(
                'request' => $request,
                'options' => array(
                    'bundle' => array(
                        'process_children' => (boolean)Mage::getStoreConfig('owebia_shipping2/bundle_product/process_children'),
                        'load_item_options_on_parent' => (boolean)Mage::getStoreConfig('owebia_shipping2/bundle_product/load_item_options_on_parent'),
                        'load_item_data_on_parent' => (boolean)Mage::getStoreConfig('owebia_shipping2/bundle_product/load_item_data_on_parent'),
                        'load_product_data_on_parent' => (boolean)Mage::getStoreConfig('owebia_shipping2/bundle_product/load_product_data_on_parent'),
                    ),
                    'configurable' => array(
                        'load_item_options_on_parent' => (boolean)Mage::getStoreConfig('owebia_shipping2/configurable_product/load_item_options_on_parent'),
                        'load_item_data_on_parent' => (boolean)Mage::getStoreConfig('owebia_shipping2/configurable_product/load_item_data_on_parent'),
                        'load_product_data_on_parent' => (boolean)Mage::getStoreConfig('owebia_shipping2/configurable_product/load_product_data_on_parent'),
                    ),
                ),
            )),
            'quote' => Mage::getModel('owebia_shipping2/Os2_Data_Quote'),
            'selection' => Mage::getModel('owebia_shipping2/Os2_Data_Selection'),
            'customer' => Mage::getModel('owebia_shipping2/Os2_Data_Customer'),
            'customer_group' => Mage::getModel('owebia_shipping2/Os2_Data_CustomerGroup'),
            'customvar' => Mage::getModel('owebia_shipping2/Os2_Data_Customvar'),
            'date' => Mage::getModel('owebia_shipping2/Os2_Data_Date'),
            'address_filter' => Mage::getModel('owebia_shipping2/Os2_Data_AddressFilter'),
            'origin' => Mage::getModel('owebia_shipping2/Os2_Data_Address', $this->_extract($request->getData(), array(
                'country_id' => 'country_id',
                'region_id' => 'region_id',
                'postcode' => 'postcode',
                'city' => 'city',
            ))),
            'shipto' => Mage::getModel('owebia_shipping2/Os2_Data_Address', $this->_extract($request->getData(), array(
                'country_id' => 'dest_country_id',
                'region_id' => 'dest_region_id',
                'region_code' => 'dest_region_code',
                'street' => 'dest_street',
                'city' => 'dest_city',
                'postcode' => 'dest_postcode',
            ))),
            'billto' => Mage::getModel('owebia_shipping2/Os2_Data_Billto'),
            'store' => Mage::getModel('owebia_shipping2/Os2_Data_Store', array('id' => $request->getData('store_id'))),
            'request' => Mage::getModel('owebia_shipping2/Os2_Data_Abstract', $request->getData()),
        );
    }

    protected function _extract($data, $attributes)
    {
        $extract = array();
        foreach ($attributes as $to => $from) {
            $extract[$to] = isset($data[$from]) ? $data[$from] : null;
        }
        return $extract;
    }
}
