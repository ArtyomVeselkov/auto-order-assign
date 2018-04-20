<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/* @var $this Mage_Core_Model_Resource_Setup */
$installer = $this;

// Reference table.
$tableName = $installer->getTable('opt_aoa/reference');
if ($installer->tableExists($tableName)) {
    return ;
}

$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($tableName)
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, [
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ], 'Order Entity ID')
    ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, [
        'nullable'  => false
    ], 'Customer ID')
    ->addColumn('customer_email', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, [
        'nullable'  => false
    ], 'Email after changing')
    ->addColumn('history', Varien_Db_Ddl_Table::TYPE_BLOB, null, [
        'nullable'  => false
    ], 'Transformation Data')
    ->addColumn('state', Varien_Db_Ddl_Table::TYPE_SMALLINT, 5, [
        'nullable'  => false
    ], 'State of entry')
    ->addColumn('time', Varien_Db_Ddl_Table::TYPE_DATETIME, null, [
        'nullable'  => false
    ], 'Timestamp')
    ->addForeignKey($installer->getFkName('opt_aoa/reference', 'id', 'sales/order', 'entity_id'),
        'id', $installer->getTable('sales/order'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->setComment('AutoOrderAssign Reference Table');
$installer->getConnection()->createTable($table);

$installer->getConnection()->addKey(
    $installer->getTable($tableName),
    'IDX_OPT_AOA_REFERENCE_TABLE_TIME',
    ['time']
);

$installer->getConnection()->addKey(
    $installer->getTable($tableName),
    'IDX_OPT_AOA_REFERENCE_TABLE_ENTITY_CUSTOMER_ID',
    ['customer_id']
);

$installer->getConnection()->addKey(
    $installer->getTable($tableName),
    'IDX_OPT_AOA_REFERENCE_TABLE_ENTITY_ORDER_ID_CUSTOMER_ID',
    ['id', 'time']
);

$installer->endSetup();