<?php
/**
 * Copyright © 2019 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Observer
{
    public function registerAutoloader()
    {
        if (!class_exists('\PhpParser\Autoloader', false)) {
            $path = Mage::getBaseDir('lib') . DS . 'PhpParser' . DS . 'Autoloader.php';
            spl_autoload_unregister([ Varien_Autoload::instance(), 'autoload' ]);
            require_once $path;
            \PhpParser\Autoloader::register();
            spl_autoload_register([ Varien_Autoload::instance(), 'autoload' ]);
        }
    }
}
