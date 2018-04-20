<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Helper_Block
 */

class Optimlight_AutoOrderAssign_Helper_Block
{
    /**
     * @var Optimlight_AutoOrderAssign_Helper_Source
     */
    protected $_sourceHelper;

    /**
     * Optimlight_AutoOrderAssign_Helper_Block constructor.
     */
    public function __construct()
    {
        $this->_sourceHelper = Mage::helper('opt_aoa/source');
    }

    /**
     * @param int $orderId
     * @return bool|int
     */
    public function getCountReferenceByOrderId($orderId)
    {
        $result = false;
        if (!$orderId) {
            return $result;
        }
        if ($orderId) {
            /** @var Optimlight_AutoOrderAssign_Model_Reference $model */
            $model = Mage::getModel('opt_aoa/reference');
            /** @var Optimlight_AutoOrderAssign_Model_Resource_Reference_Collection $resource */
            $resource = $model->getResourceCollection();
            /** @var  $collection */
            $collection = $resource->getCollectionByOrderId($orderId);
            $result = $collection->getSize();
        }
        return $result;
    }

    /**
     * @param Mage_Adminhtml_Block_Sales_Order $block
     * @throws Varien_Exception
     */
    public function appendOrderReassignButton($block)
    {
        $title = 'Reassign%s';
        /** @var int $orderId */
        $orderId = $block->getDataUsingMethod('order_id');
        $count = $this->getCountReferenceByOrderId($orderId);
        if ($count && is_int($count) && 0 < $count) {
            $suffix = ' [X]';
        } else {
            $suffix = '';
        }
        $title = sprintf($title, $suffix);
        $buttonData = Optimlight_AutoOrderAssign_Helper_Source::getOrderReferenceButtonData($title);
        $button = new Varien_Object($buttonData);
        $id = $button->getId();
        $sortOrder = $button->getSortOrder();
        $data = $button->getButtonData();
        $data['onclick'] = @$data['onclick'];
        // $data['onclick'] = @sprintf(@$data['onclick'], $orderId); // Alternatively.
        $block->addButton($id, $data, $sortOrder);
    }
}