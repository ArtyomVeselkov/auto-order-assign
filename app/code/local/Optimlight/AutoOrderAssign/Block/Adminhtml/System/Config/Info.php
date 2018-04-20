<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_MetaBase_Block_Adminhtml_Mage_Sales_Order_Edit
 */

class Optimlight_AutoOrderAssign_Block_Adminhtml_System_Config_Info extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * Set template to itself
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('optimlight/aoa/system/config/info.phtml');
        }

        return $this;
    }

    /**
     * @return array
     */
    protected function examineCollection()
    {
        $settings = Optimlight_AutoOrderAssign_Model_Cron::prepareSettingsObject();
        /** @var Optimlight_AutoOrderAssign_Model_Cron_Reassign $cronModel */
        $cronModel = Mage::getModel('opt_aoa/cron_reassign');
        $cronModel->preProcessSettings($settings);
        $settings->unsetData('page_limit');
        $collectionQueue = $cronModel->getCollection($settings);
        $queueSize = $collectionQueue->getSize();
        /** @var Optimlight_AutoOrderAssign_Model_Resource_Reference_Collection $collectionProcessed */
        $collectionProcessed = Mage::getResourceModel('opt_aoa/reference_collection');
        $processedCount = $collectionProcessed->getSize();
        /** @var Optimlight_AutoOrderAssign_Model_Resource_Reference_Collection $collectionReassigned */
        $collectionReassigned = Mage::getResourceModel('opt_aoa/reference_collection');
        $collectionReassigned->addFieldToFilter('state', Optimlight_AutoOrderAssign_Helper_Source::REFERENCE_STATE_OK);
        $collectionReassigned->addFieldToFilter('customer_id', ['gt' => 0]);
        $reassignedCount = $collectionReassigned->getSize();
        /** @var Mage_Sales_Model_Resource_Order_Collection $collectionOrders */
        $collectionOrders = Mage::getResourceModel('sales/order_collection');
        $ordersCount = $collectionOrders->getSize();
        return [
            'queue_count' => $queueSize,
            'processed_count' => $processedCount,
            'orders_count' => $ordersCount,
            'processed_count_reassigned' => $reassignedCount
        ];
    }

    /**
     * Render template.
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     * @throws Varien_Exception
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $stack = $this->examineCollection();
        $this->addData($stack);
        return $this->_toHtml();
    }

    /**
     * @param string $totalKey
     * @param string $key
     * @return string
     */
    public function printStats($totalKey, $key)
    {
        $result = 'N/A';
        $a = $this->getDataUsingMethod($totalKey);
        $b = $this->getDataUsingMethod($key);
        if ($b) {
            $result = sprintf('%01.2f%%', ($b / $a) * 100);
        }
        return $result;
    }
}
