<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Optimlight_AutoOrderAssign_Helper_Direct
 */

class Optimlight_AutoOrderAssign_Helper_Direct
{
    /**
     * @var Mage_Core_Model_Resource
     */
    protected static $coreResource = null;
    /**
     * @var Varien_Db_Adapter_Pdo_Mysql
     */
    protected static $dbConnection = null;
    /**
     * @var null|array
     */
    protected static $_tables = null;

    /**
     * @return Mage_Core_Model_Resource
     */
    public static function getCoreResource()
    {
        if (is_null(static::$coreResource)) {
            static::$coreResource = Mage::getSingleton('core/resource');
        }
        return static::$coreResource;
    }

    /**
     * @param string $coreConnection
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    public static function getDbConnection($coreConnection = 'core_read')
    {
        if (is_null(static::$dbConnection)) {
            $coreResource = static::getCoreResource();
            if ($coreResource) {
                static::$dbConnection = $coreResource->getConnection($coreConnection);
            }
        }
        return static::$dbConnection;
    }

    /**
     * @param string $table
     * @return string
     */
    public static function getTableName($table)
    {
        if (!is_array(static::$_tables)) {
            $resource = static::getCoreResource();
            foreach (['sales/quote', 'sales/order', 'sales/order_grid', 'sales/order_address', 'sales/quote_address', 'sales/shipment'] as $model) {
                static::$_tables[$model] = $resource->getTableName($model);
            }
        }
        if (!isset(static::$_tables[$table])) {
            $resource = static::getCoreResource();
            static::$_tables[$table] = $resource->getTableName($table);
        }
        return static::$_tables[$table];
    }

    /**
     * @param string $table
     * @param array $where
     * @param array $data
     * @return bool
     */
    public static function update($table, $where, $data)
    {
        $result = false;
        $write = static::getDbConnection('write');
        $table = static::getTableName($table);
        if ($table && $where && $data) {
            $setData = [];
            foreach ($data as $key => $value) {
                $setData[$key . ' = ?'] = $value;
            }

            $count = $write->update($table, $where, $setData);
            $result = 0 < $count;
        }
        return $result;
    }

    /**
     * @param string $table
     * @param array $where
     * @param array $data
     * @param null|string $orderBy
     * @param null|int $limit
     * @return bool
     * @throws Zend_Db_Adapter_Exception
     */
    public static function updateAdvanced($table, $where, $data, $orderBy = null, $limit = 1)
    {
        $result = false;
        $write = static::getDbConnection('write');
        $table = static::getTableName($table);
        if ($table && $where && $data) {
            $whereSet = [];
            foreach ($where as $key => $value) {
                $whereSet[$key . ' = ?'] = $value;
            }
            $count = static::updateWith($write, $table, $data, $whereSet, $orderBy, $limit);
            $result = 0 < $count;
        }
        return $result;
    }

    /**
     * Updates table rows with specified data based on a WHERE clause.
     *
     * @param  Zend_Db_Adapter_Abstract $self
     * @param  mixed $table The table to update.
     * @param  array $bind Column-value pairs.
     * @param  mixed $where UPDATE WHERE clause(s).
     * @param  null|string $orderBy
     * @param  null|int $limit
     * @return int          The number of affected rows.
     * @throws Zend_Db_Adapter_Exception
     */
    public static function updateWith($self, $table, array $bind, $where = '', $orderBy = null, $limit = null)
    {
        /**
         * Build "col = ?" pairs for the statement,
         * except for Zend_Db_Expr which is treated literally.
         */
        $set = array();
        $i = 0;
        foreach ($bind as $col => $val) {
            if ($val instanceof Zend_Db_Expr) {
                $val = $val->__toString();
                unset($bind[$col]);
            } else {
                if ($self->supportsParameters('positional')) {
                    $val = '?';
                } else {
                    if ($self->supportsParameters('named')) {
                        unset($bind[$col]);
                        $bind[':col'.$i] = $val;
                        $val = ':col'.$i;
                        $i++;
                    } else {
                        /** @see Zend_Db_Adapter_Exception */
                        #require_once 'Zend/Db/Adapter/Exception.php';
                        throw new Zend_Db_Adapter_Exception(get_class($self) ." doesn't support positional or named binding");
                    }
                }
            }
            $set[] = $self->quoteIdentifier($col, true) . ' = ' . $val;
        }

        $where = static::whereExprWith($self, $where);

        /**
         * Build the UPDATE statement
         */
        $sql = "UPDATE "
            . $self->quoteIdentifier($table, true)
            . ' SET ' . implode(', ', $set)
            . (($where) ? " WHERE $where" : '')
            . (($orderBy) ? " ORDER BY $orderBy" : '')
            . (($limit) ? " LIMIT $limit" : '');

        /**
         * Execute the statement and return the number of affected rows
         */
        if ($self->supportsParameters('positional')) {
            $stmt = $self->query($sql, array_values($bind));
        } else {
            $stmt = $self->query($sql, $bind);
        }
        $result = $stmt->rowCount();
        return $result;
    }

    /**
     * Convert an array, string, or Zend_Db_Expr object
     * into a string to put in a WHERE clause.
     *
     * @param Zend_Db_Adapter_Abstract $self
     * @param mixed $where
     * @return string
     */
    public static function whereExprWith($self, $where)
    {
        if (empty($where)) {
            return $where;
        }
        if (!is_array($where)) {
            $where = array($where);
        }
        foreach ($where as $cond => &$term) {
            // is $cond an int? (i.e. Not a condition)
            if (is_int($cond)) {
                // $term is the full condition
                if ($term instanceof Zend_Db_Expr) {
                    $term = $term->__toString();
                }
            } else {
                // $cond is the condition with placeholder,
                // and $term is quoted into the condition
                $term = $self->quoteInto($cond, $term);
            }
            $term = '(' . $term . ')';
        }

        $where = implode(' AND ', $where);
        return $where;
    }

    /**
     * @param string $table
     * @param array $where
     * @return bool|int
     */
    public static function select($table, $where)
    {
        $result = false;
        $read = static::getDbConnection('read');
        $table = static::getTableName($table);
        if ($table) {
            $select = clone $read->select();
            $select->from($table);
            foreach ($where as $key => $value) {
                 $select->where($key . ' = ?', $value);
            }
            $data = $read->fetchAll($select);
            $result = $data;
        }
        return $result;
    }
}