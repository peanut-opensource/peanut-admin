<?php
declare(strict_types=1);

namespace app\adminapi\http\middleware;

use app\common\model\auth\SystemRoleMenu;
use app\common\service\JsonService;

/**
 * 权限中间件：基于角色验证当前路由访问权限
 */
class AuthMiddleware
{
    public function handle($request, \Closure $next)
    {
        // 免登录接口无需权限验证
        if ($request->controllerObject->isNotNeedLogin()) {
            return $next($request);
        }

        $adminInfo = $request->adminInfo ?? null;
        if (empty($adminInfo)) {
            return JsonService::fail('请先登录', null, 40100);
        }

        // 超级管理员跳过权限验证
        if (($adminInfo['root'] ?? 0) == 1) {
            return $next($request);
        }

        // 当前访问路径（controller/action）
        $accessUri = strtolower($request->controller() . '/' . $request->action());

        // 该管理员拥有的所有菜单权限（通过角色）
        $roleIds = array_column($adminInfo['roles'] ?? [], 'id');
        if (empty($roleIds)) {
            return JsonService::fail('暂无访问权限', null, 40300);
        }

        $menuIds   = SystemRoleMenu::whereIn('role_id', $roleIds)->column('menu_id');
        $permsList = \app\common\model\auth\SystemMenu::whereIn('id', $menuIds)
            ->where('perms', '<>', '')
            ->column('perms');

        $allowedUris = array_map('strtolower', $permsList);

        if (!in_array($accessUri, $allowedUris)) {
            return JsonService::fail('暂无访问权限', null, 40300);
        }

        return $next($request);
    }
}
