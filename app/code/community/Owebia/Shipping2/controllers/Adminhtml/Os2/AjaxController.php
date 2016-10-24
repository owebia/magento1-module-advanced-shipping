<?php
/**
 * Copyright © 2008-2016 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Adminhtml_Os2_AjaxController extends Owebia_Shipping2_Controller_Abstract
{
    protected function _getOs2Helper($config, $autocorrection = true)
    {
        include_once $this->getModulePath('includes/OS2_AddressFilterParser.php');
        include_once $this->getModulePath('includes/OwebiaShippingHelper.php');
        $helper = new OwebiaShippingHelper($config, $autocorrection);
        return $helper;
    }

    protected function _getEditor($data)
    {
        $helper = $this->_getOs2Helper($data['source'], $autocorrection = true);
        $helper->checkConfig();
        $config = $helper->getConfig();
        $block = $this->getLayout()->createBlock(
            'owebia_shipping2/adminhtml_os2_editor',
            'os2_editor',
            array('config' => $config, 'opened_row_ids' => isset($data['row_ids']) ? $data['row_ids'] : array())
        );
        return $block->getHtml();
    }

    protected function _getCorrection($config, $compress = false, $html = false)
    {
        $helper = $this->_getOs2Helper($config);
        return $helper->formatConfig($compress, $keysToRemove = array('*id'), $html);
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
                'helper' => $this->_getOs2Helper(''),
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
        $helper = $this->_getOs2Helper($request->getPost('source'));
        $helper->checkConfig();
        return $this->json(
            array(
                'correction' => $helper->formatConfig(
                    $compress = false,
                    $keysToRemove = array('*id'),
                    $html = true
                ),
                'debug' => $helper->getDebug(),
                'editor' => $this->_getEditor($request->getPost()),
            )
        );
    }

    protected function ajaxUpdateProperty()
    {
        $request = $this->getRequest();
        $helper = $this->_getOs2Helper($request->getPost('source'));
        $config = $helper->getConfig();
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
        $helper->setConfig($config);
        return $this->json(
            array(
                'source' => $helper->formatConfig(
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
        $helper = $this->_getOs2Helper($request->getPost('source'));
        $row = array(
            'label' => array('value' => $this->__('New shipping method')),
            'fees' => array('value' => 0),
        ); // By reference
        $helper->addRow('new' . time(), $row);
        return $this->json(
            array(
                'source' => $helper->formatConfig(
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
        $helper = $this->_getOs2Helper($request->getPost('source'));
        $config = $helper->getConfig();
        unset($config[$request->getPost('id')]);
        $helper->setConfig($config);
        return $this->json(
            array(
                'source' => $helper->formatConfig(
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
        $shippingCode = $request->getPost('shipping_code')
        $compress = (bool)Mage::getStoreConfig('carriers/' . $shippingCode . '/compression');
        $source = $request->getPost('source');
        $config = $compress ? $this->_getCorrection($source, $compress) : $source;
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
        $helper = $this->_getOs2Helper($request->getPost('source'));
        $row = $helper->getConfigRow($request->getPost('id'));
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

    public function docAction()
    {
        $this->getResponse()
            ->setHeader('Content-Type', 'text/html; charset=UTF-8');

        $fileHandler = fopen(Mage::getBaseDir('locale') . '/fr_FR/Owebia_Shipping2.csv', 'r');
        $output = "<style>.new{color:blue}strike,.deprecated{color:maroon}</style>";
        while ($row = fgetcsv($fileHandler, 4096, ',', '"')) {
            if (isset($row[0])) {
                $key = $row[0];
                $data[$key] = isset($row[1]) ? $row[1] : null;
                if (substr($key, 0, 16)=='{os2editor.help.') {
                    $id = preg_replace('/[^a-z]/', '_', substr($key, 16, -1));
                    $content = $this->_processHelp($id, $data[$key]);
                    $output .= "<div class=\"field\"><a name=\"" . $id . "\"></a>" . $content . "</div>";
                }
            }
        }
        return $this->outputContent($output);
    }
}
