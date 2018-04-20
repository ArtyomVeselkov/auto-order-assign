<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Model_Integrator_Abstractum
 */

abstract class Optimlight_AutoOrderAssign_Model_Integrator_Abstractum implements Optimlight_AutoOrderAssign_Model_Integrator_Interface
{
    const DEFAULT_DISABLED = null;

    /**
     * @var null|Mage_Core_Helper_Data
     */
    protected $_helper = null;
    /**
     * Quote convert object
     *
     * @var Mage_Sales_Model_Convert_Quote
     */
    protected $_quoteConverter;

    /**
     * Optimlight_AutoOrderAssign_Model_Integrator_Abstractum constructor.
     */
    public function __construct()
    {
        $this->_helper = Mage::helper('core');
        $this->_quoteConverter = Mage::getSingleton('sales/convert_quote');
        $this->setup();
    }

    /**
     * @return void
     */
    abstract  public function setup();

    /**
     * @param Mage_Customer_Model_Customer $fromCustomer
     * @param Mage_Customer_Model_Customer $toCustomer
     * @param Mage_Sales_Model_Order $order
     * @return bool|null
     */
    public function transferRewardPoints($fromCustomer, $toCustomer, $order)
    {
        return self::DEFAULT_DISABLED;
    }

    /**
     * @param Mage_Customer_Model_Customer $fromCustomer
     * @param Mage_Customer_Model_Customer $toCustomer
     * @param Mage_Sales_Model_Order $order
     * @return bool|null
     */
    public function transferInvoiceRewardPoints($fromCustomer, $toCustomer, $order)
    {
        return self::DEFAULT_DISABLED;
    }

    /**
     * @param string $module
     * @return bool
     */
    public function isEnabledModule($module)
    {
        return $this->_helper ? $this->_helper->isModuleEnabled($module) : false;
    }

    /**
     * @param array $args
     * @param string $eventName
     * @return Varien_Event_Observer
     */
    public function createEventObserver($args, $eventName = '_default_event_name')
    {
        $event = new Varien_Event($args);
        $event->setName($eventName);
        $observer = new Varien_Event_Observer();
        $observer->setData(['event' => $event]);
        $observer->addData($args);
        return $observer;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param Mage_Sales_Model_Order $order
     * @param bool $mask
     * @return bool
     */
    public function updateDirectOrderData($quote, $order, $mask = false)
    {
        $result = false;
        $isVirtual = $quote->isVirtual();
        $save = [];
        $address = $isVirtual ? $quote->getBillingAddress() : $quote->getShippingAddress();
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        $orderBillingAddress = $order->getBillingAddress();
        $orderShippingAddress = $order->getShippingAddress();
        $processStack = [
            'address' => [
                'source' => $address,
                'target' => $order,
                'fieldset' => 'sales_convert_quote_address',
                'aspect' => 'to_order',
                'table' => 'sales/order'
            ],
            'shipping' => [
                'source' => $shippingAddress,
                'target' => $orderShippingAddress,
                'fieldset' => 'sales_convert_quote_address',
                'aspect' => 'to_order_address',
                'table' => 'sales/order_address'
            ],
            'billing' => [
                'source' => $billingAddress,
                'target' => $orderBillingAddress,
                'fieldset' => 'sales_convert_quote_address',
                'aspect' => 'to_order_address',
                'table' => 'sales/order_address'
            ]
        ];
        $itemsBuffer = $quote->getAllItems();
        $items = [];
        foreach ($itemsBuffer as $item) {
            $items[$item->getId()] = $item;
        }
        $orderItems = $order->getAllItems();
        /** @var Mage_Sales_Model_Order_Item $orderItem */
        foreach ($orderItems as $orderItem) {
            $quoteItemId = $orderItem->getQuoteItemId();
            if ($quoteItemId && isset($items[$quoteItemId]) && $items[$quoteItemId]) {
                $processStack['quote_item_' . $quoteItemId] = [
                    'source' => $items[$quoteItemId],
                    'target' => $orderItem,
                    'mode' => 'quote_item'
                ];
            }
        }
        $buffer = true;
        foreach ($processStack as $itemKey => $data) {
            $mode = isset($data['mode']) ? $data['mode'] : false;
            $source = $data['source'];
            $target = $data['target'];
            $fieldset = isset($data['fieldset']) ? $data['fieldset'] : false;
            $aspect = isset($data['aspect']) ? $data['aspect'] : false;
            $table = isset($data['table']) ? $data['table'] : '';
            switch ($mode) {
                case 'quote_item':
                    $buffer = $this->copyOverQuote2OrderItem($source, $target, $save, $mask);
                    break;
                default:
                    $buffer = $this->copyOverGenericObject($source, $target, $fieldset, $aspect, $table, $save, $mask);
            }
            if (!$buffer) {
                break;
            }
        }
        if ($buffer && is_array($save) && count($save)) {
            foreach ($save as $index => $item) {
                $table = isset($item['table']) ? $item['table'] : false;
                $data = isset($item['data']) ? $item['data'] : false;
                $where = isset($item['where']) ? $item['where'] : false;
                $orderBy = false;
                $tableLimit = 1;
                if (!$table || !$data || !$where) {
                    continue ;
                }
                Optimlight_AutoOrderAssign_Helper_Direct::updateAdvanced(
                    $table,
                    $where,
                    $data,
                    $orderBy,
                    $tableLimit
                );
            }
        }
        $result = $buffer;
        return $result;
    }

    /**
     * @param Mage_Sales_Model_Quote_Item $source
     * @param Mage_Sales_Model_Order_Item $target
     * @param array $push
     * @param array $accumulate
     * @param bool|string $mask
     * @return bool
     */
    protected function copyOverQuote2OrderItem($source, &$target, &$push = [], $mask = false)
    {
        $result = false;
        if (!$source || !$target || !$target->getId()) {
            return $result;
        }
        $accumulate = [];
        $result = Optimlight_AutoOrderAssign_Helper_Data::copyFieldset($source, $target, 'sales_convert_quote_item' . '|' . 'to_order_item', $accumulate, $mask, true);
        if (!$source->getNoDiscount() && $result) {
            // Here result is not returned.
            Optimlight_AutoOrderAssign_Helper_Data::copyFieldset($source, $target, 'sales_convert_quote_item' . '|' . 'to_order_item_discount', $accumulate, $mask, true);
        }
        if ($result) {
            $push[] = [
                'table' => 'sales/order_item',
                'where' => [
                    'item_id' => $target->getId()
                ],
                'data' => $accumulate
            ];
        }
        return $result;
    }

    /**
     * @param Varien_Object $source
     * @param Varien_Object $target
     * @param string $fieldset
     * @param string $aspect
     * @param string $table
     * @param array $push
     * @param array $accumulate
     * @param bool|string $mask
     * @return bool
     */
    protected function copyOverGenericObject($source, &$target, $fieldset, $aspect, $table = '', &$push = [], $mask = false)
    {
        $result = false;
        if (!$source || !$target || !$target->getId()) {
            return $result;
        }
        $accumulate = [];
        if (Optimlight_AutoOrderAssign_Helper_Data::copyFieldset($source, $target, $fieldset . '|' . $aspect, $accumulate, $mask, true)) {
            $push[] = [
                'table' => $table,
                'where' => [
                    'entity_id' => $target->getId()
                ],
                'data' => $accumulate
            ];
            $result = true;
        }
        return $result;
    }
}