<?php
declare(strict_types=1);

namespace app\adminapi\controller\auth;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\auth\AdminLogic;

class AdminController extends BaseAdminController
{
    public function lists()
    {
        return $this->data(AdminLogic::lists());
    }

    public function detail()
    {
        return $this->data(AdminLogic::detail((int)$this->request->get('id')));
    }

    public function self()
    {
        return $this->data(AdminLogic::detail($this->adminId));
    }

    public function add()
    {
        $result = AdminLogic::add($this->request->post());
        return $result ? $this->success('操作成功') : $this->fail(AdminLogic::getError());
    }

    public function edit()
    {
        $result = AdminLogic::edit($this->request->post());
        return $result ? $this->success('操作成功') : $this->fail(AdminLogic::getError());
    }

    public function delete()
    {
        AdminLogic::delete((int)$this->request->post('id'));
        return $this->success('操作成功');
    }

    public function updateStatus()
    {
        AdminLogic::updateStatus(
            (int)$this->request->post('id'),
            (int)$this->request->post('disable', 0)
        );
        return $this->success('操作成功');
    }
}
