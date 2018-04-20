<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Model_Integrator
 */

class Optimlight_AutoOrderAssign_Model_Integrator
{
    /**
     * @var Optimlight_AutoOrderAssign_Model_Integrator_Interface[]
     */
    protected $_integrators = [];
    /**
     * @var array
     */
    protected $_list = [];

    /**
     * Optimlight_AutoOrderAssign_Model_Integrator constructor.
     */
    public function __construct()
    {
        $this->createIntegrators();
    }

    /**
     *
     */
    public function createIntegrators()
    {
        $disabled = [];
        $this->_integrators = [];
        $list = static::getActiveList();
        foreach ($list as $method => $integrators) {
            $this->_list[$method] = [];
            if (is_array($integrators)) {
                foreach ($integrators as $index => $integrator) {
                    if (!isset($disabled[$integrator]) && !isset($this->_integrators[$integrator])) {
                        $classAlias = 'opt_aoa/integrator_' . $integrator;
                        $class = Mage::getModel($classAlias);
                        if (is_object($class) && is_a($class, 'Optimlight_AutoOrderAssign_Model_Integrator_Interface')) {
                            $this->_integrators[$integrator] = $class;
                            $this->_list[$method][] = $integrator;
                        } else {
                            $disabled[$integrator] = true;
                        }
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    public static function getList()
    {
        $list = [];
        return $list;
    }

    /**
     * @return array
     */
    public static function getActiveList()
    {
        $list = static::getList();
        $enabledList = Optimlight_AutoOrderAssign_Helper_Data::getIntegratorsList();
        $result = [];
        foreach ($enabledList as $index => $item) {
            if (isset($list[$item])) {
                $result[$item] = $list[$item];
            }
        }
        return $result;
    }

    /**
     * @param string[]|string $actions
     * @param Optimlight_AutoOrderAssign_Model_Pocket $pocket
     * @return array|null
     */
    public function processActions($actions, $pocket)
    {
        $result = null;
        if (is_string($actions) && strlen($actions)) {
            $actions = [$actions];
        }
        if (!is_array($actions)) {
            return $result;
        }
        $result = [];
        foreach ($actions as $method) {
            if (isset($this->_list[$method])) {
                $integrators = $this->_list[$method];
                if (!is_array($integrators)) {
                    return $result;
                }
                foreach ($integrators as $index => $integrator) {
                    if (isset($this->_integrators[$integrator])) {
                        $class = $this->_integrators[$integrator];
                        $buffer = $this->execIntegrator($class, $method, $pocket);
                        if (is_null($buffer)) {
                            continue;
                        } elseif (is_bool($buffer)) {
                            $result[$method] = $buffer;
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param Optimlight_AutoOrderAssign_Model_Integrator_Interface $integrator
     * @param string $method
     * @param Optimlight_AutoOrderAssign_Model_Pocket $pocket
     * @return bool|null
     */
    protected function execIntegrator($integrator, $method, $pocket)
    {
        $result = null;
        if (!$integrator || !$method || !is_a($pocket, 'Optimlight_AutoOrderAssign_Model_Pocket')) {
            return $result;
        }
        try {
            $fromCustomer = $pocket->getCustomerSource();
            $toCustomer = $pocket->getCustomerTarget();
            $order = $pocket->getOrder();
            $result = call_user_func_array([$integrator, $method], [$fromCustomer, $toCustomer, $order]);
        } catch (Exception $exception) {}
        return $result;
    }
}