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

// if compilation
if (file_exists(dirname(__FILE__).'/Owebia_Shipping2_includes_OwebiaShippingHelper.php')) {
    include_once 'Owebia_Shipping2_includes_OS2_AddressFilterParser.php';
    include_once 'Owebia_Shipping2_includes_OwebiaShippingHelper.php';
} else {
    include_once Mage::getBaseDir('code').'/community/Owebia/Shipping2/includes/OS2_AddressFilterParser.php';
    include_once Mage::getBaseDir('code').'/community/Owebia/Shipping2/includes/OwebiaShippingHelper.php';
}

abstract class Owebia_Shipping2_Model_Carrier_Abstract extends Mage_Shipping_Model_Carrier_Abstract
{
    protected $_config;
    protected $_helper;
    protected $_data_models = array();

    /**
     * Collect rates for this shipping method based on information in $request
     *
     * @param Mage_Shipping_Model_Rate_Request $data
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        //setlocale(LC_NUMERIC, 'fr_FR');
        if (!$this->__getConfigData('active')) return false; // skip if not enabled
        //$this->display($request->_data);
        $process = $this->__getProcess($request);
        return $this->getRates($process);
    }
    
    public function display($var)
    {
        $i = 0;
        foreach ($var as $name => $value) {
            //if ($i>20)
                echo "{$name} => {$value}<br/>";
                //$this->_helper->debug($name.' => '.$value.'<br/>');
            $i++;
        }
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
        $allowed_methods = array();
        if (count($config)>0) {
            foreach ($config as $row) $allowed_methods[$row['*id']] = isset($row['label']) ? $row['label']['value'] : 'No label';
        }
        return $allowed_methods;
    }

    public function isTrackingAvailable()
    {
        return true;
    }

    public function getTrackingInfo($tracking_number)
    {
        $original_tracking_number = $tracking_number;
        $global_tracking_url = $this->__getConfigData('tracking_view_url');
        $tracking_url = $global_tracking_url;
        $parts = explode(':', $tracking_number);
        if (count($parts)>=2) {
            $tracking_number = $parts[1];

            $process = array();
            $config = $this->_getConfig();
            
            if (isset($config[$parts[0]]['tracking_url'])) {
                $row = $config[$parts[0]];
                $tmp_tracking_url = $this->_helper->getRowProperty($row, 'tracking_url');
                if (isset($tmp_tracking_url)) $tracking_url = $tmp_tracking_url;
            }
        }

        $tracking_status = Mage::getModel('shipping/tracking_result_status')
            ->setCarrier($this->_code)
            ->setCarrierTitle($this->__getConfigData('title'))
            ->setTracking($tracking_number)
            ->addData(
                array(
                    'status'=> $tracking_url ? '<a target="_blank" href="' . str_replace('{tracking_number}', $tracking_number, $tracking_url) . '">' . $this->__('track the package') . '</a>' : "suivi non disponible pour le colis {$tracking_number} (original_tracking_number='{$original_tracking_number}', global_tracking_url='{$global_tracking_url}'" . (isset($row) ? ", tmp_tracking_url='{$tmp_tracking_url}'" : '') . ")"
                )
            )
        ;
        $tracking_result = Mage::getModel('shipping/tracking_result')
            ->append($tracking_status)
        ;

        if ($trackings = $tracking_result->getAllTrackings()) return $trackings[0];
        return false;
    }
    
    /***************************************************************************************************************************/

    protected function _process(&$process)
    {
        $debug = (bool)(isset($_GET['debug']) ? $_GET['debug'] : $this->__getConfigData('debug'));
        if ($debug) $this->_helper->initDebug($this->_code, $process);

        $value_found = false;
        foreach ($process['config'] as $row) {
            $result = $this->_helper->processRow($process, $row);
            if ($result->success) {
                $value_found = true;
                $this->__appendMethod($process, $row, $result->result);
                if ($process['options']->stop_to_first_match) break;
            }
        }
        
        $http_request = Mage::app()->getFrontController()->getRequest();
        if ($debug && $this->__checkRequest($http_request, 'checkout/cart/index')) {
            Mage::getSingleton('core/session')
                ->addNotice('DEBUG'.$this->_helper->getDebug());
        }
    }

    protected function _getConfig()
    {
        if (!isset($this->_config)) {
            $this->_helper = new OwebiaShippingHelper(
                $this->__getConfigData('config'),
                (boolean)$this->__getConfigData('auto_correction')
            );
            $this->_config = $this->_helper->getConfig();
        }
        return $this->_config;
    }

    /***************************************************************************************************************************/

    protected function __checkRequest($http_request, $path)
    {
        list($router, $controller, $action) = explode('/', $path);
        return $http_request->getRouteName()==$router && $http_request->getControllerName()==$controller && $http_request->getActionName()==$action;
    }

    protected function __getProcess($request)
    {
        $mage_config = Mage::getConfig();
        $os2_config = $this->_getConfig();
        $data = Mage::helper('owebia_shipping2')->getDataModelMap($this->_helper, $this->_code, $request);
        $process = array(
            'data' => $data,
            'cart.items' => array(),
            'config' => $os2_config,
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
        $this->_data_models[$name] = $model;
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
            ->setMethodTitle($helper->getMethodText($this->_helper, $process, $row, 'label'))
            ->setMethodDescription($helper->getMethodText($this->_helper, $process, $row, 'description'))
            ->setPrice($fees)
            ->setCost($fees)
        ;

        $process['result']->append($method);
    }

    protected function __()
    {
        $args = func_get_args();
        return Mage::helper('owebia_shipping2')->__($args);
    }
}

