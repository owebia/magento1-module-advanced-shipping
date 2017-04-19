<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Block_Adminhtml_Os2_Help extends Mage_Adminhtml_Block_Abstract
{
    public function __()
    {
        $args = func_get_args();
        return Mage::helper('owebia_shipping2')->__($args);
    }

    public function getHtml()
    {
        $controller = $this->getData('controller');
        $helpId = $this->getData('help_id');
        $content = $this->getData('content');
        $helper = $this->getData('helper');

        $localeCode = Mage::app()->getLocale()->getLocaleCode();
        $helpFileDir = Mage::getBaseDir('app') . '/code/community/Owebia/Shipping2';
        $helpFileBasename = 'doc_' . $localeCode . '.html';
        $ioFile = new Varien_Io_File();
        $ioFile->cd($helpFileDir);
        if ($ioFile->fileExists($helpFileBasename)) {
            $helpFileBasename = 'doc_en_US.html';
        }
        $content = $ioFile->read($helpFileBasename);
        $docSidebar = preg_replace('#^.*<!-- doc sidebar start -->(.*)<!-- doc sidebar end -->.*$#s', '\1', $content);
        $docContent = preg_replace('#^.*<!-- doc content start -->(.*)<!-- doc content end -->.*$#s', '\1', $content);
        $docScript = preg_replace('#^.*<!-- doc scripts start -->(.*)<!-- doc scripts end -->.*$#s', '\1', $content);
        $docScript = str_replace('$(', "jQuery(", $docScript);
        $docScript = str_replace('$.', "jQuery.", $docScript);
        $content = $docSidebar . $docContent
            . "<script>
jQuery.fn.scrollspy = function(){};
{$docScript}
function bjson() {
    var index = 0;
    jQuery('div.json').each(function(){
        var text = jQuery(this).text();
        while (text.match(/\"__auto__\"/)) {
            text = text.replace(/\"__auto__\"/, '\"id_' + ('000' + index).slice(-3) + '\"');
            index++;
        }
        var obj = jQuery.parseJSON(text);
        var beautified = jsonEncode(obj, true, true);
        jQuery(this).html('<pre>' + beautified + '</pre>');
        jQuery(this).addClass('code');
    });
}
setTimeout(function(){
    bjson();
}, 1000);
</script>"
        ;
        $nav = '';
        $title = '';
        $header = "<div class=\"ui-layout-north os2-help-header\">{$nav}<h4>{$title}</h4></div>";
        $content = ($header ? "{$header}" : '') . "<div id=os2-help class=ui-layout-center>{$content}</div>";
        return $content;
    }
}
