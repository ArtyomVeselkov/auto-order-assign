<?php

/**
 * Copyright © 2018 Optimlight. All rights reserved.
 * See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_MetaBase_Block_Adminhtml_Mage_Sales_Order_Edit
 */

class Optimlight_AutoOrderAssign_Block_Adminhtml_FormAssign_Blank extends Mage_Adminhtml_Block_Abstract
{
    const DEFAULT_TEMPLATE = 'optimlight/aoa/form-assign/blank.phtml';

    /**
     *
     */
    public function _construct()
    {
        $this->setData('template', self::DEFAULT_TEMPLATE);
        parent::_construct();
    }
}