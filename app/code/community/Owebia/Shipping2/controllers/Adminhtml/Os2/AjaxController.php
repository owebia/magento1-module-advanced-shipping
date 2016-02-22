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
		$block = $this->getLayout()->createBlock('owebia_shipping2/adminhtml_os2_editor', 'os2_editor', array('config' => $config, 'opened_row_ids' => isset($data['row_ids']) ? $data['row_ids'] : array()));
		return /*"<pre>".print_r($config, true)."</pre>".*/$block->getHtml();
	}

	protected function _getCorrection($config, $compress = false, $html = false)
	{
		$helper = $this->_getOs2Helper($config);
		return $helper->formatConfig($compress, $keys_to_remove = array('*id'), $html);
	}
	
	protected function _processHelp($help_id, $content)
	{
		$block = $this->getLayout()->createBlock('owebia_shipping2/adminhtml_os2_help', 'os2_help', array(
			'controller' => $this,
			'help_id' => $help_id,
			'content' => $content,
			'helper' => $this->_getOs2Helper(''),
		));
		return $block->getHtml();
	}
	
	public function indexAction()
	{
		header('Content-Type: text/html; charset=UTF-8');

		switch ($_POST['what']) {
			case 'page':
				$with_dialog = (bool)$_POST['with_dialog'];
				$page = $_POST['page'];
				$layout_content = array();
				//$page_header_buttons = null;
				switch ($page) {
					case 'source':
						$layout_content['north'] = "<div class=\"os2-page-header ui-layout-center\">"
							.$this->button__('Apply', "os2editor.save();", 'save')
							.$this->button__('Export', "os2editor.saveToFile();", '')
							.$this->button__('Add a shipping method',"os2editor.addRow();",'add')
							."</div>"
						;
						$layout_content['west'] = "<div class=ui-layout-north><h4 class=os2-section-title>{$this->__('Editor')}</h4></div><div id=os2-editor class=ui-layout-center></div>";
						$layout_content['center'] = "<div class=ui-layout-north><h4 class=os2-section-title>{$this->__('Source')}</h4></div><textarea id=os2-source class=ui-layout-center></textarea>";
						$layout_content['east'] = "<div class=ui-layout-north><h4 class=os2-section-title>{$this->__('Correction')}</h4></div><div id=os2-correction class=ui-layout-center></div>";
						$layout_content['south'] = "<div class=ui-layout-north><h4 class=os2-section-title>{$this->__('Debug')}</h4></div><div id=os2-debug class=ui-layout-center></div>";
						break;
					case 'help':
						$output = $this->__('{os2editor.help.'.$_POST['input'].'}');
						$layout_content['center'] = $this->_processHelp($_POST['input'], $output);
						break;
					case 'donate':
						$layout_content['center'] = "<div class=\"ui-layout-north os2-help-header\"><h4>".$this->__('You appreciate this extension and would like to help?')."</h4></div>"
								."<div id=os2-help class=ui-layout-center>".$this->__('{os2editor.donate-page.content}')."</div>";
						break;
				}
				echo $this->page($page, $layout_content, $with_dialog);
				exit;
			case 'correction':
				$helper = $this->_getOs2Helper($_POST['source']);
				$helper->checkConfig();
				echo json_encode(array(
					'correction' => $helper->formatConfig($compress = false, $keys_to_remove = array('*id'), $html = true),
					'debug' => $helper->getDebug(),
					'editor' => $this->_getEditor($_POST),
				));
				exit;
			case 'property-tools':
				$block = $this->getLayout()->createBlock('owebia_shipping2/adminhtml_os2_editor');
				echo $block->getPropertyTools($this, $_POST['property']);
				exit;
			case 'update-property':
				$helper = $this->_getOs2Helper($_POST['source']);
				$config = $helper->getConfig();
				$row_id = $_POST['row'];
				$property = $_POST['property'];
				$value = $_POST['value'];
				if ($property==='type' && $value=='method' || $property==='enabled' && $value=='1' || $property!=='enabled' && empty($value)) {
					unset($config[$row_id][$property]);
				} else if ($property==='enabled') {
					$config[$row_id][$property]['value'] = (bool)$value;
				} else {
					$config[$row_id][$property]['value'] = $value;
				}
				if ($property=='*id' && $value!=$row_id) {
					$config[$value] = $config[$row_id];
					unset($config[$row_id]);
				}
				$helper->setConfig($config);
				echo json_encode(array(
					'source' => $helper->formatConfig($compress = false, $keys_to_remove = array('*id'), $html = false),
				));
				exit;
			case 'add-row':
				$helper = $this->_getOs2Helper($_POST['source']);
				$row = array('label' => array('value' => $this->__('New shipping method')), 'fees' => array('value' => 0)); // By reference
				$helper->addRow('new'.time(), $row);
				echo json_encode(array(
					'source' => $helper->formatConfig($compress = false, $keys_to_remove = array('*id'), $html = false),
				));
				exit;
			case 'remove-row':
				$helper = $this->_getOs2Helper($_POST['source']);
				$config = $helper->getConfig();
				unset($config[$_POST['id']]);
				$helper->setConfig($config);
				echo json_encode(array(
					'source' => $helper->formatConfig($compress = false, $keys_to_remove = array('*id'), $html = false),
				));
				exit;
			case 'row-ui':
				$helper = $this->_getOs2Helper($_POST['source']);
				$row = $helper->getConfigRow($_POST['id']);
				$block = $this->getLayout()->createBlock('owebia_shipping2/adminhtml_os2_editor');
				echo $block->getRowUI($row, true);
				exit;
			case 'readable-selection':
				switch ($_POST['property']) {
					case 'shipto':
					case 'billto':
					case 'origin':
						echo Mage::getModel('owebia_shipping2/Os2_Data_AddressFilter')->readable($_POST['input']);
						break;
					case 'customer_groups':
						echo Mage::getModel('owebia_shipping2/Os2_Data_CustomerGroup')->readable($_POST['input']);
						break;
				}
				exit;
			case 'save-config':
				$compress = (bool)Mage::getStoreConfig('carriers/'.$_POST['shipping_code'].'/compression');
				$config = $compress ? $this->_getCorrection($_POST['source'], $compress) : $_POST['source'];
				//Mage::getConfig()->saveConfig('carriers/'.$_POST['shipping_code'].'/config',$output);
				echo $config;
				exit;
			case 'save-to-file':
				$config = $_POST['source'];
				$this->forceDownload('owebia-shipping-config.txt', $config);
				exit;
		}

		echo "<script type=\"text/javascript\">".$script."</script>";
		exit;
	}

	public function docAction()
	{
		header('Content-Type: text/html; charset=UTF-8');

		$file_handler = fopen(Mage::getBaseDir('locale').'/fr_FR/Owebia_Shipping2.csv', 'r');
		$output = "<style>.new{color:blue}strike,.deprecated{color:maroon}</style>";
		while ($row = fgetcsv($file_handler,4096,',','"')) {
			if (isset($row[0])) {
				$key = $row[0];
				$data[$key] = isset($row[1]) ? $row[1] : null;
				if (substr($key,0,16)=='{os2editor.help.') {
					$id = preg_replace('/[^a-z]/','_',substr($key,16,-1));
					$content = $this->_processHelp($id, $data[$key]);
					$output .= "<div class=\"field\"><a name=\"".$id."\"></a>".$content."</div>";
				}
			}
		}
		echo $output;
		exit;
	}
}
