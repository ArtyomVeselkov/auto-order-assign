<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

error_reporting(E_ALL | E_STRICT);
chdir(dirname(__FILE__));

require_once 'app/Mage.php';
umask(0);
Mage::app();

Mage::getConfig()->init()->loadEventObservers('crontab');
Mage::app()->addEventArea('crontab');

$mode = 1;
$onlyNew = false;
$result = 'NOT EXECUTED!';

/** @var Optimlight_AutoOrderAssign_Model_Cron $cron */
$cron = Mage::getSingleton('opt_aoa/cron');

if (1 === $mode) {
    $result = Optimlight_AutoOrderAssign_Model_Cron::run(true);
} elseif (2 === $mode) {

}

var_dump($result);
