<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Helper_Data extends Mage_Core_Helper_Data
{
    protected $_isTranslateInlineEnabled;

    public function __()
    {
        $args = func_get_args();
        if (isset($args[0]) && is_array($args[0]) && count($args) == 1) {
            $args = $args[0];
        }
        $message = array_shift($args);
        if ($message instanceof Owebia_Shipping2_Model_Os2_Message) {
            $args = $message->args;
            $message = $message->message;
        }

        $output = parent::__($message);

        if (count($args) == 0) {
            $result = $output;
        } else {
            if (!isset($this->_isTranslateInlineEnabled)) {
                $this->_isTranslateInlineEnabled = Mage::getSingleton('core/translate')
                    ->getTranslateInline();
            }
            if ($this->_isTranslateInlineEnabled) {
                $parts = explode('}}{{', $output);
                $parts[0] = vsprintf($parts[0], $args);
                $result = implode('}}{{', $parts);
            } else {
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
        return $helper->evalInput(
            $process,
            $row,
            $property,
            str_replace(
                array(
                    '{cart.weight}',
                    '{cart.price-tax+discount}',
                    '{cart.price-tax-discount}',
                    '{cart.price+tax+discount}',
                    '{cart.price+tax-discount}',
                ),
                array(
                    $cart->getData('weight') . $cart->getData('weight_unit'),
                    $this->currency($cart->getData('price-tax+discount')),
                    $this->currency($cart->getData('price-tax-discount')),
                    $this->currency($cart->getData('price+tax+discount')),
                    $this->currency($cart->getData('price+tax-discount')),
                ),
                $helper->getRowProperty($row, $property)
            )
        );
    }

    protected function getBoolean($path)
    {
        return (boolean) Mage::getStoreConfig('owebia_shipping2/' . $path);
    }

    public function getDataModelMap($helper, $carrierCode, $request)
    {
        $mageConfig = Mage::getConfig();
        $cartOptions = array(
            'bundle' => array(
                'process_children' => $this->getBoolean('bundle_product/process_children'),
                'load_item_options_on_parent' => $this->getBoolean('bundle_product/load_item_options_on_parent'),
                'load_item_data_on_parent' => $this->getBoolean('bundle_product/load_item_data_on_parent'),
                'load_product_data_on_parent' => $this->getBoolean('bundle_product/load_product_data_on_parent'),
            ),
            'configurable' => array(
                'load_item_options_on_parent' => $this->getBoolean('configurable_product/load_item_options_on_parent'),
                'load_item_data_on_parent' => $this->getBoolean('configurable_product/load_item_data_on_parent'),
                'load_product_data_on_parent' => $this->getBoolean('configurable_product/load_product_data_on_parent'),
            ),
        );
        return array(
            'info' => Mage::getModel(
                'owebia_shipping2/Os2_Data_Info',
                array_merge(
                    $helper->getInfos(),
                    array(
                        'magento_version' => Mage::getVersion(),
                        'module_version' => (string)$mageConfig->getNode('modules/Owebia_Shipping2/version'),
                        'carrier_code' => $carrierCode,
                    )
                )
            ),
            'cart' => Mage::getModel(
                'owebia_shipping2/Os2_Data_Cart',
                array(
                    'request' => $request,
                    'options' => $cartOptions,
                )
            ),
            'quote' => Mage::getModel('owebia_shipping2/Os2_Data_Quote'),
            'selection' => Mage::getModel('owebia_shipping2/Os2_Data_Selection'),
            'customer' => Mage::getModel('owebia_shipping2/Os2_Data_Customer'),
            'customer_group' => Mage::getModel('owebia_shipping2/Os2_Data_CustomerGroup'),
            'customvar' => Mage::getModel('owebia_shipping2/Os2_Data_Customvar'),
            'date' => Mage::getModel('owebia_shipping2/Os2_Data_Date'),
            'address_filter' => Mage::getModel('owebia_shipping2/Os2_Data_AddressFilter'),
            'origin' => Mage::getModel(
                'owebia_shipping2/Os2_Data_Address',
                $this->_extract(
                    $request->getData(),
                    array(
                        'country_id' => 'country_id',
                        'region_id' => 'region_id',
                        'postcode' => 'postcode',
                        'city' => 'city',
                    )
                )
            ),
            'shipto' => Mage::getModel(
                'owebia_shipping2/Os2_Data_Address',
                $this->_extract(
                    $request->getData(),
                    array(
                        'country_id' => 'dest_country_id',
                        'region_id' => 'dest_region_id',
                        'region_code' => 'dest_region_code',
                        'street' => 'dest_street',
                        'city' => 'dest_city',
                        'postcode' => 'dest_postcode',
                    )
                )
            ),
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
