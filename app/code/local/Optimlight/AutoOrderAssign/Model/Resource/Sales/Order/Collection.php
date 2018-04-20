<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Model_Resource_Sales_Order_Collection
 */

class Optimlight_AutoOrderAssign_Model_Resource_Sales_Order_Collection extends Mage_Sales_Model_Resource_Order_Collection
{
    /**
     * Optimlight_AutoOrderAssign_Model_Resource_Sales_Order_Collection constructor.
     * @param Mage_Core_Model_Resource_Db_Abstract|array|null $resource
     */
    public function __construct($resource = null)
    {
        parent::__construct($resource);
        $this->_itemObjectClass = 'Optimlight_AutoOrderAssign_Model_Sales_Order';
    }
}