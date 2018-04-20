<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Model_Pocket
 */

/**
 * @method $this setResultFlag(bool $flat)
 * @method bool getResultFlag()
 * @method $this setResult(mixed $result)
 * @method mixed getResult()
 * @method $this setOrder(Mage_Sales_Model_Order $order)
 * @method Mage_Sales_Model_Order getOrder()
 * @method $this setCustomerSource(Mage_Customer_Model_Customer $customer)
 * @method Mage_Customer_Model_Customer getCustomerSource()
 * @method $this setCustomerTarget(Mage_Customer_Model_Customer $customer)
 * @method Mage_Customer_Model_Customer getCustomerTarget()
 */

class Optimlight_AutoOrderAssign_Model_Pocket extends Varien_Object
{
    /**
     * @var string[]
     */
    public $errors = [];

    /**
     * @param $errorMessage
     * @return $this
     */
    public function appendError($errorMessage)
    {
        $this->setResultFlag(false);
        $this->errors[] = $errorMessage;
        return $this;
    }

    /**
     * @param bool $asArray
     * @return Varien_Object
     */
    public function pull($asArray = false)
    {
        $fields = ['order', 'customer_source', 'customer_target'];
        $result = [];
        $object = new Varien_Object();
        foreach ($fields as $field) {
            if ($this->hasData($field)) {
                $buffer = $this->getDataUsingMethod($field);
                if ($buffer && is_a($buffer, 'Varien_Object')) {
                    $buffer = $buffer->getData();
                }
                $result[$field] = $buffer;
            }
        }
        if (!$asArray) {
            $object->setData($result);
            $result = $object;
        }
        return $result;
    }

    /**
     * @return Optimlight_AutoOrderAssign_Model_Pocket
     */
    public static function getInstance()
    {
        $result = new Optimlight_AutoOrderAssign_Model_Pocket();
        $result->setResultFlag(true);
        return $result;
    }
}