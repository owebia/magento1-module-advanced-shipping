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

class Owebia_Shipping2_Controller_Abstract extends Mage_Adminhtml_Controller_Action
{
	public function __()
	{
		$args = func_get_args();
		return Mage::helper('owebia_shipping2')->__($args);
	}

	public function getModulePath($path)
	{
		if (file_exists(dirname(__FILE__) . '/Owebia_Shipping2_' . str_replace('/', '_', $path))) {
			return 'Owebia_Shipping2_'.str_replace('/', '_', $path);
		} else {
			return Mage::getBaseDir('code') . '/community/Owebia/Shipping2/' . $path;
		}
	}

	protected function getMimeType($extension)
	{
		$mime_type_array = array(
			'.gz' => 'application/x-gzip',
			'.tgz' => 'application/x-gzip',
			'.zip' => 'application/zip',
			'.pdf' => 'application/pdf',
			'.png' => 'image/png',
			'.gif' => 'image/gif',
			'.jpg' => 'image/jpeg',
			'.jpeg' => 'image/jpeg',
			'.txt' => 'text/plain',
			'.htm' => 'text/html',
			'.html' => 'text/html',
			'.mpg' => 'video/mpeg',
			'.avi' => 'video/x-msvideo',
		);
		return isset($mime_type_array[$extension]) ? $mime_type_array[$extension] : 'application/octet-stream';
	}

	protected function forceDownload($filename, $content)
	{
		if (headers_sent()) {
			trigger_error('forceDownload($filename) - Headers have already been sent',E_USER_ERROR);
			return false;
		}

		$extension = strrchr($filename,'.');
		$mime_type = $this->getMimeType($extension);

		header('Content-disposition: attachment; filename="'.$filename.'"');
		header('Content-Type: application/force-download');
		header('Content-Transfer-Encoding: '.$mime_type."\n"); // Surtout ne pas enlever le \n
		//header('Content-Length: '.filesize($filename));
		header('Pragma: no-cache');
		header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		header('Expires: 0');
		echo $content;
		return true;
	}

	protected function cleanKey($key)
	{
		return preg_replace('/[^a-z0-9\-_]/i','_',$key);
	}

	protected function page($page, $layout_content = array(), $with_dialog = true)
	{
		if (!is_array($layout_content)) $layout_content = array('center' => $layout_content);
		return ($with_dialog ? "<div id=os2-dialog>"
					. $this->pageHeader($this->__('Owebia Shipping 2 Editor'),
						$this->button__('Source &amp; Correction',     "os2editor.page('source');",     'source')
						. $this->button__('Help',       "os2editor.help('summary');",    'help')
						//. $this->button__('Donate',     "os2editor.page('donate');",     'donate')
						. $this->button__('Close',      "os2editor.close();",            'cancel')
					)
					. "<div id=os2-page-container class=ui-layout-center>" : '')
						. "<div id=os2-page-{$page} class=os2-page>"
							. (!isset($layout_content['north']) ? '' : "<div class=\"ui-layout-north inner-layout\">".$layout_content['north']."</div>")
							. "<div class=\"ui-layout-center inner-layout\">".$layout_content['center']."</div>"
							. (!isset($layout_content['south']) ? '' : "<div class=\"ui-layout-south inner-layout\">".$layout_content['south']."</div>")
							. (!isset($layout_content['west']) ? '' : "<div class=\"ui-layout-west inner-layout\">".$layout_content['west']."</div>")
							. (!isset($layout_content['east']) ? '' : "<div class=\"ui-layout-east inner-layout\">".$layout_content['east']."</div>")
						. "</div>"
					. ($with_dialog ? "</div>"
				. "</div>" : '')
		;
	}

	protected function pageHeader($title, $buttons)
	{
		return "<div class=ui-layout-north><div id=os2-page-header>"
					."<table cellspacing=0><tr>"
						."<td><h3>{$title}</h3></td>"
						."<td class=buttons>{$buttons}</td>"
					."</tr></table>"
				."</div></div>"
		;
	}

	public function button($label, $onclick, $class_name='')
	{
		$class_name = 'scalable'.($class_name!='' ? ' '.$class_name : '');
		return "<button type=\"button\" class=\"".$class_name."\" onclick=\"".$onclick."\"><span>".$label."</span></button>";
	}

	public function button__($label, $onclick, $class_name='')
	{
		return $this->button($this->__($label),$onclick,$class_name);
	}
}

