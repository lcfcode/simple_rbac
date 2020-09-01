<?php

/**
 * @author LCF
 * @date 2019/3/13 14:25
 * @version 2.0.4
 * 来源
 * @link https://gitee.com/lcfcode/linker
 * @link https://github.com/lcfcode/linker
 */

namespace authmng\utils;
/**
 * Class MysqliClass
 * @package Syslib\utils
 * 原本设计保留单例，现在也满足单例情况，需要自行改写
 */
class MysqliClass implements DriveInterface
{
    private $_connect = null;
    private $_keys = '';
    private $_values = '';
    private $_bindType = '';
    private $_wheres = '';
    private $_orWheres = '';
    private $_bindValue = [];
    private $_sql = '';
    private $_sqlParameter;

    private static $instances = [];
    private static $instancesKey = '';

    /**
     * @param $config
     * @return DriveInterface
     * @author LCF
     * @date
     */
    public static function getInstance($config)
    {
        return new self($config);//兼容swoole
        self::$instancesKey = $config['host'] . ':' . $config['port'] . ':' . $config['user'] . ':' . $config['database'];
        if (isset(self::$instances[self::$instancesKey])) {
            return self::$instances[self::$instancesKey];
        }
        self::$instances[self::$instancesKey] = new self($config);
        return self::$instances[self::$instancesKey];
    }

    public function __construct($config)
    {
        if (isset($config['db.obj']) && ($config['db.obj'] instanceof \mysqli)) {
            $conn = $config['db.obj'];
        } else {
            $conn = new \mysqli($config['host'], $config['user'], $config['password'], $config['database'], $config['port']);
            $conn->set_charset($config['charset']);
        }
        $this->_connect = $conn;
    }

    /**
     * @param $table
     * @param $data
     * @return mixed
     * @author LCF
     * @date
     * 数据插入
     */
    public function insert($table, $data)
    {
        $sql = 'insert into ' . $table;
        $this->clear();
        $this->iBand($data);
        $sql .= ' (' . $this->_keys . ') values (' . $this->_values . ')';
        $args[] = $this->_bindType;
        $parameter = array_merge($args, $this->_bindValue);
        $stmt = $this->_prepare($sql, $parameter);
        call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        $stmt->execute();
        if ($stmt->errno) {
            throw new \Exception('MysqliClass::insert exception , message : ' . $stmt->error, $stmt->errno);
        }
        $affectedRows = $stmt->affected_rows;
        $stmt->free_result();
        $stmt->close();
        $this->clear();
        if ($affectedRows > 0) {
            return $affectedRows;
        }
        return false;
    }

    /**
     * @param $table
     * @param $data
     * @param $where
     * @return mixed
     * @author LCF
     * @date
     * 数据更新
     */
    public function update($table, $data, $where)
    {
        $sql = 'update ' . $table . ' set ';
        $this->clear();
        $this->uBand($data);
        $sql .= ' ' . $this->_keys . ' where ';
        $this->_and($where);
        $args[] = $this->_bindType;
        $sql .= ' ' . $this->_wheres;
        $parameter = array_merge($args, $this->_bindValue);
        $stmt = $this->_prepare($sql, $parameter);
        call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        $stmt->execute();
        if ($stmt->errno) {
            throw new \Exception('MysqliClass::update exception , message : ' . $stmt->error, $stmt->errno);
        }
        $affectedRows = $stmt->affected_rows;
        $stmt->free_result();
        $stmt->close();
        $this->clear();
        if ($affectedRows > 0) {
            return $affectedRows;
        }
        return false;
    }

    /**
     * @param $table
     * @param $where
     * @return mixed
     * @author LCF
     * @date
     * 删除数据
     */
    public function delete($table, $where)
    {
        $sql = 'delete from ' . $table;
        $this->clear();
        $this->_and($where);
        $sql .= ' where ' . $this->_wheres;
        $args[] = $this->_bindType;
        $parameter = array_merge($args, $this->_bindValue);
        $stmt = $this->_prepare($sql, $parameter);
        call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        $stmt->execute();
        if ($stmt->errno) {
            throw new \Exception('MysqliClass::delete exception , message : ' . $stmt->error, $stmt->errno);
        }
        $affectedRows = $stmt->affected_rows;
        $stmt->free_result();
        $stmt->close();
        if ($affectedRows > 0) {
            return $affectedRows;
        }
        return false;
    }

    /**
     * @param $table
     * @param $where
     * @param array $order
     * @param array $getInfo
     * @return mixed
     * @author LCF
     * @date
     * 根据条件查询单条数据
     */
    public function selectOne($table, $where, $order = [], $getInfo = ['*'])
    {
        $sql = 'select ' . implode(',', $getInfo) . ' from ' . $table;
        $this->clear();
        $this->_and($where);
        $sql .= ' where ' . $this->_wheres;
        if (!empty($order)) {
            $sql .= ' order by ' . $this->_order($order);
        }
        $sql .= ' limit 1';
        $args[] = $this->_bindType;
        $parameter = array_merge($args, $this->_bindValue);
        $stmt = $this->_prepare($sql, $parameter);
        call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        $stmt->execute();
        $returnData = $this->_dynamicBindResults($stmt);
        $stmt->free_result();
        $stmt->close();
        $this->clear();
        if ($returnData) {
            return $returnData[0];
        }
        return [];
    }

    /**
     * @param $table
     * @param array $order
     * @param int $offset
     * @param int $fetchNum
     * @param array $getInfo
     * @return mixed
     * @author LCF
     * @date
     * 查询所有数据
     */
    public function selectAll($table, $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*'])
    {
        $sql = 'select ' . implode(',', $getInfo) . ' from ' . $table;
        if (!empty($order)) {
            $sql .= ' order by ' . $this->_order($order);
        }
        if ($fetchNum > 0 && $offset > 0) {
            $offset = ($offset - 1) * $fetchNum;
            $sql .= ' limit ' . $offset . ',' . $fetchNum;
        }
        $stmt = $this->_prepare($sql);
        $stmt->execute();
        $returnData = $this->_dynamicBindResults($stmt);
        $stmt->free_result();
        $stmt->close();
        return $returnData;
    }

    /**
     * @param $table
     * @param array $where
     * @param array $order
     * @param int $offset
     * @param int $fetchNum
     * @param array $getInfo
     * @return mixed
     * @author LCF
     * @date
     * 查询所有数据，添加条件
     */
    public function selects($table, $where = [], $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*'])
    {
        $sql = 'select ' . implode(',', $getInfo) . ' from ' . $table;
        if (!empty($where)) {
            $this->clear();
            $this->_and($where);
            $sql .= ' where ' . $this->_wheres;
        }
        if (!empty($order)) {
            $sql .= ' order by ' . $this->_order($order);
        }
        if ($fetchNum > 0 && $offset > 0) {
            $offset = ($offset - 1) * $fetchNum;
            $sql .= ' limit ' . $offset . ',' . $fetchNum;
        }
        if (empty($this->_bindValue)) {
            $stmt = $this->_prepare($sql);
        } else {
            $args[] = $this->_bindType;
            $parameter = array_merge($args, $this->_bindValue);
            $stmt = $this->_prepare($sql, $parameter);
            call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        }
        $stmt->execute();
        $returnData = $this->_dynamicBindResults($stmt);
        $stmt->free_result();
        $stmt->close();
        $this->clear();
        return $returnData;
    }

    /**
     * @param $table
     * @param $field
     * @param $inWhere
     * @param array $where
     * @param array $order
     * @param int $offset
     * @param int $fetchNum
     * @param array $getInfo
     * @return mixed
     * @author LCF
     * @date
     * whereIn操作
     */
    public function selectIn($table, $field, $inWhere, $where = [], $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*'])
    {
        $sql = 'select ' . implode(',', $getInfo) . ' from ' . $table;
        $inStr = '';
        foreach ($inWhere as $value) {
            $inStr .= ",'{$value}'";
        }
        $inStr = trim($inStr, ',');
        $sql .= ' where ' . $field . ' in (' . $inStr . ') ';
        if (!empty($where)) {
            $this->clear();
            $this->_and($where);
            $sql .= ' and ' . $this->_wheres;
        }
        if (!empty($order)) {
            $sql .= ' order by ' . $this->_order($order);
        }
        if ($fetchNum > 0 && $offset > 0) {
            $offset = ($offset - 1) * $fetchNum;
            $sql .= ' limit ' . $offset . ',' . $fetchNum;
        }
        if (empty($this->_bindValue)) {
            $stmt = $this->_prepare($sql);
        } else {
            $args[] = $this->_bindType;
            $parameter = array_merge($args, $this->_bindValue);
            $stmt = $this->_prepare($sql, $parameter);
            call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        }
        $stmt->execute();
        $returnData = $this->_dynamicBindResults($stmt);
        $stmt->free_result();
        $stmt->close();
        $this->clear();
        return $returnData;
    }

    /**
     * @param $table
     * @param array $where
     * @param string $columnName
     * @param bool $distinct
     * @return mixed
     * @author LCF
     * @date
     * 统计数量
     */
    public function count($table, $where = [], $columnName = '*', $distinct = false)
    {
        if ($distinct) {
            $sql = "select count( distinct " . $columnName . ") as count from " . $table;
        } else {
            $sql = "select count(" . $columnName . ") as count from " . $table;
        }
        $returnData = $this->_group($sql, $where);
        return $returnData[0]['count'];
    }

    /**
     * @param $table
     * @param $multiInsertData
     * @param array $keys
     * @return bool|int
     * @author LCF
     * @date 2019/8/17 21:36
     * 多条语句执行插入方法
     */
    public function insertMultiple($table, $multiInsertData, $keys = [])
    {
        $sql = 'insert into ' . $table;
        $keyArr = [];
        $valueArr = [];
        $bindType = '';
        $sqlTemp = '';
        $index = 0;
        foreach ($multiInsertData as $data) {
            if (empty($data)) {
                continue;
            }
            $tmpArr = [];
            foreach ($data as $key => $value) {
                if ($index == 0) {
                    $keyArr[] = $key;
                }
                $tmpArr[] = '?';
                $valueArr[] =& $data[$key];
                $bindType .= $this->_determineType($value);
            }
            $values = implode(',', $tmpArr);
            $sqlTemp .= '(' . $values . '),';
            $index++;
        }
        $sqlTemp = rtrim($sqlTemp, ',');
        if (empty($keys)) {
            $keys = implode(',', $keyArr);
        }
        $sql .= ' (' . $keys . ') values ' . $sqlTemp;
        $bindValue = $valueArr;
        $args[] = $bindType;
        $parameter = array_merge($args, $bindValue);
        $stmt = $this->_prepare($sql, $parameter);
        call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->free_result();
        $stmt->close();
        $this->clear();
        if ($affectedRows > 0) {
            return $affectedRows;
        }
        return false;
    }

    public function close()
    {
        if ($this->_connect) {
            $this->_connect->close();
            $this->_connect = null;
        }
        if (isset(self::$instances[self::$instancesKey])) {
            unset(self::$instances[self::$instancesKey]);
        }
    }

    //////////////////////////////////////////////////////////////////
    /// 以下是私有函数
    //////////////////////////////////////////////////////////////////
    private function _group($sql, $where = [])
    {
        if (!empty($where)) {
            $this->clear();
            $this->_and($where);
            $sql .= ' where ' . $this->_wheres;
        }
        if (empty($this->_bindValue)) {
            $stmt = $this->_prepare($sql);
        } else {
            $args[] = $this->_bindType;
            $parameter = array_merge($args, $this->_bindValue);
            $stmt = $this->_prepare($sql, $parameter);
            call_user_func_array([$stmt, 'bind_param'], self::refValues($parameter));
        }
        $stmt->execute();
        $returnData = $this->_dynamicBindResults($stmt);
        $stmt->free_result();
        $stmt->close();
        $this->clear();
        return $returnData;
    }

    private function clear()
    {
        $this->_keys = '';
        $this->_values = '';
        $this->_bindType = '';
        $this->_wheres = '';
        $this->_orWheres = '';
        $this->_bindValue = [];
    }

    private function iBand($data)
    {
        $keyArr = [];
        $tmpArr = [];
        $valueArr = [];
        foreach ($data as $key => $value) {
            $keyArr[] = $key;
            $tmpArr[] = '?';
            $valueArr[] =& $data[$key];
            $this->_bindType .= $this->_determineType($value);
        }
        $this->_keys = implode(',', $keyArr);
        $this->_values = implode(',', $tmpArr);
        $this->_bindValue = $valueArr;
        return true;
    }

    private function _determineType($dataType)
    {
        switch (gettype($dataType)) {
            case 'NULL':
            case 'string':
                return 's';
                break;
            case 'boolean':
            case 'integer':
                return 'i';
                break;
            case 'blob':
                return 'b';
                break;
            case 'double':
                return 'd';
                break;
        }
        return trigger_error('MysqliClass::_determineType exception , message : data type exception!', E_USER_ERROR);
    }

    /**
     * @param $sql
     * @param null $parameter
     * @return \mysqli_stmt
     * @author LCF
     * @date 2019/9/24 9:57
     */
    private function _prepare($sql, $parameter = null)
    {
        $this->_sql = $sql;
        $this->_sqlParameter = $parameter;
        $stmt = $this->_connect->prepare($sql);
        if (!$stmt) {
            $msg = $this->_connect->error . " --SQL: " . $sql;
            trigger_error('MysqliClass::_prepare exception , message : ' . $msg, E_USER_ERROR);
        }
        return $stmt;
    }

    private function refValues($data)
    {
        $refs = [];
        foreach ($data as $key => $value) {
            $refs[] =& $data[$key];
        }
        return $refs;
    }

    private function uBand($data)
    {
        $keyArr = [];
        $valueArr = [];
        foreach ($data as $key => $value) {
            $keyArr[] = $key . '=? ';
            $valueArr[] =& $data[$key];
            $this->_bindType .= $this->_determineType($value);
        }
        $this->_keys = implode(',', $keyArr);
        $this->_bindValue = $valueArr;
        return true;
    }

    private function _and($where)
    {
        $whereValueArr = [];
        $strTmp = '';
        foreach ($where as $keys => $values) {
            if (!strpos($keys, '::')) {
                $strTmp .= ' and ' . $keys . '=? ';
            } else {
                $strTmp .= ' and ' . str_replace('::', ' ', $keys) . ' ? ';
            }
            $whereValueArr[] =& $where[$keys];
            $this->_bindType .= $this->_determineType($values);
        }
        $this->_wheres = substr($strTmp, 4);
        if (!empty($this->_bindValue)) {
            $this->_bindValue = array_merge($this->_bindValue, $whereValueArr);
        } else {
            $this->_bindValue = $whereValueArr;
        }
        return true;
    }

    private function _dynamicBindResults($stmt)
    {
        $result = $stmt->get_result();
        $results = [];
        while ($resultRow = $result->fetch_assoc()) {
            $results[] = $resultRow;
        }
        return $results;
    }

    private function _order($order)
    {
        $orderArr = [];
        foreach ($order as $orderKey => $rowOrder) {
            $orderArr[] = $orderKey . ' ' . $rowOrder;
        }
        return implode(',', $orderArr);
    }

    function __destruct()
    {
        if ($this->_connect) {
            $this->_connect->close();
        }
    }
}
