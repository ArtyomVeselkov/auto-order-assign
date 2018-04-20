<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Model_Assign
 */

class Optimlight_AutoOrderAssign_Model_Assign extends Mage_Catalog_Model_Abstract
{
    /**
     * @param int|string|mixed $entityId
     * @param int $entityType
     * @param null $storeId
     * @return bool|Mage_Core_Model_Abstract|Mage_Customer_Model_Customer|Mage_Sales_Model_Order
     */
    public static function getInstanceBy($entityId, $entityType, $storeId = null)
    {
        switch ($entityType) {
            case Optimlight_AutoOrderAssign_Helper_Source::ENTITY_TYPE_CUSTOMER:
                if (Optimlight_AutoOrderAssign_Helper_Data::isValidEmail($entityId)) {
                    $model = static::getCustomerModelByEmail($entityId, $storeId);
                    break;
                }
            default:
                $model = Optimlight_AutoOrderAssign_Helper_Data::loadByType($entityId, $entityType, $storeId);
                break;
        }
        return $model;
    }

    /**
     * @param $customerEmail
     * @param int|null $storeId
     * @return Mage_Customer_Model_Customer|mixed
     */
    public static function getCustomerModelByEmail($customerEmail, $storeId = null)
    {
        /** @var Mage_Customer_Model_Customer $result */
        $result = Mage::getModel('customer/customer');
        $result = Optimlight_AutoOrderAssign_Helper_Data::loadCustomerByEmail($result, $customerEmail, $storeId);
        return $result;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param Varien_Object $settings
     * @param int|string $matched Customer's email or `entity_id`
     * @return bool
     */
    public function checkOrder($order, $settings, &$matched)
    {
        $result = false;
        $matched = false;
        $fields = [
            'guest_only' => true
        ];
        $settingsObject = new Varien_Object();
        if ($settings && is_a($settings, 'Varien_Object')) {
            /** @var Varien_Object $settings */
            /**
             * @var string $key
             * @var mixed $default
             */
            foreach ($fields as $key => $default) {
                if ($settings->hasData($key)) {
                    $settingsObject->setDataUsingMethod($key, $settings->getDataUsingMethod($key));
                } else {
                    $settingsObject->setDataUsingMethod($key, $default);
                }
            }
        }
        // TODO Check.
        $order = static::getInstanceBy($order, Optimlight_AutoOrderAssign_Helper_Source::ENTITY_TYPE_ORDER);
        if ($order && $order->getId()) {
            $email = false;
            // $isGuest = $order->getCustomerIsGuest(); // Can be true, but customer ID is not set.
            $isGuest = is_null($order->getCustomerId());
            if (!$isGuest) {
                if ($settingsObject->getData('guest_only')) {
                    Optimlight_AutoOrderAssign_Helper_Data::refreshReference($order->getId(), true);
                    return $result;
                } else {
                    $email = $order->getCustomerEmail();
                }
            } else {
                $email = $order->getCustomerEmail();
            }
            if ($email) {
                $customer = static::getInstanceBy(
                    $email,
                    Optimlight_AutoOrderAssign_Helper_Source::ENTITY_TYPE_CUSTOMER,
                    $order->getStoreId()
                );
                if ($customer && $customer->getId()) {
                    $result = $order->getCustomerId() != $customer->getId();
                    if ($result) {
                        $matched = $customer->getId();
                    }
                }
            }
        }
        try {
            Optimlight_AutoOrderAssign_Helper_Data::refreshReference($order->getId(), true);
        } catch (Exception $exception) {}
        Mage::dispatchEvent(
            Optimlight_AutoOrderAssign_Helper_Source::EVENT_ORDER_CHECK_AFTER,
            ['result' => $result, 'order' => $order, 'settings' => $settings, 'matched' => $matched]
        );
        return $result;
    }

    /**
     * @param Mage_Customer_Model_Customer $customer
     * @param Mage_Sales_Model_Order $order
     */
    public function setDefaultValues($customer, $order)
    {
        $fields = [
            'group_id' => 'customer_group_id',
            'entity_id' => null
        ];
        $defaults = [
            'group_id' => 0,
            'entity_id' => null
        ];
        foreach ($fields as $toCustomer => $fromOrder) {
            if (is_null($customer->getDataUsingMethod($toCustomer))) {
                if (!is_null($fromOrder)) {
                    $value = $order->getDataUsingMethod($fromOrder);
                } else {
                    $value = null;
                }
                if (is_null($value)) {
                    $value = @$defaults[$toCustomer];
                }
                $customer->setDataUsingMethod($toCustomer, $value);
            }
        }
    }

    /**
     * @param int|mixed $orderId
     * @param int|string $customerIdentifier
     * @param array $extra
     * @return Optimlight_AutoOrderAssign_Model_Pocket
     * @throws Varien_Exception
     */
    public function reassignOrder($orderId, $customerIdentifier, $extra = [])
    {
        $result = Optimlight_AutoOrderAssign_Model_Pocket::getInstance();
        $result->setResult('Warning.');
        $order = static::getInstanceBy($orderId, Optimlight_AutoOrderAssign_Helper_Source::ENTITY_TYPE_ORDER);
        if (!$order || !$order->getId()) {
            $result->appendError('Order ID: ' . $orderId . ' does not exist.');
            return $result;
        }
        $orderId = $order->getId(); // This line is important.
        $storeId = $order->getStoreId();
        $customerSource = static::getInstanceBy(
            $order->getCustomerId(),
            Optimlight_AutoOrderAssign_Helper_Source::ENTITY_TYPE_CUSTOMER,
            $storeId
        );
        if (!$customerSource || !$customerSource->getId()) {
            $customerSource = static::getInstanceBy(
                $order->getCustomerEmail(),
                Optimlight_AutoOrderAssign_Helper_Source::ENTITY_TYPE_CUSTOMER,
                $storeId
            );
            // TODO In order to log.
        }
        $customerTarget = static::getInstanceBy(
            $customerIdentifier,
            Optimlight_AutoOrderAssign_Helper_Source::ENTITY_TYPE_CUSTOMER,
            $storeId
        );
        if (!$customerTarget) {
            /** @var Mage_Customer_Model_Customer $customerTarget */
            $customerTarget = Mage::getModel('customer/customer');
            try {
                $customerTarget->setStoreId($storeId);
                if (Optimlight_AutoOrderAssign_Helper_Data::isValidEmail($customerIdentifier)) {
                    $customerTarget->setEmail($customerIdentifier);
                } else {
                    $result->appendError('Invalid customer identifier: such customer does not exist or specified email is not valid.');
                    return $result;
                }
            } catch (Exception $exception) {
                $result->appendError('Unable to load customer, error: ' . $exception->getMessage());
            }
        }
        if ($customerTarget->getEmail() == $order->getCustomerEmail() && $order->getCustomerEmail()) {
            /** As order can has the same email, but is not assigned to a registered customer. */
            // $result->appendError('The order is already assigned to the customer with such email/ID.');
            // return $result;
        }
        if ($customerTarget->getId() == $order->getCustomerId() && $order->getCustomerId()) {
            $result->appendError('The order is already assigned to the customer with such ID/email.');
            return $result;
        }
        if ($result->getResultFlag()) {
            $this->setDefaultValues($customerSource, $order);
            $this->setDefaultValues($customerTarget, $order);
            $result->setOrder($order);
            $result->setCustomerSource($customerSource);
            $result->setCustomerTarget($customerTarget);
            Mage::dispatchEvent(
                Optimlight_AutoOrderAssign_Helper_Source::EVENT_ASSIGN_ORDER_BEFORE, ['pocket' => $result]
            );
            if (!$this->transfer($result)) {
                $result->setResult('Reassignment was not successful.');
            } else {
                $reference = Optimlight_AutoOrderAssign_Helper_Data::getReference($orderId, true);
                $reference->setCustomerId($customerSource->getId());
                $reference->setCustomerEmail($customerSource->getEmail());
                $email = $customerTarget->getEmail();
                $customerId = $customerTarget->getId();
                $reference->replaceCustomer(
                    $customerId,
                    $email,
                    Optimlight_AutoOrderAssign_Helper_Source::REFERENCE_STATE_OK,
                    $extra
                );
                $result->setResult(
                    sprintf('The order was successfully reassigned to the customer `%s`, ID: %s.', $email, $customerId ? $customerId : '[guest]')
                );
                try {
                    $reference->save();
                } catch (Exception $exception) {
                    $result->appendError('Unable to save Reference object due to an error: ' . $exception->getMessage() .'.');
                }
            }
            Mage::dispatchEvent(
                Optimlight_AutoOrderAssign_Helper_Source::EVENT_ASSIGN_ORDER_AFTER, ['pocket' => $result]
            );
        }
        return $result;
    }

    /**
     * @param Optimlight_AutoOrderAssign_Model_Pocket $pocket
     * @return bool
     */
    protected function transfer($pocket)
    {
        $result = false;
        /** @var Varien_Object $data */
        $data = $pocket->pull(false);
        $this->start();
        try {
            if (!$this->exec($data)) {
                throw new Optimlight_AutoOrderAssign_Exception('Execution method returns FALSE as a result. It seems like some data about Order/Customer cannot be found in DB (to be replaced).');
            } else {
                $result = true;
                $this->finish();
            }
        } catch (Exception $exception) {
            Optimlight_AutoOrderAssign_Exception::catchException($exception);
            $pocket->appendError($exception->getMessage());
            $this->revert();
        }
        return $result;
    }

    /**
     * @param Varien_Object $data
     * @return bool
     * @throws Optimlight_AutoOrderAssign_Exception
     * @throws Zend_Db_Adapter_Exception
     */
    protected function exec($data)
    {
        $result = false;
        $rules = Optimlight_AutoOrderAssign_Helper_Data::getTransferRules();
        if (!is_array($rules) || !count($rules)) {
            // TODO Log exception.
            throw new Optimlight_AutoOrderAssign_Exception('Invalid transformation rules, refer to config.xml for more details.');
        }
        foreach ($rules as $table => $rule) {
            if (!is_array($rule)) {
                throw new Optimlight_AutoOrderAssign_Exception(
                    sprintf('Invalid columns are set for table `%s`, refer to config.xml for more details.', $table)
                );
            }
            $meta = isset($rule['@']) ? $rule['@'] : false;
            if (!$meta) {
                throw new Optimlight_AutoOrderAssign_Exception(
                    sprintf('Invalid attributes are set for table `%s`, refer to config.xml for more details.', $table)
                );
            }
            unset($rule['@']);
            $tableName = $meta['table'];
            $tableIdColumn = $meta['id'];
            $tableIdSource = $meta['key'];
            $tableSkip = isset($meta['skipnull']) ? $meta['skipnull'] : false;
            $tableLimit = isset($meta['limit']) ? $meta['limit'] : 1;
            $canBeZero = isset($meta['optional']) ? $meta['optional'] : false;
            if (in_array($tableLimit, ['false', '0'])) {
                $tableLimit = null;
            }
            $tableDataSource = [];
            $map = [];
            $tableIdValue = $data->getData($tableIdSource);
            if (!$tableIdValue) {
                throw new Optimlight_AutoOrderAssign_Exception(
                    sprintf('ID is not valid for table `%s`, column: `%s`, source key: `%s`.', $table, $tableIdColumn, $tableIdSource)
                );
            }
            $validationMap = [];
            foreach ($rule as $column => $source) {
                if (is_array($source)) {
                    if (isset($source['@'])) {
                        $validationMap[$column] = $source['@'];
                        $source = array_pop($source);
                    } else {
                        $source = array_pop($source);
                    }
                }
                $map[$source] = $column;
            }
            if (Optimlight_AutoOrderAssign_Helper_Data::copyFieldset($data, $tableDataSource, $map)) {
                foreach ($validationMap as $column => $validation) {
                    if (is_array($validation)) {
                        if (isset($validation['notnull']) && $validation['notnull']) {
                            if (!isset($tableDataSource[$column]) || !$tableDataSource[$column]) {
                                throw new Optimlight_AutoOrderAssign_Exception(
                                    sprintf('Invalid value for table `%s`, column: `%s`.', $table, $column)
                                );
                            }
                        }
                    }
                }
                $where = [$tableIdColumn => $tableIdValue];
                $orderBy = $tableIdColumn . ' DESC';
                $result = Optimlight_AutoOrderAssign_Helper_Direct::updateAdvanced(
                    $tableName,
                    $where,
                    $tableDataSource,
                    $orderBy,
                    $tableLimit
                );
                if (!$result && !$canBeZero) {
                    if (
                        !$result &&
                        $tableSkip &&
                        array_key_exists($tableSkip, $tableDataSource) &&
                        is_null($tableDataSource[$tableSkip])
                    ) {
                        // Situation is acceptable.
                    } else {
                        break;
                    }
                }
            }
            $result = true;
        }
        return $result;
    }

    /**
     *
     */
    protected function start()
    {
        $resource = Optimlight_AutoOrderAssign_Helper_Direct::getDbConnection('core_write');
        $resource->beginTransaction();
    }

    /**
     *
     */
    protected function finish()
    {
        $resource = Optimlight_AutoOrderAssign_Helper_Direct::getDbConnection('core_write');
        $resource->commit();
    }

    /**
     *
     */
    protected function revert()
    {
        $resource = Optimlight_AutoOrderAssign_Helper_Direct::getDbConnection('core_write');
        $resource->rollback();
    }
}