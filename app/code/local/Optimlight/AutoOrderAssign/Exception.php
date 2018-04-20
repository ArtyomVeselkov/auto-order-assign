<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Exception
 */

class Optimlight_AutoOrderAssign_Exception extends Mage_Exception
{

    /**
     * @param Exception $exception
     * @param int $type
     * @param array $data
     */
    public static function catchException($exception, $data = [])
    {
        $newException = new Optimlight_AutoOrderAssign_Exception(
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getPrevious()
        );
        $newException->exceptionalize($exception, $data);
    }

    /**
     * @param Exception $exception
     * @param int $type
     * @param array $data
     */
    public function exceptionalize($exception, $data = [])
    {
        $message = static::generateMessageFromException($exception);
        if (is_array($data)) {
            if (isset($data['*message*'])) {
                $message = $data['*message*'] . PHP_EOL . $message;
            }
        }
        Optimlight_AutoOrderAssign_Helper_Logger::log('aoa', $message);
    }

    /**
     * @param Exception $exception
     * @return string
     */
    public static function generateMessageFromException($exception)
    {
        $data = [
            'message' => $exception->getMessage(),
            'trace' => print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true)
        ];
        $contentMask = "MESSAGE: {{message}}\r\nTRACE: {{trace}}";
        return Optimlight_AutoOrderAssign_Helper_Data::replaceStringByMask($contentMask, $data);
    }
}