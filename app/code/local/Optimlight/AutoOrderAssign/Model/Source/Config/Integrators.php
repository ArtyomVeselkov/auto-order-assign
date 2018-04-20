<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Model_Resource_Reference
 */

class Optimlight_AutoOrderAssign_Model_Source_Config_Integrators
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $list = Optimlight_AutoOrderAssign_Model_Integrator::getList();
        $result = [];
        foreach ($list as $method => $integrators) {
            $result[] = [
                'label' => ucfirst($method) . ' for (' . implode(',', $integrators) . ')',
                'value' => $method
            ];
        }
        return $result;
    }
}