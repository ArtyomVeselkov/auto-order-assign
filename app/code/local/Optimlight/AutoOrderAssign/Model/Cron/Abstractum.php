<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Model_Cron_Abstractum
 */

abstract class Optimlight_AutoOrderAssign_Model_Cron_Abstractum
{
    /**
     * Default is 1 minute
     *
     * @var int
     */
    protected static $_frequency = 1;
    /**
     * @var string
     */
    protected static $_keyName = 'opt_aoa_cron_lock_abstractum';
    /**
     * @var Optimlight_AutoOrderAssign_Model_Assign
     */
    protected static $_assign;
    /**
     * @var DateTime
     */
    protected $_now;

    /**
     * @param Varien_Object $settings
     * @return mixed
     */
    abstract public function execute($settings);

    /**
     * constructor.
     */
    public function __construct()
    {
        static::init();
        $this->_now = Optimlight_AutoOrderAssign_Model_Cron::initTime();
    }

    /**
     *
     */
    public static function init()
    {
        static::$_assign = Mage::getModel('opt_aoa/assign');
    }

    /**
     * @return string
     */
    public function getKeyName()
    {
        return static::$_keyName;
    }

    /**
     * @param Varien_Object $settings
     * @return Varien_Object
     */
    public function preProcessSettings($settings)
    {
        return $settings;
    }

    /**
     * In minutes.
     *
     * @param bool $returnSeconds
     * @return int
     * @throws Exception
     */
    public function getFrequency($returnSeconds = false)
    {
        $freq = static::$_frequency;
        if ($returnSeconds) {
            if (is_int($freq)) {
                $freq = $freq * 60;
            } else {
                if (!is_string($freq) && $freq <= 0) {
                    $freq = 60;
                } elseif (is_numeric($freq)) {
                    $freq = (int)$freq;
                }
                else {
                    $freq = new DateInterval($freq);
                    $freq = Optimlight_AutoOrderAssign_Helper_Data::dateIntervalToSeconds($freq);
                }
            }
        }
        return $freq;
    }
}