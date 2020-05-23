<?php


namespace authmng;

use authmng\domain\OperateTab;
use authmng\utils\Tools;

class Info
{
    /**
     * @var OperateTab;
     */
    private $operateTab;

    /**
     * Info constructor.
     * @param $config array 数据配置
     */
    public function __construct($config)
    {
        $this->operateTab = new OperateTab($config);
    }

    /**
     * @param array $where
     * @return array
     * @author LCF
     * @date 2020/4/27 10:08
     * 菜单列表
     */
    public function menuList($where = [])
    {
        return $this->operateTab->menuList($where);
    }

    /**
     * @param array $where
     * @return array
     * @author LCF
     * @date 2020/4/27 10:08
     * 角色列表
     */
    public function roleList($where = [])
    {
        return $this->operateTab->roleList($where);
    }

    /**
     * @param array $where
     * @return array
     * @author LCF
     * @date 2020/4/27 10:08
     * 管理员列表
     */
    public function adminList($where = [])
    {
        return $this->operateTab->adminList($where);
    }

    /**
     * @param $adminId
     * @param $url
     * @return bool
     * @author LCF
     * @date 2020/4/27 10:09
     * 验证url权限
     */
    public function auth($adminId, $url)
    {
        if ('admin' == $adminId) {
            return true;
        }
        $roleList = $this->operateTab->roleAdminVerify(['admin_id' => $adminId]);
        if (empty($roleList)) {
            return false;
        }
        $url = trim($url, '/');
        $name = strtolower($url);
        $noVerify = $this->operateTab->menuRuleVerify($name);
        if ($noVerify) {
            return true;
        }
        $flag = false;
        foreach ($roleList as $roleId) {
            //先判断角色是否有效
            $status = $this->operateTab->roleVerify(['id' => $roleId['role_id'], 'status' => '1']);
            if (empty($status)) {
                continue;
            }
            $menus = $this->operateTab->authAccessVerify(['role_id' => $roleId['role_id']]);
            if ($menus) {
                foreach ($menus as $value) {
                    if ($value['menu_url'] == $name) {
                        $flag = true;
                        break;
                    }
                }
            }
            if ($flag === true) {
                break;
            }
        }
        return $flag;
    }

    /**
     * @param $nickname
     * @param $password
     * @return array|bool|mixed
     * @author LCF
     * @date 2020/4/27 10:09
     * 登录
     */
    public function login($nickname, $password)
    {
        return $this->operateTab->loginVerify($nickname, $password);
    }

    /**
     * @param $adminId
     * @param $password
     * @return mixed
     * @author LCF
     * @date
     * 单个管理员修改自己的密码
     */
    public function updatePwd($adminId, $password)
    {
        $data['passwd'] = password_hash(md5($password), PASSWORD_BCRYPT);
        $data['update_date'] = date('Y-m-d H:i:s');
        return $this->operateTab->updateAdmin(['id' => $adminId], $data);
    }

    /**
     * @param $adminId
     * @return array
     * @author LCF
     * @date 2020/4/27 10:10
     * 获取某个管理员拥有的菜单
     */
    public function getMenu($adminId)
    {
        if ('admin' == $adminId) {
            return $this->operateTab->menuList(['is_show' => '1']);
        }
        $roleList = $this->operateTab->roleAdminVerify(['admin_id' => $adminId]);
        if (empty($roleList)) {
            trigger_error('没有授权信息', E_USER_ERROR);
        }
        $menuTmpArr = [];
        foreach ($roleList as $roleId) {
            //先判断角色是否有效
            $status = $this->operateTab->roleVerify(['id' => $roleId['role_id'], 'status' => '1']);
            if (empty($status)) {
                continue;
            }
            $menus = $this->operateTab->authAccessVerify(['role_id' => $roleId['role_id']]);
            foreach ($menus as $item) {
                $menuTmpArr[$item['menu_id']] = $item['menu_id'];
            }
        }
        //处理不需要验证的菜单
//        $noVerifyMenu = $this->operateTab->menuList(['is_show' => '1', 'type' => '0']);
//        foreach ($noVerifyMenu as $items) {
//            $menuTmpArr[$items['id']] = $items['id'];
//        }
        if (empty($menuTmpArr)) {
            trigger_error('没有授权菜单信息', E_USER_ERROR);
        }
        return $this->operateTab->menuWhereIn($menuTmpArr);
    }

    /**
     * @param $adminId
     * @return array
     * @author LCF
     * @date 2020/4/27 10:09
     * bui使用的菜单结构，本人使用的
     */
    public function getBuiList($adminId)
    {
        $menuArr = $this->getMenu($adminId);
        $menuArr = Tools::arraySort($menuArr, 'sort', SORT_ASC);
        $parentArr = Tools::getSubset($menuArr);
        if (empty($parentArr)) {
            trigger_error('菜单没有数据', E_USER_ERROR);
        }
        $pageList = [];
        $menuList = [];
        $homePage = '';
        $first = 0;
        foreach ($parentArr as $item) {
            $tmp = [];
            $tmp['id'] = $item['id'];
            $tmp['title'] = $item['name'];
            $menu = Tools::getSubset($menuArr, $item['id']);
            $menus = [];
            foreach ($menu as $items) {
                $temp = [];
                $temp['text'] = $items['name'];
                $menuSubset = Tools::getSubset($menuArr, $items['id']);
                $menusItem = [];
                foreach ($menuSubset as $value) {
                    if (empty($homePage) && $first == 0) {
                        //单独处理默认打开的指定的页面
                        $homePage = $value['id'];
                    }
                    $it = [];
                    $it['id'] = $value['id'];
                    $it['text'] = $value['name'];
                    $it['href'] = '/' . $value['app'] . '/' . $value['control'] . '/' . $value['action'];
                    $menusItem[] = $it;
                }
                $temp['items'] = $menusItem;
                $menus[] = $temp;
            }
            if ($first == 0) {
                $tmp['homePage'] = $homePage;
            }
            $tmp['menu'] = $menus;
            $menuList[] = $tmp;
            $pageList[] = $item['name'];
            $first++;
        }
        return [$pageList, $menuList];
    }
}