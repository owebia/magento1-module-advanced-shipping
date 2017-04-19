<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Block_Adminhtml_System_Config_Form_Field_Informations
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    public function __()
    {
        $args = func_get_args();
        return Mage::helper('owebia_shipping2')->__($args);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $version = Mage::getConfig()->getNode('modules/Owebia_Shipping2/version');
        return $this->__('Version: %s', $version);
    }
}
