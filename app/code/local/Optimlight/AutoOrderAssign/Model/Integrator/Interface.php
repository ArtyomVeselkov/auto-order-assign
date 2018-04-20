<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Interface Optimlight_AutoOrderAssign_Model_Integrator_Interface
 */

interface Optimlight_AutoOrderAssign_Model_Integrator_Interface
{
    /**
     * @param Mage_Customer_Model_Customer $fromCustomer
     * @param Mage_Customer_Model_Customer $toCustomer
     * @param Mage_Sales_Model_Order $order
     * @return bool|null
     */
    public function transferRewardPoints($fromCustomer, $toCustomer, $order);

    /**
     * @param Mage_Customer_Model_Customer $fromCustomer
     * @param Mage_Customer_Model_Customer $toCustomer
     * @param Mage_Sales_Model_Order $order
     * @return bool|null
     */
    public function transferInvoiceRewardPoints($fromCustomer, $toCustomer, $order);
}