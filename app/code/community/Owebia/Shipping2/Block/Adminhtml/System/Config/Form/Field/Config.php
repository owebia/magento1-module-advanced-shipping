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

class Owebia_Shipping2_Block_Adminhtml_System_Config_Form_Field_Config extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    private static $JS_INCLUDED = false;
    
    public function __()
    {
        $args = func_get_args();
        return Mage::helper('owebia_shipping2')->__($args);
    }

    protected function _prepareLayout()
    {
        $layout = $this->getLayout();
        $head = $layout->getBlock('head');
        $head->addJs('owebia/shipping2/jquery-1.8.2.min.js');
        $head->addJs('owebia/shipping2/jquery.noconflict.js');
        $head->addJs('owebia/shipping2/jquery-ui-1.8.23.custom/js/jquery-ui-1.8.23.custom.min.js');
        $head->addJs('owebia/shipping2/jquery.layout-1.3.0-rc30.6.min.js');
        $head->addJs('owebia/shipping2/colorbox/jquery.colorbox-min.js');
        $head->addJs('owebia/shipping2/jquery.caret.1.02.min.js');
        $head->addJs('owebia/shipping2/os2editor.js');
        //$head->addItem('js_css', 'owebia/shipping2/jquery-ui-1.8.23.custom/css/ui-lightness/jquery-ui-1.8.23.custom.css');
        $head->addItem('js_css', 'owebia/shipping2/colorbox/colorbox.css', 'media="all"');
        $head->addItem('js_css', 'owebia/shipping2/os2editor.css', 'media="all"');
        //$head->addItem('other', 'http://ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js');
        //$head->append($block);
        
        parent::_prepareLayout();
    }

    private function label__($input)
    {
        return str_replace(array("\r\n","\r","\n","'"), array("\\n","\\n","\\n","\\'"), $this->__($input));
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $output = '';
        if (!self::$JS_INCLUDED) {
            $output = "<script type=\"text/javascript\">\n"
                ."//<![CDATA[\n"
                ."jQuery.noConflict();\n"
                ."var os2editor = new OS2Editor({\n"
                ."ajax_url: '".$this->getUrl('adminhtml/os2_ajax/index')."?isAjax=true',\n"
                ."form_key: FORM_KEY,\n"
                ."apply_btn_label: '".$this->label__('Apply')."',\n"
                ."cancel_btn_label: '".$this->label__('Cancel')."',\n"
                ."menu_item_dissociate_label: '".$this->label__('Dissociate')."',\n"
                ."menu_item_remove_label: '".$this->label__('Remove')."',\n"
                ."menu_item_edit_label: '".$this->label__('Edit')."',\n"
                ."prompt_new_value_label: '".$this->label__('Enter the new value:')."',\n"
                ."default_row_label: '".$this->label__('[No label]')."',\n"
                ."loading_label: '".$this->label__('Loading...')."'\n"
                ."});\n"
                ."
"
                ."//]]>\n"
                ."</script>\n"
            ;
            self::$JS_INCLUDED = true;
        }

        $shipping_code = preg_replace('/^groups\[([^\]]*)\].*$/','\1',$element->getName());
        return <<<EOD
{$output}
<div style="margin-bottom:1px;">
    <button type="button" class="scalable" onclick="os2editor.init(this, '{$shipping_code}').page('source');"><span>{$this->__('Source &amp; Correction')}</span></button>
    <button type="button" class="scalable" onclick="os2editor.init(this, '{$shipping_code}').help('summary');"><span>{$this->__('Help')}</span></button>
    <!--<a href="{$this->getUrl('adminhtml/os2_ajax/doc')}">doc</a>-->
</div>
{$element->getElementHtml()}<br/>
<a href="http://www.owebia.com/contributions/magento/owebia-shipping/fr/modeles-de-configuration" target="_blank">{$this->__('Download configuration templates')}</a>
EOD;
    }
}
