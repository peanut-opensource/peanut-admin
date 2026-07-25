<?php
declare(strict_types=1);

namespace app\adminapi\controller\auth;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\auth\AdminLogic;
use app\adminapi\service\AdminTokenService;
use app\common\model\auth\Admin;

class LoginController extends BaseAdminController
{
    public array $notNeedLogin = ['login', 'logout'];

    /**
     * 管理员登录
     */
    public function login()
    {
        $params   = $this->request->post();
        $username = trim($params['username'] ?? '');
        $password = trim($params['password'] ?? '');

        if (empty($username) || empty($password)) {
            return $this->fail('用户名和密码不能为空');
        }

        $admin = Admin::where('username', $username)->findOrEmpty();
        if ($admin->isEmpty()) {
            return $this->fail('账号不存在');
        }
        if ($admin->disable) {
            return $this->fail('账号已被禁用');
        }

        $hashedPwd = md5(md5($password) . $admin->salt);
        if ($hashedPwd !== $admin->password) {
            return $this->fail('密码错误');
        }

        $token = AdminTokenService::createToken($admin->id);

        return $this->data([
            'token'    => $token,
            'admin_id' => $admin->id,
        ]);
    }

    /**
     * 获取当前管理员信息
     */
    public function info()
    {
        $admin = Admin::with(['roles'])->findOrEmpty($this->adminId);
        if ($admin->isEmpty()) {
            return $this->fail('管理员不存在');
        }

        return $this->data([
            'id'       => $admin->id,
            'username' => $admin->username,
            'nickname' => $admin->nickname,
            'avatar'   => $admin->avatar,
            'root'     => $admin->root,
            'roles'    => $admin->roles->column('name'),
        ]);
    }

    /**
     * 退出登录（JWT 无状态，前端清除 token 即可）
     */
    public function logout()
    {
        return $this->success('退出成功');
    }
}
