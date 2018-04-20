<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Model_Cron
 */

class Optimlight_AutoOrderAssign_Model_Cron
{
    const CACHE_ENABLED = false;
    const LOCK = 'opt_aoa_cron_lock';
    const LOCKTIME = 300; // 5 minutes

    /**
     * @var int
     */
    protected static $_limitSize = 50;
    /**
     * @var bool
     */
    protected static $_unsetLock = false;
    /**
     * @var bool
     */
    protected static $_enabled = false;
    /**
     * @var DateTime
     */
    protected static $_now;
    
    /**
     * @param bool $unsetLock
     */
    public static function init($unsetLock = false)
    {
        ini_set('memory_limit', '2048M');
        static::$_enabled = Optimlight_AutoOrderAssign_Helper_Data::isEnabled();
        static::$_limitSize = 8000; // Optimlight_AutoOrderAssign_Helper_Data::getQueueLimit();
        static::$_unsetLock = $unsetLock;
        static::$_now = static::initTime();
    }

    /**
     * @return array
     */
    public static function getCronTasks()
    {
        return array('Optimlight_AutoOrderAssign_Model_Cron_Reassign' => true);
    }

    /**
     * @return Varien_Object
     */
    public static function prepareSettingsObject()
    {
        $object = new Varien_Object();
        $objectData = array(
            'page_limit' => static::$_limitSize,
            'guest_only' => true,
            'time_limiter' => 60, // Days.
            'process_only_new' => false,
        );
        $object->setData($objectData);
        return $object;
    }

    /**
     * @param mixed|string $time
     * @return DateTime
     */
    public static function initTime($time = 'now')
    {
        $timezone = null;
        if (false) {
            $timezoneName = Mage::app()->getLocale()->date()->getTimezone();
            $timezone = new DateTimeZone($timezoneName);
        }
        return new DateTime($time, $timezone);
    }

    /**
     * Process method.
     */
    public static function run()
    {
        if (!Optimlight_AutoOrderAssign_Helper_Data::isEnabled()) {
            return ;
        }
        static::init(true);
        if (!static::$_enabled) {
            return ;
        }
        if (static::$_unsetLock) {
            Mage::app()->removeCache(self::LOCK);
        }
        if (self::checkLock()) {
            // TODO use $now for logging
            Optimlight_AutoOrderAssign_Helper_Logger::log('aoa_cron', 'Start cron...', Zend_Log::DEBUG);
            $tasks = static::getCronTasks();
            $settings = static::prepareSettingsObject();
            foreach ($tasks as $taskName => $enabled) {
                if ($enabled) {
                    static::runTask($taskName, $settings);
                }
            }
            Optimlight_AutoOrderAssign_Helper_Logger::log('aoa_cron', 'Cron finished.', Zend_Log::DEBUG);
            Mage::app()->removeCache(self::LOCK);
        }
    }

    /**
     * @return bool
     */
    public static function checkLock()
    {
        if ($time = Mage::app()->loadCache(self::LOCK)) {
            if ((time() - $time) < self::LOCKTIME) {
                Optimlight_AutoOrderAssign_Helper_Logger::log('aoa_cron', 'Cron is locked until ' . date('Y/m/d H:i:s', $time + self::LOCKTIME) . '.', Zend_Log::DEBUG);
                return false;
            }
        }
        Mage::app()->saveCache(time(), self::LOCK, array(), self::LOCKTIME);
        return true;
    }

    /**
     * @param string $taskClassName
     * @param Varien_Object $settings
     * @return bool|mixed
     */
    public static function runTask($taskClassName, $settings)
    {
        $result = false;
        if (class_exists($taskClassName)) {
            /** @var Optimlight_AutoOrderAssign_Model_Cron_Abstractum $taskObject */
            $taskObject = new $taskClassName();
            $lockKey = $taskClassName . '_lock';
            $lock = Mage::app()->loadCache($lockKey);
            if (true) {
            //if (is_a($taskObject, 'Optimlight_AutoOrderAssign_Model_Cron_Abstractum') && static::examineTime($taskObject) && !$lock) {
                $lockTime = round($taskObject->getFrequency(true) * 0.3) + 1;
                Mage::app()->saveCache(true, $lockKey, [], $lockTime);
                $settings = $taskObject->preProcessSettings($settings);
                try {
                    Optimlight_AutoOrderAssign_Helper_Logger::log('aoa_cron', 'Cron task [' . $taskClassName . '] started...', Zend_Log::DEBUG);
                    $result = $taskObject->execute($settings);
                    Optimlight_AutoOrderAssign_Helper_Logger::log('aoa_cron', 'Cron task [' . $taskClassName . '] finished.', Zend_Log::DEBUG);
                } catch (Exception $exception) {
                    Optimlight_AutoOrderAssign_Exception::catchException($exception);
                    Optimlight_AutoOrderAssign_Helper_Logger::log('aoa_cron', 'Cron task [' . $taskClassName . '] error: ' . $exception->getMessage() . '.', Zend_Log::ERR);
                }
                Mage::app()->removeCache($lockKey);
                $key = $taskObject->getKeyName();
                Mage::app()->saveCache(static::$_now->format('Y-m-d H:i:s'), $key, array('opt_aoa_cron'), 60*60*24*2);
            }
        }
        return $result;
    }

    /**
     * Return true if need to run.
     *
     * @param Optimlight_AutoOrderAssign_Model_Cron_Abstractum $taskObject
     * @return bool
     * @throws Exception
     */
    public static function examineTime($taskObject)
    {
        $timeDelta = $taskObject->getFrequency();
        if (is_numeric($timeDelta) || is_int($timeDelta)) {
            $timeInterval = new DateInterval('PT' . $timeDelta . 'M');
        } else {
            $timeInterval = new DateInterval($timeDelta);
        }
        $key = $taskObject->getKeyName();
        if ($time = Mage::app()->loadCache($key)) {
            try {
                $timeLastLock = static::initTime($time);
            } catch (Exception $exception) {
                Mage::app()->removeCache($key);
                return true;
            }
            $timeLastLock->add($timeInterval);
            if ($timeLastLock <= static::$_now) {
                return true;
            }
        } else {
            return true;
        }
        return false;
    }
}
