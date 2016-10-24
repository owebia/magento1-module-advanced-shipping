<?php

/**
 * Copyright (c) 2008-16 Owebia
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

    protected function outputContent($content)
    {
        return $this->getResponse()
            ->setBody($content);
    }

    protected function json($data)
    {
        return $this->outputContent(
            Mage::helper('core')
                ->jsonEncode($data)
        );
    }

    protected function getMimeType($extension)
    {
        $mimeTypeArray = array(
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
        return isset($mimeTypeArray[$extension]) ? $mimeTypeArray[$extension] : 'application/octet-stream';
    }
    
    protected function forceDownload($filename, $content)
    {
        if (headers_sent()) {
            trigger_error('forceDownload($filename) - Headers have already been sent',E_USER_ERROR);
            return false;
        }

        $extension = strrchr($filename,'.');
        $mimeType = $this->getMimeType($extension);

        header('Content-disposition: attachment; filename="'.$filename.'"');
        header('Content-Type: application/force-download');
        header('Content-Transfer-Encoding: '.$mimeType."\n"); // Surtout ne pas enlever le \n
        //header('Content-Length: '.filesize($filename));
        header('Pragma: no-cache');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        return $this->outputContent($content);
    }

    protected function cleanKey($key)
    {
        return preg_replace('/[^a-z0-9\-_]/i','_',$key);
    }

    protected function page($page, $layoutContent = array(), $withDialog = true)
    {
        if (!is_array($layoutContent)) $layoutContent = array('center' => $layoutContent);
        return ($withDialog ? "<div id=os2-dialog>"
                    . $this->pageHeader($this->__('Owebia Shipping 2 Editor'),
                        $this->button__('Source &amp; Correction',     "os2editor.page('source');",     'source')
                        . $this->button__('Help',       "os2editor.help('summary');",    'help')
                        . $this->button__('Close',      "os2editor.close();",            'cancel')
                    )
                    . "<div id=os2-page-container class=ui-layout-center>" : '')
                        . "<div id=os2-page-{$page} class=os2-page>"
                            . (!isset($layoutContent['north']) ? '' : "<div class=\"ui-layout-north inner-layout\">".$layoutContent['north']."</div>")
                            . "<div class=\"ui-layout-center inner-layout\">".$layoutContent['center']."</div>"
                            . (!isset($layoutContent['south']) ? '' : "<div class=\"ui-layout-south inner-layout\">".$layoutContent['south']."</div>")
                            . (!isset($layoutContent['west']) ? '' : "<div class=\"ui-layout-west inner-layout\">".$layoutContent['west']."</div>")
                            . (!isset($layoutContent['east']) ? '' : "<div class=\"ui-layout-east inner-layout\">".$layoutContent['east']."</div>")
                        . "</div>"
                    . ($withDialog ? "</div>"
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

    public function button($label, $onclick, $className='')
    {
        $className = 'scalable'.($className!='' ? ' '.$className : '');
        return "<button type=\"button\" class=\"".$className."\" onclick=\"".$onclick."\"><span>".$label."</span></button>";
    }

    public function button__($label, $onclick, $className='')
    {
        return $this->button($this->__($label),$onclick,$className);
    }
}
