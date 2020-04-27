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
interface DriveInterface
{
    public static function getInstance($config);

    /**
     * @param $table
     * @param $data
     * @return mixed
     * @author LCF
     * @date
     * 数据插入
     */
    public function insert($table, $data);

    /**
     * @param $table
     * @param $data
     * @param $where
     * @return mixed
     * @author LCF
     * @date
     * 数据更新
     */
    public function update($table, $data, $where);

    /**
     * @param $table
     * @param $where
     * @return mixed
     * @author LCF
     * @date
     * 删除数据
     */
    public function delete($table, $where);

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
    public function selectOne($table, $where, $order = [], $getInfo = ['*']);

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
    public function selectAll($table, $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*']);

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
    public function selects($table, $where = [], $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*']);

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
    public function selectIn($table, $field, $inWhere, $where = [], $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*']);

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
    public function count($table, $where = [], $columnName = '*', $distinct = false);

    public function close();
}