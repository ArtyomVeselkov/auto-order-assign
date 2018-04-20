<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Model_Cron_Reassign
 */

class Optimlight_AutoOrderAssign_Model_Cron_Reassign extends Optimlight_AutoOrderAssign_Model_Cron_Abstractum
{
    /**
     * Default in Abstractum is 1 minute.
     *
     * @var int
     */
    protected static $_frequency = Optimlight_AutoOrderAssign_Helper_Data::QUEUE_DEFAULT_PAUSE;
    /**
     * @var string
     */
    protected static $_keyName = 'opt_aoa_cron_lock_reassign';

    /**
     * Optimlight_AutoOrderAssign_Model_Cron_Reassign constructor.
     */
    public function __construct()
    {
        parent::__construct();
        static::$_frequency = Optimlight_AutoOrderAssign_Helper_Data::getQueuePause();
    }

    /**
     * @param Varien_Object $settings
     * @return mixed
     */
    public function execute($settings)
    {
        $result = [];
        $collection = $this->getCollection($settings);
        /**
         * @var Mage_Sales_Model_Order $item
         */
        foreach ($collection->getItems() as $index => $item) {
            $matched = false; // New customer (target) model.
            if (static::$_assign->checkOrder($item, $settings, $matched)) {
                if ($matched) {
                    $result[$item->getId()] = static::$_assign->reassignOrder($item, $matched);
                }
            }
        }
        return $result;
    }

    /**
     * @param Varien_Object $settings
     * @return Mage_Sales_Model_Resource_Sale_Collection
     */
    public function getCollection($settings)
    {
        $date = false;
        $timeLimiter = $settings->getDataUsingMethod('time_limiter');
        if ($timeLimiter) {
            try {
                if (is_int($timeLimiter)) {
                    $sub = new DateInterval('P' . $timeLimiter . 'D');
                } else {
                    $sub = new DateInterval($timeLimiter);
                }
                $date = $this->_now->sub($sub);
                // $date = $this->_now->add(new DateInterval('P1D')); // TODO Remove after testing.
            } catch (Exception $exception) {}
        }
        $notProcessed = $settings->getDataUsingMethod('process_only_new');
        $limit = $settings->getDataUsingMethod('page_limit');
        /** @var Optimlight_AutoOrderAssign_Model_Resource_Reference_Collection $resource */
        $resource = Mage::getResourceModel('opt_aoa/reference_collection');
        $collection = $resource->getJoinedCollection($limit, $date, $notProcessed);
        return $collection;
    }
}