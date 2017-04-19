<?php
/**
 * Copyright © 2008-2017 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

abstract class Owebia_Shipping2_Model_Carrier_Abstract extends Mage_Shipping_Model_Carrier_Abstract
{
    protected $_config;
    protected $_parser;
    protected $_dataModels = array();

    /**
     * Collect rates for this shipping method based on information in $request
     *
     * @param Mage_Shipping_Model_Rate_Request $data
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->__getConfigData('active')) return false; // skip if not enabled
        $process = $this->__getProcess($request);
        return $this->getRates($process);
    }

    public function getRates($process)
    {
        $this->_process($process);
        return $process['result'];
    }

    public function getAllowedMethods()
    {
        $process = array();
        $config = $this->_getConfig();
        $allowedMethods = array();
        if (count($config)>0) {
            foreach ($config as $row) {
                $allowedMethods[$row['*id']] = isset($row['label']) ? $row['label']['value'] : 'No label';
            }
        }
        return $allowedMethods;
    }

    public function isTrackingAvailable()
    {
        return true;
    }

    public function getTrackingInfo($trackingNumber)
    {
        $originalTrackingNumber = $trackingNumber;
        $globalTrackingUrl = $this->__getConfigData('tracking_view_url');
        $trackingUrl = $globalTrackingUrl;
        $parts = explode(':', $trackingNumber);
        if (count($parts) >= 2) {
            $trackingNumber = $parts[1];

            $process = array();
            $config = $this->_getConfig();

            if (isset($config[$parts[0]]['tracking_url'])) {
                $row = $config[$parts[0]];
                $tmpTrackingUrl = $this->getParser()->getRowProperty($row, 'tracking_url');
                if (isset($tmpTrackingUrl)) $trackingUrl = $tmpTrackingUrl;
            }
        }

        $trackingStatus = Mage::getModel('shipping/tracking_result_status')
            ->setCarrier($this->_code)
            ->setCarrierTitle($this->__getConfigData('title'))
            ->setTracking($trackingNumber)
            ->addData(
                array(
                    'status'=> $trackingUrl
                        ? '<a target="_blank" href="' . str_replace('{tracking_number}', $trackingNumber, $trackingUrl)
                            . '">' . $this->__('track the package') . '</a>'
                        : "suivi non disponible pour le colis {$trackingNumber}"
                            . " (originalTrackingNumber='{$originalTrackingNumber}',"
                            . " globalTrackingUrl='{$globalTrackingUrl}'"
                            . (isset($row) ? ", tmpTrackingUrl='{$tmpTrackingUrl}'" : '')
                            . ")"
                )
            );
        $trackingResult = Mage::getModel('shipping/tracking_result')
            ->append($trackingStatus);

        if ($trackings = $trackingResult->getAllTrackings()) return $trackings[0];
        return false;
    }

    protected function _process(&$process)
    {
        $debug = (bool)$this->__getConfigData('debug');
        if ($debug) $this->getParser()->initDebug($this->_code, $process);

        $valueFound = false;
        foreach ($process['config'] as $row) {
            $result = $this->getParser()->processRow($process, $row);
            if ($result->success) {
                $valueFound = true;
                $this->__appendMethod($process, $row, $result->result);
                if ($process['options']->stop_to_first_match) break;
            }
        }

        $httpRequest = Mage::app()->getFrontController()->getRequest();
        if ($debug && $this->__checkRequest($httpRequest, 'checkout/cart/index')) {
            Mage::getSingleton('core/session')
                ->addNotice('DEBUG' . $this->getParser()->getDebug());
        }
    }

    protected function _getConfig()
    {
        if (!isset($this->_config)) {
            $this->_config = $this->getParser()
                ->getConfig();
        }
        return $this->_config;
    }

    protected function getParser()
    {
        if (!isset($this->_parser)) {
            $this->_parser = Mage::getModel('owebia_shipping2/ConfigParser')
                ->init(
                    $this->__getConfigData('config'),
                    (boolean)$this->__getConfigData('auto_correction')
                );
        }
        return $this->_parser;
    }

    protected function __checkRequest($httpRequest, $path)
    {
        list($router, $controller, $action) = explode('/', $path);
        return $httpRequest->getRouteName() == $router
            && $httpRequest->getControllerName() == $controller
            && $httpRequest->getActionName() == $action;
    }

    protected function __getProcess($request)
    {
        $data = Mage::helper('owebia_shipping2')->getDataModelMap($this->getParser(), $this->_code, $request);
        $process = array(
            'data' => $data,
            'cart.items' => array(),
            'config' => $this->_getConfig(),
            'result' => Mage::getModel('shipping/rate_result'),
            'options' => (object)array(
                'auto_escaping' => (boolean)$this->__getConfigData('auto_escaping'),
                'auto_correction' => (boolean)$this->__getConfigData('auto_correction'),
                'stop_to_first_match' => (boolean)$this->__getConfigData('stop_to_first_match'),
            ),
        );
        return $process;
    }

    public function addDataModel($name, $model)
    {
        $this->_dataModels[$name] = $model;
    }

    protected function __getConfigData($key)
    {
        return $this->getConfigData($key);
    }

    protected function __appendMethod(&$process, $row, $fees)
    {
        $helper = Mage::helper('owebia_shipping2');
        $method = Mage::getModel('shipping/rate_result_method')
            ->setCarrier($this->_code)
            ->setCarrierTitle($this->__getConfigData('title'))
            ->setMethod($row['*id'])
            ->setMethodTitle($helper->getMethodText($this->getParser(), $process, $row, 'label'))
            ->setMethodDescription($helper->getMethodText($this->getParser(), $process, $row, 'description'))
            ->setPrice($fees)
            ->setCost($fees);

        $process['result']->append($method);
    }

    protected function __()
    {
        $args = func_get_args();
        return Mage::helper('owebia_shipping2')->__($args);
    }
}
