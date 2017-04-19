<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Adminhtml_Os2_AjaxController extends Owebia_Shipping2_Controller_Abstract
{
    protected function getParser($config, $autocorrection = true)
    {
        $parser = Mage::getModel('owebia_shipping2/ConfigParser')
            ->init($config, $autocorrection);
        return $parser;
    }

    protected function _getEditor($data)
    {
        $parser = $this->getParser($data['source'], $autocorrection = true);
        $parser->checkConfig();
        $config = $parser->getConfig();
        $block = $this->getLayout()->createBlock(
            'owebia_shipping2/adminhtml_os2_editor',
            'os2_editor',
            array('config' => $config, 'opened_row_ids' => isset($data['row_ids']) ? $data['row_ids'] : array())
        );
        return $block->getHtml();
    }

    protected function _getCorrection($config, $compress = false, $html = false)
    {
        $parser = $this->getParser($config);
        return $parser->formatConfig($compress, $keysToRemove = array('*id'), $html);
    }

    protected function _processHelp($helpId, $content)
    {
        $block = $this->getLayout()->createBlock(
            'owebia_shipping2/adminhtml_os2_help',
            'os2_help',
            array(
                'controller' => $this,
                'help_id' => $helpId,
                'content' => $content,
                'helper' => $this->getParser(''),
            )
        );
        return $block->getHtml();
    }

    protected function ajaxPage()
    {
        $request = $this->getRequest();
        $withDialog = (bool)$request->getPost('with_dialog');
        $page = $request->getPost('page');
        $layoutContent = array();
        switch ($page) {
            case 'source':
                $layoutContent['north'] = "<div class=\"os2-page-header ui-layout-center\">"
                    . $this->button__('Apply', "os2editor.save();", 'save')
                    . $this->button__('Export', "os2editor.saveToFile();", '')
                    . $this->button__('Add a shipping method', "os2editor.addRow();", 'add')
                    . "</div>"
                ;
                $layoutContent['west'] = "<div class=ui-layout-north>"
                    . "<h4 class=os2-section-title>{$this->__('Editor')}</h4>"
                    . "</div><div id=os2-editor class=ui-layout-center></div>";
                $layoutContent['center'] = "<div class=ui-layout-north>"
                    . "<h4 class=os2-section-title>{$this->__('Source')}</h4>"
                    . "</div><textarea id=os2-source class=ui-layout-center></textarea>";
                $layoutContent['east'] = "<div class=ui-layout-north>"
                    . "<h4 class=os2-section-title>{$this->__('Correction')}</h4>"
                    . "</div><div id=os2-correction class=ui-layout-center></div>";
                $layoutContent['south'] = "<div class=ui-layout-north>"
                    . "<h4 class=os2-section-title>{$this->__('Debug')}</h4>"
                    . "</div><div id=os2-debug class=ui-layout-center></div>";
                break;
            case 'help':
                $output = $this->__('{os2editor.help.' . $request->getPost('input') . '}');
                $layoutContent['center'] = $this->_processHelp($request->getPost('input'), $output);
                break;
        }
        return $this->outputContent(
            $this->page($page, $layoutContent, $withDialog)
        );
    }

    protected function ajaxCorrection()
    {
        $request = $this->getRequest();
        $parser = $this->getParser($request->getPost('source'));
        $parser->checkConfig();
        return $this->json(
            array(
                'correction' => $parser->formatConfig(
                    $compress = false,
                    $keysToRemove = array('*id'),
                    $html = true
                ),
                'debug' => $parser->getDebug(),
                'editor' => $this->_getEditor($request->getPost()),
            )
        );
    }

    protected function ajaxUpdateProperty()
    {
        $request = $this->getRequest();
        $parser = $this->getParser($request->getPost('source'));
        $config = $parser->getConfig();
        $rowId = $request->getPost('row');
        $property = $request->getPost('property');
        $value = $request->getPost('value');
        if ($property === 'type' && $value == 'method'
            || $property === 'enabled' && $value == '1'
            || $property !== 'enabled' && empty($value)
        ) {
            unset($config[$rowId][$property]);
        } else if ($property === 'enabled') {
            $config[$rowId][$property]['value'] = (bool)$value;
        } else {
            $config[$rowId][$property]['value'] = $value;
        }
        if ($property == '*id' && $value != $rowId) {
            $config[$value] = $config[$rowId];
            unset($config[$rowId]);
        }
        $parser->setConfig($config);
        return $this->json(
            array(
                'source' => $parser->formatConfig(
                    $compress = false,
                    $keysToRemove = array('*id'),
                    $html = false
                ),
            )
        );
    }

    protected function ajaxAddRow()
    {
        $request = $this->getRequest();
        $parser = $this->getParser($request->getPost('source'));
        $row = array(
            'label' => array('value' => $this->__('New shipping method')),
            'fees' => array('value' => 0),
        ); // By reference
        $parser->addRow('new' . uniqid(), $row);
        return $this->json(
            array(
                'source' => $parser->formatConfig(
                    $compress = false,
                    $keysToRemove = array('*id'),
                    $html = false
                ),
            )
        );
    }

    protected function ajaxRemoveRow()
    {
        $request = $this->getRequest();
        $parser = $this->getParser($request->getPost('source'));
        $config = $parser->getConfig();
        unset($config[$request->getPost('id')]);
        $parser->setConfig($config);
        return $this->json(
            array(
                'source' => $parser->formatConfig(
                    $compress = false,
                    $keysToRemove = array('*id'),
                    $html = false
                ),
            )
        );
    }

    protected function ajaxReadableSelection()
    {
        $request = $this->getRequest();
        switch ($request->getPost('property')) {
            case 'shipto':
            case 'billto':
            case 'origin':
                return $this->outputContent(
                    Mage::getModel('owebia_shipping2/Os2_Data_AddressFilter')
                        ->readable($request->getPost('input'))
                );
            case 'customer_groups':
                return $this->outputContent(
                    Mage::getModel('owebia_shipping2/Os2_Data_CustomerGroup')
                        ->readable($request->getPost('input'))
                );
        }
        return $this->outputContent('');
    }

    protected function ajaxSaveConfig()
    {
        $request = $this->getRequest();
        $shippingCode = $request->getPost('shipping_code');
        $config = $request->getPost('source');
        return $this->outputContent($config);
    }

    protected function ajaxSaveToFile()
    {
        $request = $this->getRequest();
        $config = $request->getPost('source');
        return $this->forceDownload('owebia-shipping-config.txt', $config);
    }

    protected function ajaxRowUi()
    {
        $request = $this->getRequest();
        $parser = $this->getParser($request->getPost('source'));
        $row = $parser->getConfigRow($request->getPost('id'));
        $block = $this->getLayout()->createBlock('owebia_shipping2/adminhtml_os2_editor');
        return $this->outputContent(
            $block->getRowUI($row, true)
        );
    }

    protected function ajaxPropertyTools()
    {
        $request = $this->getRequest();
        $block = $this->getLayout()->createBlock('owebia_shipping2/adminhtml_os2_editor');
        return $this->outputContent(
            $block->getPropertyTools($this, $request->getPost('property'))
        );
    }

    public function indexAction()
    {
        $this->getResponse()
            ->setHeader('Content-Type', 'text/html; charset=UTF-8');

        $request = $this->getRequest();
        $map = array(
            'page' => 'ajaxPage',
            'correction' => 'ajaxCorrection',
            'property-tools' => 'ajaxPropertyTools',
            'update-property' => 'ajaxUpdateProperty',
            'add-row' => 'ajaxAddRow',
            'remove-row' => 'ajaxRemoveRow',
            'row-ui' => 'ajaxRowUi',
            'readable-selection' => 'ajaxReadableSelection',
            'save-config' => 'ajaxSaveConfig',
            'save-to-file' => 'ajaxSaveToFile',
        );
        $what = $request->getPost('what');
        if (isset($map[$what])) {
            $callback = $map[$what];
            return $this->$callback();
        }

        return $this->outputContent('');
    }
}
