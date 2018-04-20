<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Model_Observer
 */

class Optimlight_AutoOrderAssign_Model_Observer
{
    /**
     * @var Optimlight_AutoOrderAssign_Model_Integrator
     */
    protected $_integrator = null;

    /**
     * Optimlight_AutoOrderAssign_Model_Observer constructor.
     */
    public function __construct()
    {
        $this->_integrator = Mage::getModel('opt_aoa/integrator');
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function addOrderReassignButton(Varien_Event_Observer $observer)
    {
        if (!Optimlight_AutoOrderAssign_Helper_Data::isEnabled()) {
            return ;
        }
        if (!Optimlight_AutoOrderAssign_Helper_Data::checkHandle(['adminhtml_sales_order_view'])) {
            return ;
        }
        $block = $observer->getDataUsingMethod('block');
        if (
            !$block ||
            (
                !is_a($block, 'Mage_Adminhtml_Block_Sales_Order_View')
            )
        ) {
            return ;
        }
        /** @var Mage_Adminhtml_Block_Sales_Order $block */
        /** @var Optimlight_AutoOrderAssign_Helper_Block $helper */
        $helper = Mage::helper('opt_aoa/block');
        try {
            $helper->appendOrderReassignButton($block);
        } catch (Exception $exception) {
            Optimlight_AutoOrderAssign_Exception::catchException($exception);
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function addCustomerCheckoutLabel(Varien_Event_Observer $observer)
    {
        if (!Optimlight_AutoOrderAssign_Helper_Data::isEnabled()) {
            return ;
        }
        $collection = $observer->getDataUsingMethod('collection');
        $select = $observer->getDataUsingMethod('select');
        if (!$collection || !$select) {
            return ;
        }
        try {
            // Join info about re-assigned orders.
            $table = $collection->getTable('opt_aoa/reference');
            $select->joinLeft(
                ['aoa' => $table],
                '(`aoa`.`id` = `main_table`.`entity_id`)',
                ['opt_aoa_state' => 'state']
            );
        } catch (Exception $exception) {}
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function transferRewardPoints(Varien_Event_Observer $observer)
    {
        if (!Optimlight_AutoOrderAssign_Helper_Data::isEnabled()) {
            return ;
        }
        try {
            /** @var Optimlight_AutoOrderAssign_Model_Pocket $pocket */
            $pocket = $observer->getPocket();
            if (!$pocket || !is_a($pocket, 'Optimlight_AutoOrderAssign_Model_Pocket') || !$pocket->getResultFlag()) {
                return ;
            }
            $this->_integrator->processActions(['transferRewardPoints', 'transferInvoiceRewardPoints'], $pocket);
        } catch (Exception $exception) {}
    }
}