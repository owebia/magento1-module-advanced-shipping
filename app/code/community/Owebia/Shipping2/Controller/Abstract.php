<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Controller_Abstract extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin');
    }

    public function __()
    {
        $args = func_get_args();
        return Mage::helper('owebia_shipping2')->__($args);
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
            trigger_error('forceDownload($filename) - Headers have already been sent', E_USER_ERROR);
            return false;
        }

        $extension = strrchr($filename, '.');
        $mimeType = $this->getMimeType($extension);

        $this->getResponse()
            ->clearHeaders()
            ->setHeader('Content-disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Content-Type', 'application/force-download')
            ->setHeader('Content-Transfer-Encoding', $mimeType . "\n") // Surtout ne pas enlever le \n
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0')
            ->setHeader('Expires', '0');
        return $this->outputContent($content);
    }

    protected function cleanKey($key)
    {
        return preg_replace('/[^a-z0-9\-_]/i', '_', $key);
    }

    protected function page($page, $layoutContent = array(), $withDialog = true)
    {
        if (!is_array($layoutContent)) $layoutContent = array('center' => $layoutContent);
        return ($withDialog ? "<div id=os2-dialog>"
                    . $this->pageHeader(
                        $this->__('Advanced Shipping 2 Editor'),
                        $this->button__('Source &amp; Correction', "os2editor.page('source');", 'source')
                            . $this->button__('Help', "os2editor.help('summary');", 'help')
                            . $this->button__('Close', "os2editor.close();", 'cancel')
                    )
                    . "<div id=os2-page-container class=ui-layout-center>" : '')
                        . "<div id=os2-page-{$page} class=os2-page>"
                            . (!isset($layoutContent['north'])
                                ? ''
                                : "<div class=\"ui-layout-north inner-layout\">" . $layoutContent['north'] . "</div>"
                            )
                            . "<div class=\"ui-layout-center inner-layout\">" . $layoutContent['center'] . "</div>"
                            . (!isset($layoutContent['south'])
                                ? ''
                                : "<div class=\"ui-layout-south inner-layout\">" . $layoutContent['south'] . "</div>"
                            )
                            . (!isset($layoutContent['west'])
                                ? ''
                                : "<div class=\"ui-layout-west inner-layout\">" . $layoutContent['west'] . "</div>"
                            )
                            . (!isset($layoutContent['east'])
                                ? ''
                                : "<div class=\"ui-layout-east inner-layout\">" . $layoutContent['east'] . "</div>"
                            )
                        . "</div>"
                    . ($withDialog ? "</div>"
                . "</div>" : '')
        ;
    }

    protected function pageHeader($title, $buttons)
    {
        return "<div class=ui-layout-north><div id=os2-page-header>"
                    . "<table cellspacing=0><tr>"
                        . "<td><h3>{$title}</h3></td>"
                        . "<td class=buttons>{$buttons}</td>"
                    . "</tr></table>"
                . "</div></div>"
        ;
    }

    public function button($label, $onclick, $className = '')
    {
        $className = 'scalable' . ($className != '' ? ' ' . $className : '');
        return "<button type=\"button\" class=\"" . $className . "\" onclick=\"" . $onclick . "\">"
            . "<span>" . $label . "</span></button>";
    }

    public function button__($label, $onclick, $className = '')
    {
        return $this->button($this->__($label), $onclick, $className);
    }
}
