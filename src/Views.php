<?php


namespace authmng;


use authmng\domain\OperateTab;
use authmng\utils\Tools;

class Views
{
    private $config;
    private $url;

    private $operateTab;

    public function __construct($config, $url)
    {
        $this->config = $config;
        $this->url = $url;
    }

    private function getOperateTab()
    {
        if ($this->operateTab) {
            return $this->operateTab;
        }
        $this->operateTab = new OperateTab($this->config);
        return $this->operateTab;
    }

    public function getCss()
    {
        return file_get_contents(__DIR__ . '/view/css.html');
    }

    public function menu($save = [])
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = !empty($save) ? $save : $_POST;
            return $this->getOperateTab()->menuSortUpdate($data);
        }
        $menu = $this->getOperateTab()->menuList();
        $param = !empty($save) ? $save : $_GET;
        $menuId = isset($param['id']) ? trim($param['id']) : '';
        if ($menuId) {
            foreach ($menu as $key => $menuRow) {
                if ('0' == $menuRow['parent_id'] && $menuRow['id'] != $menuId) {
                    unset($menu[$key]);
                }
            }
        }
        $menus = Tools::arraySort($menu, 'sort', SORT_ASC);
        unset($menu);
        $info = [];
        $this->getTrees($menus, $info);
        return require __DIR__ . '/view/menu.phtml';
    }

    public function menuAdd($save = [])
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = !empty($save) ? $save : $_POST;
            return $this->getOperateTab()->menuOperate($data);
        }
        $menu = $this->getOperateTab()->menuList();
        $param = !empty($save) ? $save : $_GET;
        $id = isset($param['id']) ? trim($param['id']) : '';
        $parentId = isset($param['parent_id']) ? trim($param['parent_id']) : '';
        foreach ($menu as &$row) {
            $row['selected'] = $row['id'] == $parentId ? 'selected' : '';
        }
        $upperLevel = [];
        $this->getTrees($menu, $upperLevel, false);
        if ($id) {
            $list = $this->getOperateTab()->menuListById($id);
            return require __DIR__ . '/view/menuEdit.phtml';
        }
        return require __DIR__ . '/view/menuAdd.phtml';
    }

    private function getTrees($arr, &$info, $flag = true, $id = '0', $nb = '', $i = 0)
    {
        $i++;
        if (!is_string($id)) {
            $id = (string)$id;
        }
        $nbsp = '&nbsp;&nbsp;';
        $slice = $flag == true ? '<span class="font_color">├</span>' : '├';
        foreach ($arr as $rows) {
            if ($rows['parent_id'] == $id) {
                $ids = $rows['id'];
                if (isset($info[$ids])) {
                    continue;
                }
                $rows['name'] = $nb . $slice . $nbsp . $rows['name'];
                $info[$ids] = $rows;
                $this->getTrees($arr, $info, $flag, $ids, $nb . $slice . $nbsp, $i);
            }
        }
    }

    public function roleAdd($save = [])
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = !empty($save) ? $save : $_POST;
            return $this->getOperateTab()->roleOperate($data);
        }
        $param = !empty($save) ? $save : $_GET;
        $id = isset($param['id']) ? trim($param['id']) : '';
        if ($id) {
            $list = $this->getOperateTab()->roleListById($id);
            return require __DIR__ . '/view/roleEdit.phtml';
        }
        return require __DIR__ . '/view/roleAdd.phtml';
    }

    public function role()
    {
        $info = $this->getOperateTab()->roleList();
        return require __DIR__ . '/view/role.phtml';
    }

    public function authorize($save = [])
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = !empty($save) ? $save : $_POST;
            return $this->getOperateTab()->roleMenuAccessOperate($data);
        }
        $param = !empty($save) ? $save : $_GET;
        $roleId = isset($param['role_id']) ? trim($param['role_id']) : '';
        if (empty($roleId)) {
            trigger_error('没有角色id', E_USER_ERROR);
        }
        $menuIdArr = $this->getOperateTab()->roleMenuAccessVerify($roleId);
        $chooseMenuIdArr = [];
        if ($menuIdArr) {
            foreach ($menuIdArr as $item) {
                $chooseMenuIdArr[] = $item['menu_id'];
            }
            unset($item);
        }
        $menu = $this->getOperateTab()->menuList();
        $menus = Tools::getSubset($menu);
//        $this->getAllMenu($menus, $menu);
//        $strHtml = $this->authorizeHtml($menus, $chooseMenuIdArr);
        $strHtml = $this->getAauthorizeHtml($menu, $menus, $chooseMenuIdArr);
        return require __DIR__ . '/view/authorize.phtml';
    }

    public function getAauthorizeHtml($menu, $data, $chooseMenuIdArr)
    {
        $divHead = '<div class="authorize_check" style="border: solid 1px #ddd;margin: 5px 10px">';
        $divFoot = '</div>';
        $spanHead = '<span class="first_bg" style="display: inline-block;width: 100%;">';
        $spanFoot = ' </span>';
        $inputChild = '<input class="authorize_checkbox" name="menu_id[]" parent="0" type="checkbox" value="';
        $inputParent = '<input class="authorize_checkbox" name="menu_id[]" parent="1" type="checkbox" value="';
        $inputBody = '" ';
        $inputFoot = ' >';

        $str = '';
        $i = 0;
        foreach ($data as $item) {
            $itemId = $item['id'];
            $checked = '';
            if (in_array($itemId, $chooseMenuIdArr)) {
                $checked = ' checked';
            }
            $menus = Tools::getSubset($menu, $itemId);
            if ($menus) {
                if ($i != 0) {
                    $i = 0;
                }
                if ('0' == $item['parent_id']) {
                    $str .= $divHead . $spanHead . $inputParent . $itemId . $inputBody . $checked . $inputFoot . $item['name'] . $spanFoot;
                } else {
                    $str .= $divHead . $inputChild . $itemId . $inputBody . $checked . $inputFoot . $item['name'];
                }
                $str .= $this->getAauthorizeHtml($menu, $menus, $chooseMenuIdArr);
                $str .= $divFoot;
            } else {
                $childDivHead = '<div class="authorize_check" style="display: inline-block;margin-left: 10px">';
                if ($i == 0) {
                    $str .= '<div></div>';
                    $str .= $childDivHead . $inputChild . $itemId . $inputBody . $checked . $inputFoot . $item['name'] . $divFoot;
                } else {
                    $str .= $childDivHead . '&nbsp;&nbsp;' . $inputChild . $itemId . $inputBody . $checked . $inputFoot . $item['name'] . $divFoot;
                }
                $i++;
            }
        }
        return $str;
    }

    private function getAllMenu(&$child, $arr)
    {
        foreach ($child as &$item) {
            $menu = Tools::getSubset($arr, $item['id']);
            if ($menu) {
                foreach ($menu as $items) {
                    $item['child'][$items['id']] = $items;
                }
            } else {
                $item['child'] = [];
            }
            if ($item['child']) {
                $this->getAllMenu($item['child'], $arr);
            }
        }
    }

    private function authorizeHtml($data, $chooseMenuIdArr)
    {
        $divHead = '<div class="authorize_check" style="border-top: solid 1px #ddd">';
        $divFoot = '</div>';
        $nbsp = '&nbsp;&nbsp;';
        $panHead = '<span class="first_bg" style="display: inline-block;width: 100%;">';
        $inputChild = '<input class="authorize_checkbox" name="menu_id[]" parent="0" type="checkbox" value="';
        $inputParent = '<input class="authorize_checkbox" name="menu_id[]" parent="1" type="checkbox" value="';
        $inputBody = '" ';
        $inputFoot = ' >';
        $spanFoot = ' </span>';
        $strHtml = '';
        foreach ($data as $item) {
            $str = $divHead . $panHead . $inputParent . $item['id'] . $inputBody;
            if (in_array($item['id'], $chooseMenuIdArr)) {
                $str .= ' checked';
            }
            $str .= $inputFoot . $item['name'] . $spanFoot;
            if (isset($item['child']) && !empty($item['child'])) {
                $str .= $this->recursion($item['child'], $chooseMenuIdArr, $divHead, $divFoot, $nbsp, $inputChild, $inputBody, $inputFoot);
            }
            $str .= $divFoot;
            $strHtml .= $str;
        }
        return $strHtml;
    }

    private function recursion($data, $chooseMenuIdArr, $divHead, $divFoot, $nbsp, $inputChild, $inputBody, $inputFoot)
    {
        $str = '';
        foreach ($data as $item) {
            $str .= $divHead . $nbsp . $inputChild . $item['id'] . $inputBody;
            if (in_array($item['id'], $chooseMenuIdArr)) {
                $str .= ' checked';
            }
            $str .= $inputFoot . $item['name'];
            if (isset($item['child']) && !empty($item['child'])) {
                $str .= $this->recursion($item['child'], $chooseMenuIdArr, $divHead . $nbsp . $nbsp, $divFoot, $nbsp, $inputChild, $inputBody, $inputFoot);
            }
            $str .= $divFoot;
        }
        return $str;
    }

    public function admin()
    {
        $info = $this->getOperateTab()->adminList();
        if ($info) {
            foreach ($info as $key => $item) {
                $roleName = '';
                $roleList = $this->getOperateTab()->roleAdminVerify(['admin_id' => $item['id']]);
                if ($roleList) {
                    foreach ($roleList as $value) {
                        $roleNameArr = $this->getOperateTab()->roleListById($value['role_id']);
                        if ($roleNameArr) {
                            $roleName .= $roleNameArr['name'] . '； ';
                        }
                    }
                    unset($value);
                }
                $info[$key]['role_name'] = $roleName;
            }
            unset($item);
        }

        return require __DIR__ . '/view/admin.phtml';
    }

    public function adminAdd($save = [])
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = !empty($save) ? $save : $_POST;
            return $this->getOperateTab()->adminOperate($data);
        }
        $role = $this->getOperateTab()->roleList();
        $param = !empty($save) ? $save : $_GET;
        $id = isset($param['id']) ? trim($param['id']) : '';
        $accessArr = [];
        if ($id) {
            $roleAdmin = $this->getOperateTab()->roleAdminVerify(['admin_id' => $id]);
            if ($roleAdmin) {
                foreach ($roleAdmin as $item) {
                    $accessArr[] = $item['role_id'];
                }
                unset($item);
            }
            $list = $this->getOperateTab()->adminListById($id);
            return require __DIR__ . '/view/adminEdit.phtml';
        }
        return require __DIR__ . '/view/adminAdd.phtml';
    }

}