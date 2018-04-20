<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Model_Reference
 */

/**
 * @method $this setTime(mixed $time)
 * @method string getTime()
 * @method $this setHistory(array $history)
 * @method array getHistory()
 * @method $this setCustomerEmail(string $email)
 * @method string getCustomerEmail()
 * @method $this setCustomerId(int $customerId)
 * @method string getCustomerId()
 * @method $this setState(int $state)
 * @method int getState()
 */

class Optimlight_AutoOrderAssign_Model_Reference extends Mage_Core_Model_Abstract
{
    /**
     * @var null|mixed
     */
    protected $_lastResult = null;

    /**
     *
     */
    public function _construct()
    {
        $this->_init('opt_aoa/reference');
        $this->setState(Optimlight_AutoOrderAssign_Helper_Source::REFERENCE_STATE_ERROR);
    }

    /**
     * @param null|mixed $time
     * @param bool $saveAfter
     * @throws Exception
     */
    public function updateTime($time = null, $saveAfter = false)
    {
        if (is_null($time)) {
            $time = time();
        }
        $this->setTime($time);
        if ($saveAfter) {
            $this->save();
        }
    }

    /**
     * @param int $customerId
     * @param string $customerEmail
     * @param int $state
     * @param array $extra
     */
    public function replaceCustomer($customerId, $customerEmail, $state, $extra = [])
    {
        $this->setState($state);
        $extra['customer_id_new'] = $customerId;
        $extra['customer_email_new'] = $customerEmail;
        $this->appendHistory($extra);
        $this->setCustomerId($customerId);
        $this->setCustomerEmail($customerEmail);
    }

    /**
     * @param array $extra
     */
    public function appendHistory($extra = [])
    {
        $history = $this->getHistory();
        if (is_string($history)) {
            $history = Optimlight_AutoOrderAssign_Helper_Data::tryUnserialize($history);
        }
        if (!is_array($history)) {
            $history = [];
        }
        $removeEmpty = ['customer_id', 'customer_email'];
        foreach ($removeEmpty as $key ) {
            if (isset($extra[$key]) && !$extra[$key]) {
                unset($extra[$key]);
            }
        }
        $element = $this->exportCurrentHistory($extra);
        $history[] = $element;
        $this->setHistory($history);
    }

    /**
     * @param array $extra
     * @return array
     */
    public function exportCurrentHistory($extra = [])
    {
        $result = [
            'customer_id' => false,
            'customer_email' => false,
        ];
        foreach ($result as $key => &$value) {
            $value = $this->getDataUsingMethod($key);
        }
        $time = new DateTime();
        $result['time'] = $time->format('Y-m-d H:i:s');
        $result = array_merge($result, $extra);
        return $result;
    }

    /**
     * @param int $id
     * @return Optimlight_AutoOrderAssign_Model_Reference|null
     */
    public static function getInstanceById($id)
    {
        $result = null;
        /** @var Optimlight_AutoOrderAssign_Model_Reference $model */
        $model = Mage::getModel('opt_aoa/reference');
        $model->load($id);
        if ($model->getId() == $id) {
            $result = $model;
        }
        return $result;
    }

    /**
     * @param int $id
     * @throws Exception
     */
    public static function touchById($id)
    {
        $model = static::getInstanceById($id);
        if ($model) {
            $model->updateTime(null, true);
        }
    }

    /**
     * @param int $id
     * @return array|null
     */
    public static function getHistoryById($id)
    {
        $result = null;
        $model = static::getInstanceById($id);
        if ($model) {
            $result = $model->getHistory();
        }
        return $result;
    }
}