<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Model_Resource_Reference
 */

class Optimlight_AutoOrderAssign_Model_Resource_Reference extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     *
     */
    public function _construct()
    {
        $this->_serializableFields = [
            'history' => [[],[]]
        ];
        $this->_isPkAutoIncrement = false;
        $this->_init('opt_aoa/reference', 'id');
    }

    /**
     * Perform actions before object save
     *
     * @param Optimlight_AutoOrderAssign_Model_Reference $object
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        $time = time();
        $object->setTime($time);
        return $this;
    }
}