<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Model_Resource_Reference_Collection
 */

class Optimlight_AutoOrderAssign_Model_Resource_Reference_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     *
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('opt_aoa/reference');
    }

    /**
     * @param $ids
     * @return Varien_Data_Collection_Db
     */
    public function loadByIds($ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $this->addFieldToFilter('id', array('in' => $ids));
        return $this->load();
    }

    /**
     * @param array $fields
     * @return Optimlight_AutoOrderAssign_Model_Resource_Reference_Collection
     */
    public static function loadByFields($fields = [])
    {
        $result = Mage::getResourceModel('opt_aoa/reference_collection');
        if (!is_array($fields) || empty($fields)) {
            // To get empty collection.
            $result->addFieldToFilter('id', array('lt' => -1));
        } else {
            foreach ($fields as $field => $condition) {
                $result->addFieldToFilter($field, $condition);
            }
        }
        return $result;
    }

    /**
     * @param int $orderId
     * @return Optimlight_AutoOrderAssign_Model_Resource_Reference_Collection|bool
     */
    public function getCollectionByOrderId($orderId)
    {
        $result = false;
        if (!$orderId) {
            return $result;
        }
        $result = static::loadByFields(['id' => $orderId]);
        return $result;
    }

    /**
     * @param null|int $limit
     * @param null|int|DateTime|string $timeLimiter
     * @param null|bool $notProcessedFlag
     * @return Optimlight_AutoOrderAssign_Model_Resource_Sales_Order_Collection
     */
    public function getJoinedCollection($limit = null, $timeLimiter = null, $notProcessedFlag = null)
    {
        $adapter = Optimlight_AutoOrderAssign_Helper_Direct::getDbConnection('core_write');
        /** @var Optimlight_AutoOrderAssign_Model_Resource_Sales_Order_Collection $order */
        $order = Mage::getResourceModel('opt_aoa/sales_order_collection');
        $referenceTable = $this->getTable('opt_aoa/reference');
        $order->getSelect()
            ->joinLeft(
                ['ar' => $referenceTable],
                $adapter->quoteInto('`main_table`.`entity_id` = `ar`.`id`', ''),
                [
                    'aoa_id' => 'id',
                    'aoa_customer_id' => 'customer_id',
                    'aoa_customer_email' => 'customer_email',
                ]
            );
        if ($timeLimiter) {
            $order->getSelect()
                ->where('`ar`.`time` <= DATE(?) OR `ar`.`time` IS NULL', $timeLimiter->format('Y-m-d H:i:s'));
        }
        if ($notProcessedFlag) {
            $order->getSelect()
                ->where('`ar`.`state` IS NOT NULL')
                ->where('`ar`.`id` IS NOT NULL');
        }
        if ($limit) {
            $order->getSelect()
                ->limit($limit);
        }
        $order->getSelect()
            ->order('ar.time ASC');
        return $order;
    }
}