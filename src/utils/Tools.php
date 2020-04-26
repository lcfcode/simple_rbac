<?php


namespace authmng\utils;


class Tools
{
    /**
     * 二维数组根据key值为键值
     * @param array $array 数组
     * @param string $keys 键字段
     * @return array 排序后的数组
     */
    public static function arrayKey($array, $keys)
    {
        $keysArray = [];
        foreach ($array as $k => $v) {
            $keysArray[$v[$keys]] = $v;
        }
        return $keysArray;
    }

    /**
     * 二维数组根据某个字段排序
     * @param array $array 要排序的数组
     * @param string $keys 要排序的键字段
     * @param string $sort 排序类型  SORT_ASC     SORT_DESC
     * @return array 排序后的数组
     */
    public static function arraySort($array, $keys, $sort = 'SORT_ASC')
    {
        $keysValue = [];
        foreach ($array as $k => $v) {
            $keysValue[$k] = $v[$keys];
        }

        //保持键值不变
        $key = array_keys($array);
        array_multisort(
            array_column($array, $keys), $sort, SORT_NUMERIC, $array, $key
        );
        $array = array_combine($key, $array);
        //保持键值不变
        return $array;
    }

    //获取菜单子集数据
    public static function getSubset($arr, $id = '0')
    {
        $res = [];
        if (!is_string($id)) {
            $id = (string)$id;
        }
        foreach ($arr as $rows) {
            if ($rows['parent_id'] == $id) {
                $res[$rows['id']] = $rows;
            }
        }
        return $res;
    }

    public static function outputJson($code, $msg, $data = [])
    {
        header('Content-Type:application/json;charset=UTF-8');
        return json_encode(['code' => $code, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
    }

    public static function getUuid($prefix = null)
    {
        return strtolower(md5(uniqid($prefix . php_uname('n') . mt_rand(), true)));
    }
}