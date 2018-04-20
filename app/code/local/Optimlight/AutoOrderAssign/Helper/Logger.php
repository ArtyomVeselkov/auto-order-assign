<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Helper_Logger
 */

class Optimlight_AutoOrderAssign_Helper_Logger extends Mage_Core_Helper_Abstract
{
    /**
     * @var bool
     */
    public static $forceLog = false;
    /**
     * @var bool
     */
    public static $logDebug = true;

    /**
     * @param $component
     * @param $message
     * @param $type
     */
    public static function log($component, $message, $type = Zend_Log::DEBUG)
    {
        static::$logDebug = Optimlight_AutoOrderAssign_Helper_Data::isLogEnabled();
        if (Zend_Log::DEBUG === $type && !static::$logDebug) {
            return ;
        }
        $path = static::getFilePath($component);
        try {
            Mage::log($message, $type, $path, static::$forceLog);
        } catch (Exception $exception) {}
    }

    /**
     * @param $component
     * @return string
     */
    public static function getFilePath($component)
    {
        $pathDirectory = 'optimlight' . DS . date('Y-m-d');
        $subDirectory = true;
        $result = false;
        if (!@file_exists($pathDirectory)) {
            try {
                $result = mkdir($pathDirectory, 0777, true);
            } catch (Exception $exception) {
                $subDirectory = false;
            }
            if (!$result || !$subDirectory) {
                $pathDirectory = 'optimlight' . '--' . date('Y-m-d') . '--';
            } else {
                $pathDirectory = $pathDirectory . DS;
            }
        } else {
            $pathDirectory = $pathDirectory . DS;
        }
        return $pathDirectory . $component . '.log';
    }
}