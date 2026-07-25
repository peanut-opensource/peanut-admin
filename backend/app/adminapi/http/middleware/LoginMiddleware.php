<?php
declare(strict_types=1);

namespace app\adminapi\http\middleware;

use app\adminapi\service\AdminTokenService;
use app\common\model\auth\Admin;
use app\common\service\JsonService;

/**
 * 登录中间件：验证 JWT token，注入 adminInfo 到 request
 */
class LoginMiddleware
{
    public function handle($request, \Closure $next)
    {
        $isNotNeedLogin = $request->controllerObject->isNotNeedLogin();

        // 解析 Bearer token
        $authorization = $request->header('Authorization', '');
        $token         = '';
        if (str_starts_with($authorization, 'Bearer ')) {
            $token = substr($authorization, 7);
        }

        if (empty($token) && !$isNotNeedLogin) {
            return JsonService::fail('请求缺少 token', null, 40100);
        }

        if (!empty($token)) {
            $adminId = AdminTokenService::parseToken($token);
            if ($adminId === false) {
                if (!$isNotNeedLogin) {
                    return JsonService::fail('登录超时，请重新登录', null, 40100);
                }
            } else {
                $admin = Admin::findOrEmpty($adminId);
                if ($admin->isEmpty()) {
                    if (!$isNotNeedLogin) {
                        return JsonService::fail('账号不存在或已删除', null, 40100);
                    }
                } else {
                    if ($admin->disable) {
                        return JsonService::fail('账号已被禁用', null, 40300);
                    }
                    $request->adminInfo = $admin->toArray();
                    $request->adminId   = $adminId;
                }
            }
        }

        return $next($request);
    }
}
