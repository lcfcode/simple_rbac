<?php

namespace authmng\domain;

use authmng\utils\MongoClass;
use authmng\utils\MysqliClass;
use authmng\utils\Tools;

/**
 * Class OperateTab
 * @package authmng\domain
 * @author LCF
 * @date
 * 使用到的db的统一操作
 */
class OperateTab
{
    /**
     * @var \authmng\utils\DriveInterface
     * mongodb的原因 没有做关联查询
     */
    private $db = null;
    private $config;
    private $menuTab = 'menu';
    private $menuRuleTab = 'menu_rule';
    private $roleTab = 'role';
    private $roleMenuAccessTab = 'role_menu_access';
    private $adminTab = 'admin';
    private $roleAdminTab = 'role_admin';

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function getDb()
    {
        if (!$this->db) {
            $this->db = (isset($this->config['drive']) && 'mongo' == $this->config['drive']) ? MongoClass::getInstance($this->config) : MysqliClass::getInstance($this->config);
        }
        return $this->db;
    }

    public function menuList($where = [])
    {
        $result = $this->getDb()->selects($this->menuTab, $where);
        array_filter($result);
        return $result;
    }

    public function menuListById($id)
    {
        return $this->getDb()->selectOne($this->menuTab, ['id' => $id]);
    }

    public function menuSortUpdate($data)
    {
        if (!isset($data['id']) || strlen($data['id']) <= 0 ||
            !isset($data['sort']) || strlen($data['sort']) <= 0) {
            return ['code' => 0, 'msg' => '参数错误', 'data' => null];
        }
        $result = $this->getDb()->update($this->menuTab, ['sort' => intval($data['sort'])], ['id' => $data['id']]);
        if ($result) {
            return ['code' => 1, 'msg' => '修改顺序成功', 'data' => $result];
        }
        return ['code' => 0, 'msg' => '修改顺序失败', 'data' => $result];
    }

    public function menuOperate($data)
    {
        $flag = isset($data['flag']) ? $data['flag'] : '';
        if ('d' == $flag) {
            if (empty($data['id'])) {
                return ['code' => 0, 'msg' => '参数错误', 'data' => null];
            }
            $result = $this->getDb()->delete($this->menuTab, ['id' => $data['id']]);
            $resultRule = $this->getDb()->delete($this->menuRuleTab, ['menu_id' => $data['id']]);
            if ($result && $resultRule) {
                return ['code' => 1, 'msg' => '删除成功', 'data' => [$result, $resultRule]];
            }
            return ['code' => 0, 'msg' => '删除失败', 'data' => [$result, $resultRule]];
        }
        unset($data['flag']);
        if (!isset($data['parent_id']) || strlen($data['parent_id']) <= 0 ||
            !isset($data['is_show']) || strlen($data['is_show']) <= 0 ||
            !isset($data['is_verify']) || strlen($data['is_verify']) <= 0 ||
            !isset($data['name']) || strlen($data['name']) <= 0 ||
            !isset($data['app']) || strlen($data['app']) <= 0 ||
            !isset($data['control']) || strlen($data['control']) <= 0 ||
            !isset($data['action']) || strlen($data['action']) <= 0) {
            return ['code' => 0, 'msg' => '参数错误', 'data' => null];
        }
        $data['sort'] = intval($data['sort']);
        $data['id'] = isset($data['id']) ? $data['id'] : Tools::getUuid();
        $oldData = $this->getDb()->selectOne($this->menuTab, ['id' => $data['id']]);
        if ($oldData) {
            $ret = $this->getDb()->update($this->menuTab, $data, ['id' => $data['id']]);
        } else {
            $oldData = $this->getDb()->selectOne($this->menuTab, [
                'app' => $data['app'],
                'control' => $data['control'],
                'action' => $data['action'],
            ]);
            if ($oldData) {
                return ['code' => 0, 'msg' => '改模块已经存在', 'data' => $oldData];
            }
            $ret = $this->getDb()->insert($this->menuTab, $data);
        }
        if ($ret) {
            $this->getDb()->delete($this->menuRuleTab, ['menu_id' => $data['id']]);
            $name = strtolower($data['app'] . '/' . $data['control'] . '/' . $data['action']);
            $authRule = [
                "url" => $name,
                "is_verify" => $data['is_verify'],
                "title" => $data['name'],
                'menu_id' => $data['id'],
                'url_param' => $data['url_param'],
            ];
            $result = $this->getDb()->insert($this->menuRuleTab, $authRule);
            if ($result) {
                return ['code' => 1, 'msg' => '保存成功', 'data' => $result];
            }
        }
        return ['code' => 0, 'msg' => '保存失败', 'data' => null];
    }

    public function roleOperate($data)
    {
        $flag = isset($data['flag']) ? $data['flag'] : '';
        if ('d' == $flag) {
            if (empty($data['id'])) {
                return ['code' => 0, 'msg' => '参数错误', 'data' => null];
            }
            //判断是否与admin关联
            $isAdmin = $this->getDb()->selectOne($this->roleAdminTab, ['role_id' => $data['id']]);
            $isMenu = $this->getDb()->selectOne($this->roleMenuAccessTab, ['role_id' => $data['id']]);
            if ($isMenu || $isAdmin) {
                return ['code' => 0, 'msg' => '存在还在使用的配置', 'data' => [$isMenu, $isAdmin]];
            }
            $result = $this->getDb()->delete($this->roleTab, ['id' => $data['id']]);
            if ($result) {
                return ['code' => 1, 'msg' => '删除成功', 'data' => $result];
            }
            return ['code' => 0, 'msg' => '删除失败', 'data' => $result];
        }
        unset($data['flag']);
        if (!isset($data['name']) || strlen($data['name']) <= 0 ||
            !isset($data['remark']) || strlen($data['remark']) <= 0 ||
            !isset($data['status']) || strlen($data['status']) <= 0) {
            return ['code' => 0, 'msg' => '参数错误', 'data' => null];
        }
        $data['id'] = isset($data['id']) ? $data['id'] : Tools::getUuid();
        $oldData = $this->getDb()->selectOne($this->roleTab, ['id' => $data['id']]);
        if ($oldData) {
            $result = $this->getDb()->update($this->roleTab, $data, ['id' => $data['id']]);
        } else {
            $oldData = $this->getDb()->selectOne($this->roleTab, ['name' => $data['name']]);
            if ($oldData) {
                return ['code' => 0, 'msg' => '该名称已经存在', 'data' => $oldData];
            }
            $result = $this->getDb()->insert($this->roleTab, $data);
        }
        if ($result) {
            return ['code' => 1, 'msg' => '保存成功', 'data' => $result];
        }
        return ['code' => 0, 'msg' => '保存失败', 'data' => null];
    }

    public function roleListById($id)
    {
        return $this->getDb()->selectOne($this->roleTab, ['id' => $id]);
    }

    public function roleList($where = [])
    {
        return $this->getDb()->selects($this->roleTab, $where);
    }

    public function roleMenuAccessOperate($post)
    {
        if (!isset($post['menu_id']) || empty($post['menu_id']) ||
            !isset($post['role_id']) || strlen($post['role_id']) <= 0) {
            return ['code' => 0, 'msg' => '参数错误', 'data' => null];
        }
        $menuList = $this->menuList();
        $menu = Tools::arrayKey($menuList, 'id');
        $menuId = $post['menu_id'];
        $roleId = $post['role_id'];
        $this->getDb()->delete($this->roleMenuAccessTab, ['role_id' => $roleId]);
        $allData = [];
        foreach ($menuId as $v) {
            $menus = isset($menu[$v]) ? $menu[$v] : [];
            if ($menus) {
                $name = strtolower($menus['app'] . '/' . $menus['control'] . '/' . $menus['action']);
                $data = [
                    "role_id" => $roleId,
                    "menu_url" => $name,
                    'menu_id' => $v
                ];
                $allData[] = $data;
            }
        }
        $result = $this->getDb()->insertMultiple($this->roleMenuAccessTab, $allData);
        if ($result) {
            return ['code' => 1, 'msg' => '保存成功', 'data' => $result];
        }
        return ['code' => 0, 'msg' => '保存失败', 'data' => $result];
    }

    public function adminList($where = [])
    {
        return $this->getDb()->selects($this->adminTab, $where);
    }

    public function adminListById($id)
    {
        return $this->getDb()->selectOne($this->adminTab, ['id' => $id]);
    }

    public function adminOperate($post)
    {
        $flag = isset($post['flag']) ? $post['flag'] : '';
        if ('d' == $flag) {
            if (empty($post['id'])) {
                return ['code' => 0, 'msg' => '参数错误', 'data' => null];
            }
            $this->getDb()->delete($this->roleAdminTab, ['admin_id' => $post['id']]);
            $result = $this->getDb()->delete($this->adminTab, ['id' => $post['id']]);
            if ($result) {
                return ['code' => 1, 'msg' => '删除成功', 'data' => $result];
            }
            return ['code' => 0, 'msg' => '删除失败', 'data' => $result];
        }
        unset($post['flag']);
        if (!isset($post['nickname']) || strlen($post['nickname']) <= 0 ||
            !isset($post['phone']) || strlen($post['phone']) <= 0 ||
            !isset($post['name']) || strlen($post['name']) <= 0 ||
            !isset($post['status']) || strlen($post['status']) <= 0) {
            return ['code' => 0, 'msg' => '参数错误', 'data' => null];
        }
        $roleId = isset($post['role_id']) ? $post['role_id'] : [];
        $adminId = $post['id'] = isset($post['id']) ? $post['id'] : Tools::getUuid();
        $oldData = $this->getDb()->selectOne($this->adminTab, ['id' => $adminId]);
        unset($post['role_id']);
        if ($oldData) {
            if (isset($post['passwd']) && !empty($post['passwd'])) {
                $post['passwd'] = password_hash(md5($post['passwd']), PASSWORD_BCRYPT);
            } else {
                unset($post['passwd']);
            }
            $post['update_date'] = date('Y-m-d H:i:s');
            $result = $this->getDb()->update($this->adminTab, $post, ['id' => $adminId]);
        } else {
            $oldData = $this->getDb()->selectOne($this->adminTab, ['nickname' => $post['nickname']]);
            if ($oldData) {
                return ['code' => 0, 'msg' => '该管理员已经存在', 'data' => $oldData];
            }
            if (!isset($post['passwd']) || strlen($post['passwd']) <= 0) {
                return ['code' => 0, 'msg' => '密码没有输入', 'data' => null];
            }
            $post['passwd'] = password_hash(md5($post['passwd']), PASSWORD_BCRYPT);
//            $post['passwd'] = md5($post['passwd']);
            $post['create_date'] = date('Y-m-d H:i:s');
            $post['update_date'] = date('Y-m-d H:i:s');
            $result = $this->getDb()->insert($this->adminTab, $post);
        }
        $resArr = [];
        if ($roleId && $result) {
            $this->getDb()->delete($this->roleAdminTab, ['admin_id' => $adminId]);
            foreach ($roleId as $roleIdItem) {
                $insertRole = [
                    'admin_id' => $adminId,
                    'role_id' => $roleIdItem,
                ];
                $resArr[] = $this->getDb()->insert($this->roleAdminTab, $insertRole);
            }
        }
        return ['code' => 1, 'msg' => '保存成功', 'data' => [$result, $resArr]];
    }

    public function authAccessVerify($where)
    {
        return $this->getDb()->selects($this->roleMenuAccessTab, $where);
    }

    public function roleVerify($where)
    {
        return $this->getDb()->selectOne($this->roleTab, $where);
    }

    public function roleAdminVerify($where)
    {
        return $this->getDb()->selects($this->roleAdminTab, $where);
    }

    public function menuRuleVerify($url, $isVerify = 0)
    {
        return $this->getDb()->selectOne($this->menuRuleTab, ['url' => $url, 'is_verify' => $isVerify]);
    }

    public function loginVerify($nickname, $password)
    {
        $result = $this->getDb()->selectOne($this->adminTab, ['nickname' => $nickname, 'status' => '1']);
        if (empty($result)) {
            return false;
        }
        $verify = password_verify(md5($password), $result['passwd']);
        if (false == $verify) {
            return false;
        }
        return $result;
    }

    public function roleMenuAccessVerify($roleId)
    {
        return $this->getDb()->selects($this->roleMenuAccessTab, ['role_id' => $roleId]);
    }

    public function menuWhereIn($whereIn, $status = '1')
    {
        return $this->getDb()->selectIn($this->menuTab, 'id', $whereIn, ['is_show' => $status]);
    }

    public function updateAdmin($where, $data)
    {
        return $this->getDb()->update($this->adminTab, $data, $where);
    }
}