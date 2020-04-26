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

    public function __construct($config);

    public function insert($table, $data);

    public function update($table, $data, $where);

    public function delete($table, $where);

    /**
     * @param $table
     * @param $where
     * @param array $order
     * @param array $getInfo
     * @return array|mixed
     * @author LCF
     * @date 2019/8/17 21:32
     * 查询单条数据，一般用于登录类型的
     */
    public function selectOne($table, $where, $order = [], $getInfo = ['*']);

    /**
     * @param $table
     * @param array $order
     * @param int $offset
     * @param int $fetchNum
     * @param array $getInfo
     * @return array
     * @author LCF
     * @date 2019/8/17 21:33
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
     * @return array
     * @author LCF
     * @date 2019/8/17 21:33
     * selectAll 方法 和 select 方法的合体
     */
    public function selects($table, $where = [], $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*']);

    public function selectIn($table, $field, $inWhere, $where = [], $order = [], $offset = 0, $fetchNum = 0, $getInfo = ['*']);

    public function count($table, $where = [], $columnName = '*', $distinct = false);

    public function close();
}