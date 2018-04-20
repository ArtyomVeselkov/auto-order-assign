<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Helper_Data
 */

class Optimlight_AutoOrderAssign_Helper_Data extends Mage_Core_Helper_Abstract
{
    const CONFIG_PATH_ENABLED = 'opt_aoa/general/enabled';
    const CONFIG_PATH_DISABLED_NOTIFICATION = 'opt_aoa/general/disabled_email_notification';
    const CONFIG_PATH_QUEUE_LIMIT = 'opt_aoa/general/queue_limit';
    const CONFIG_PATH_QUEUE_PAUSE = 'opt_aoa/general/queue_pause';
    const CONFIG_PATH_INTEGRATORS = 'opt_aoa/general/integrators';
    const CONFIG_PATH_ENABLED_CRON = 'opt_aoa/general/enabled_cron';
    const CONFIG_XML_PATH_TRANSFER_RULES = 'optimlight/aoa/transfer_rules';
    const CONFIG_XML_PATH_LOG = 'optimlight/aoa/log';
    const QUEUE_DEFAULT_LIMIT = 50; 
    const QUEUE_DEFAULT_PAUSE = 20; 
    
    /**
     * @var bool
     */
    protected static $_enabled = null;
    /**
     * @var bool
     */
    protected static $_emailNotification = null;
    /**
     * @var array
     */
    protected static $_integrators = null;
    /**
     * @var bool
     */
    protected static $_enabledCron = null;
    /**
     * @var int
     */
    protected static $_queueLimit = null;
    /**
     * @var int
     */
    protected static $_queuePause = null;
    /**
     * @var null|array
     */
    protected static $_transferRules = null;
    /**
     * @var null|Mage_Sales_Model_Order
     */
    protected static $_currentOrder = null;
    /**
     * @var bool
     */
    protected static $_logging = null;

    /**
     * @return bool
     */
    public static function isEnabled()
    {
        if (is_null(static::$_enabled)) {
            static::$_enabled = (int)Mage::getStoreConfig(self::CONFIG_PATH_ENABLED);
        }
        return (bool)static::$_enabled;
    }

    /**
     * @return bool
     */
    public static function isEnabledEmailNotification()
    {
        if (is_null(static::$_emailNotification)) {
            static::$_emailNotification = !(int)Mage::getStoreConfig(self::CONFIG_PATH_DISABLED_NOTIFICATION);
        }
        return (bool)static::$_emailNotification;
    }

    /**
     * @return array
     */
    public static function getIntegratorsList()
    {
        if (is_null(static::$_integrators)) {
            $buffer = Mage::getStoreConfig(self::CONFIG_PATH_INTEGRATORS);
            static::$_integrators = is_string($buffer) ? explode(',', $buffer) : [];
        }
        return static::$_integrators;
    }


    /**
     * @return bool
     */
    public static function isLogEnabled()
    {
        if (is_null(static::$_logging)) {
            /** @var Mage_Core_Model_Config $config */
            $config = Mage::app()->getConfig();
            $buffer = $config->getNode(self::CONFIG_XML_PATH_LOG);
            if ($buffer) {
                $buffer = (int)$buffer;
            }
            static::$_logging = $buffer;
        }
        return (bool)static::$_logging;
    }

    /**
     * @return bool
     */
    public static function isCronEnabled()
    {
        if (is_null(static::$_enabledCron)) {
            static::$_enabledCron = (int)Mage::getStoreConfig(self::CONFIG_PATH_ENABLED) && Mage::getStoreConfig(self::CONFIG_PATH_ENABLED_CRON);
        }
        return (bool)static::$_enabledCron;
    }
    
    /**
     * @return int
     */
    public static function getQueueLimit()
    {
        if (is_null(static::$_queueLimit)) {
            $buffer = Mage::getStoreConfig(self::CONFIG_PATH_QUEUE_LIMIT);
            if (is_null($buffer) || !$buffer) {
                $buffer = static::CONFIG_PATH_QUEUE_LIMIT;
            }
            static::$_queueLimit = $buffer;
        }
        return static::$_queueLimit;
    }

    /**
     * @return int
     */
    public static function getQueuePause()
    {
        if (is_null(static::$_queuePause)) {
            $buffer = Mage::getStoreConfig(self::CONFIG_PATH_QUEUE_PAUSE);
            if (is_null($buffer) || !$buffer) {
                $buffer = static::CONFIG_PATH_QUEUE_PAUSE;
            }
            static::$_queuePause = $buffer;
        }
        return static::$_queuePause;
    }

    /**
     * @return array|null
     */
    public static function getTransferRules()
    {
        if (!is_array(static::$_transferRules)) {
            /** @var Mage_Core_Model_Config $config */
            $config = Mage::app()->getConfig();
            $buffer = $config->getNode(self::CONFIG_XML_PATH_TRANSFER_RULES);
            if ($buffer) {
                $buffer = $buffer->asArray();
            } else {
                $buffer = [];
            }
            static::$_transferRules = $buffer;
        }
        return static::$_transferRules;
    }

    /**
     * @param array|Varien_Object $source
     * @param array|Varien_Object $target
     * @param array|string $map
     * @param array $accumulate
     * @param bool $mask
     * @param bool $onlyOnNull
     * @return bool
     */
    public static function copyFieldset($source, &$target, $map, &$accumulate = [], $mask = false, $onlyOnNull = false)
    {
        if (!(is_array($source) || $source instanceof Varien_Object)
            || !(is_array($target) || $target instanceof Varien_Object)) {

            return false;
        }
        if (!$map) {
            return false;
        }

        $sourceIsArray = is_array($source);
        $targetIsArray = is_array($target);

        $result = false;
        if (is_string($map)) {
            $parts = explode('|', $map);
            if (2 > count($parts)) {
                return false;
            }
            $map = [];
            $fieldset = $parts[0];
            $aspect = $parts[1];
            $fields = Mage::getConfig()->getFieldset($fieldset, 'global');
            if (!$fields) {
                return false;
            }
            foreach ($fields as $code => $node) {
                if (!empty($node->$aspect)) {
                    $targetCode = (string)$node->$aspect;
                    $targetCode = $targetCode == '*' ? $code : $targetCode;
                    $map[$code] = $targetCode;
                }
            }
        }
        foreach ($map as $from => $to) {
            if (!$to) {
                continue;
            }

            if ($sourceIsArray) {
                $value = isset($source[$from]) ? $source[$from] : null;
            } else {
                $value = $source->getDataUsingMethod($from);
            }

            $targetCode = (string)$to;
            $targetCode = $targetCode == '*' ? $from : $targetCode;

            $result = true;
            if ($mask && !preg_match($mask, $targetCode)) {
                continue;
            }

            if ($targetIsArray) {
                if (!isset($target[$targetCode]) || $target[$targetCode] != $value) {
                    $accumulate[$targetCode] = $value;
                }
                $target[$targetCode] = $value;
            } else {
                if ($onlyOnNull && is_null($value) && !is_null($target->getDataUsingMethod($targetCode))) {
                    continue;
                }
                if (!$target->hasData($targetCode) || $target->getDataUsingMethod($targetCode) != $value) {
                    $accumulate[$targetCode] = $value;
                }
                $target->setDataUsingMethod($targetCode, $value);
            }
        }
        return $result;
    }

    /**
     * @param $string
     * @param $array
     * @param string $openBracket
     * @param string $closeBracket
     * @return null|string|string[]
     */
    public static function replaceStringByMask($string, $array, $openBracket = '{{', $closeBracket = '}}')
    {
        // Safe return.
        if (!is_string($string)) {
            return '';
        }
        // Anonymous functions become available after PHP 5.3
        return preg_replace_callback('/'. $openBracket. '(.*?)' . $closeBracket . '/', function ($fields) use ($array) {
            return isset($array[$fields[1]]) ? $array[$fields[1]] : 'null';
        }, $string);
    }

    /**
     * @param int $entityId
     * @param int $entityType
     * @param null $storeId
     * @return bool|Mage_Core_Model_Abstract|Mage_Sales_Model_Order
     */
    public static function loadByType($entityId, $entityType, $storeId = null)
    {
        $result = false;
        $proceedFlag = true;
        switch ($entityType) {
            case Optimlight_AutoOrderAssign_Helper_Source::ENTITY_TYPE_CUSTOMER:
                if (static::isCustomerObject($entityId)) {
                    $result = $entityId;
                    $proceedFlag = false;
                } else {
                    /** @var Mage_Customer_Model_Customer $result */
                    $result = static::getCustomerById($entityId);
                }
                break;
            case Optimlight_AutoOrderAssign_Helper_Source::ENTITY_TYPE_ORDER:
                if (static::isOrderObject($entityId)) {
                    $result = $entityId;
                    $proceedFlag = false;
                } else {
                    /** @var Mage_Sales_Model_Order $result */
                    $result = static::getOrderById($entityId);
                }
                break;
        }
        if ($proceedFlag && $result && $entityId && is_a($result, 'Mage_Catalog_Model_Abstract')) {
            if ($storeId) {
                $result->setStoreId($storeId);
            }
            if (!$result->getId()) {
                $result->load($entityId);
            }
        }
        return $result;
    }

    /**
     * @param $url
     * @param $id
     * @return mixed
     */
    public static function getAdminhtmlLocationUrl($url, $id)
    {
        return Mage::helper('adminhtml')->getUrl($url, array('_secure' => true, 'id' => $id));
    }

    /**
     * @param string[] $handles
     * @return bool
     */
    public static function checkHandle($handles)
    {
        if ((is_array($handles) && 0 == count($handles)) || false === $handles) {
            return true;
        } elseif (!is_array($handles)) {
            $handles = array($handles);
        }
        $actualHandles = @Mage::app()->getLayout()->getUpdate()->getHandles();
        $intersect = array_intersect($actualHandles, $handles);
        return 0 < count($intersect);
    }

    /**
     * @param int|null $storeId
     * @return int|null
     */
    public static function getCurrentStoreId($storeId = null)
    {
        if (!is_null($storeId)) {
            $store = @Mage::app()->getStore();
            $storeId = $store ? $store->getId() : null;
        }
        return $storeId;
    }

    /**
     * @param Mage_Customer_Model_Customer $customer
     * @param string $email
     * @param bool $storeId
     * @param bool $alwaysStore
     * @return mixed
     */
    public static function loadCustomerByEmail($customer, $email, $storeId = false, $alwaysStore = true)
    {
        if (true) {
            $store = null;
            if ($storeId) {
                try {
                    $store = Mage::getModel('core/store')->load($storeId);
                } catch (\Exception $exception) {
                    $store = null;
                }
            }
            if (!$store) {
                if ($alwaysStore && Mage::app()->getStore()->isAdmin()) {
                    $store = Mage::app()->getDefaultStoreView();
                } else {
                    $store = Mage::app()->getStore();
                }
            }
            if ($store) {
                $websiteId = $store->getWebsiteId();
                $customer->setData('website_id', $websiteId);
            }
        }
        try {
            $customer->loadByEmail($email);
            $customer->setEmail($email);
        } catch (\Exception $exception) {}
        return $customer;
    }

    /**
     * @param string $email
     * @return bool
     */
    public static function isValidEmail($email)
    {
        return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @param $dateInterval
     * @return int
     */
    public static function dateIntervalToSeconds($dateInterval)
    {
        $reference = new DateTime();
        $endTime = $reference->add($dateInterval);

        return $endTime->getTimestamp() - $reference->getTimestamp();
    }


    /**
     * @param int $orderId
     * @return Mage_Core_Model_Abstract|Mage_Sales_Model_Order
     */
    public static function getOrderById($orderId)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        if (is_string($orderId) && 0 === strpos($orderId, '#')) {
            $order->loadByIncrementId(ltrim($orderId, '#'));
        } else {
            $order->load($orderId);
        }
        return $order;
    }

    /**
     * @param int $customerId
     * @return Mage_Core_Model_Abstract|Mage_Customer_Model_Customer
     */
    public static function getCustomerById($customerId)
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer');
        $customer->load($customerId);
        return $customer;
    }

    /**
     * @param mixed $orderObject
     * @return bool
     */
    public static function isOrderObject($orderObject)
    {
        return is_object($orderObject) && is_a($orderObject, 'Mage_Sales_Model_Order');
    }

    /**
     * @param mixed $customerObject
     * @return bool
     */
    public static function isCustomerObject($customerObject)
    {
        return is_object($customerObject) && is_a($customerObject, 'Mage_Customer_Model_Customer');
    }

    /**
     * @param int $orderId
     * @param bool $alwaysObject
     * @return false|Mage_Core_Model_Abstract
     */
    public static function refreshReference($orderId, $alwaysObject = false)
    {
        $result = Optimlight_AutoOrderAssign_Model_Reference::touchById($orderId);
        if (!$result && $alwaysObject) {
            $result = Mage::getModel('opt_aoa/reference');
            if ($orderId) {
                $result->setId($orderId);
                $result->save();
            }
        }
        return $result;
    }

    /**
     * @param int $orderId
     * @param bool $alwaysObject
     * @return Optimlight_AutoOrderAssign_Model_Reference|null
     */
    public static function getReference($orderId, $alwaysObject = false)
    {
        $result = Optimlight_AutoOrderAssign_Model_Reference::getInstanceById($orderId);
        if (!$result && $alwaysObject) {
            $result = Mage::getModel('opt_aoa/reference');
            if ($orderId) {
                $result->setId($orderId);
            }
        }
        return $result;
    }

    /**
     * @param int $orderId
     * @param bool $formatFlag
     * @return array|null
     */
    public static function getReferenceHistory($orderId, $formatFlag = false)
    {
        $object = Optimlight_AutoOrderAssign_Model_Reference::getHistoryById($orderId);
        if ($formatFlag) {
            $result = '';
            if (is_object($object)) {
                $object = (array)$object;
            }
            $buffer = [];
            foreach ($object as $index => $data) {
                if (!is_array($data)) {
                    continue;
                }
                $mask = '%d) Reassigned from `%s` [ID: %s] to `%s` [ID: %s]. Time: %s';
                $buffer[] = sprintf(
                    $mask,
                    $index + 1,
                    @$data['customer_email'],
                    @$data['customer_id'] ? @$data['customer_id'] : 'GUEST',
                    @$data['customer_email_new'],
                    @$data['customer_id_new'] ? @$data['customer_id_new'] : 'GUEST',
                    @$data['time']
                );
            }
            arsort($buffer);
            $result = implode("\n", $buffer);

        } else {
            $result = $object;
        }
        return $result;
    }

    /**
     * @param mixed $data
     * @param null|mixed $default
     * @return mixed|null
     */
    public static function tryJsonDecode($data, $default = null)
    {
        $result = $default;
        try {
            $result = Zend_Json_Decoder::decode($data, true);
        } catch (Exception $exception) {}
        return $result;
    }

    /**
     * @param mixed $data
     * @param null|mixed $default
     * @return mixed|null
     */
    public static function tryUnserialize($data, $default = null)
    {
        $result = $default;
        try {
            /** @var Mage_Core_Helper_UnserializeArray $helper */
            $helper = Mage::helper('core/unserializeArray');
            $result = $helper->unserialize($data);
        } catch (Exception $exception) {}
        return $result;
    }

    /**
     * @return Mage_Sales_Model_Order|null
     */
    public static function getCurrentOrder() {
        if (is_null(static::$_currentOrder)) {
            if (Mage::registry('current_order')) {
                $order = Mage::registry('current_order');
            }
            elseif (Mage::registry('order')) {
                $order = Mage::registry('order');
            }
            else {
                $order = new Varien_Object();
            }
            static::$_currentOrder = $order;
        }
        return static::$_currentOrder;
    }
}