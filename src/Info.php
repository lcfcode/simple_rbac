<?php


namespace Rbac;

use Rbac\Domain\OperateTab;
use Rbac\Utils\Tools;

class Info
{
    /**
     * @var OperateTab;
     */
    private $operateTab;

    public function __construct($config)
    {
        $this->operateTab = new OperateTab($config);
    }

    public function menuList($where = [])
    {
        return $this->operateTab->menuList($where);
    }

    public function roleList($where = [])
    {
        return $this->operateTab->roleList($where);
    }

    public function adminList($where = [])
    {
        return $this->operateTab->adminList($where);
    }

    //验证url权限
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

    public function login($nickname, $password)
    {
        return $this->operateTab->loginVerify($nickname, $password);
    }

    public function getBuiList($adminId)
    {
        $menuArr = $this->getMenu($adminId);
        $menuArr = Tools::arraySort($menuArr, 'sort', SORT_ASC);
        $parentArr = Tools::getSubset($menuArr);
        if (empty($parentArr)) {
            throw new \Exception('菜单没有数据', 500);
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

    public function getMenu($adminId)
    {
        if ('admin' == $adminId) {
            return $this->operateTab->menuList(['is_show' => '1']);
        }
        $roleList = $this->operateTab->roleAdminVerify(['admin_id' => $adminId]);
        if (empty($roleList)) {
            throw new \Exception('没有授权信息', 500);
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
            throw new \Exception('没有授权菜单信息', 500);
        }
        return $this->operateTab->menuWhereIn($menuTmpArr);
    }
}