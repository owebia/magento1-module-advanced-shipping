<?php
/**
 * Copyright © 2019 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

class Owebia_Shipping2_Model_Registry
{
    /**
     * @var array
     */
    protected $data = [
        [] // Main Scope
    ];

    /**
     * @var array
     */
    protected $globalVariables = [
        [] // Main Scope
    ];

    /**
     * @param mixed $data
     * @return mixed
     */
    public function wrap($data)
    {
        $type = gettype($data);
        if ($type == 'NULL' || $type == 'boolean' || $type == 'integer' || $type == 'double' || $type == 'string') {
            return $data;
        } elseif ($type == 'array') {
            return $data;
        } elseif ($type == 'object') {
            if ($data instanceof \Closure) {
                return $data;
            } else {
                return new \Exception(__("Unsupported class %s", get_class($type)));
            }
        } else {
            throw new \Exception(__("Unsupported type %s", $type));
        }
    }

    /**
     * @param string $name
     * @param int|null $scopeIndex
     * @return mixed
     */
    public function get($name, $scopeIndex = null)
    {
        if (! isset($scopeIndex)) {
            $scopeIndex = $this->getCurrentScopeIndex();
        }

        if (isset($this->globalVariables[$scopeIndex][$name])) {
            $scopeIndex = 0;
        }

        if (isset($this->data[$scopeIndex][$name])) {
            return $this->data[$scopeIndex][$name];
        }

        return null;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getGlobal($name)
    {
        return $this->get($name, 0);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param bool $override
     * @param int $scopeIndex
     */
    public function register($name, $value, $override = false, $scopeIndex = null)
    {
        if (! isset($scopeIndex)) {
            $scopeIndex = $this->getCurrentScopeIndex();
        }

        if (isset($this->globalVariables[$scopeIndex][$name])) {
            $scopeIndex = 0;
        }

        if (!$override && isset($this->data[$scopeIndex][$name])) {
            return;
        }

        $this->data[$scopeIndex][$name] = $value;
    }

    /**
     * @param string $name
     */
    public function declareGlobalAtCurrentScope($name)
    {
        $scopeIndex = $this->getCurrentScopeIndex();
        if (!isset($this->globalVariables[$scopeIndex][$name])) {
            $this->globalVariables[$scopeIndex][$name] = true;
        }
    }

    /**
     * @return int Current scope Index
     */
    public function getCurrentScopeIndex()
    {
        return count($this->data) - 1;
    }

    public function createScope()
    {
        $scopeIndex = $this->getCurrentScopeIndex() + 1;
        $this->data[$scopeIndex] = [];
        $this->globalVariables[$scopeIndex] = [];
    }

    public function deleteScope()
    {
        $scopeIndex = $this->getCurrentScopeIndex();
        unset($this->data[$scopeIndex]);
        unset($this->globalVariables[$scopeIndex]);
    }
}
