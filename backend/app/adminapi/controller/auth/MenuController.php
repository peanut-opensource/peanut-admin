<?php
declare(strict_types=1);

namespace app\adminapi\controller\auth;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\auth\MenuLogic;

class MenuController extends BaseAdminController
{
    /** 获取当前管理员的菜单路由（用于前端动态菜单） */
    public function route()
    {
        return $this->data(MenuLogic::getMenuByAdminId($this->adminId));
    }

    /** 菜单列表（管理用，树形） */
    public function lists()
    {
        return $this->data(MenuLogic::getAll());
    }

    /** 全部菜单数据（用于角色分配） */
    public function all()
    {
        return $this->data(MenuLogic::getAllSimple());
    }

    /** 菜单详情 */
    public function detail()
    {
        $id = (int)$this->request->get('id');
        return $this->data(MenuLogic::detail($id));
    }

    /** 添加菜单 */
    public function add()
    {
        $params = $this->request->post();
        $result = MenuLogic::add($params);
        return $result ? $this->success('操作成功') : $this->fail(MenuLogic::getError());
    }

    /** 编辑菜单 */
    public function edit()
    {
        $params = $this->request->post();
        $result = MenuLogic::edit($params);
        return $result ? $this->success('操作成功') : $this->fail(MenuLogic::getError());
    }

    /** 删除菜单 */
    public function delete()
    {
        $id = (int)$this->request->post('id');
        MenuLogic::delete($id);
        return $this->success('操作成功');
    }

    /** 更新状态 */
    public function updateStatus()
    {
        $id        = (int)$this->request->post('id');
        $isDisable = (int)$this->request->post('is_disable', 0);
        MenuLogic::updateStatus($id, $isDisable);
        return $this->success('操作成功');
    }
}
