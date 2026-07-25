<?php
declare(strict_types=1);

namespace app\adminapi\http\middleware;

use app\adminapi\controller\BaseAdminController;
use think\exception\HttpException;

/**
 * 初始化中间件：校验控制器合法性，并实例化注入到 request
 */
class InitMiddleware
{
    public function handle($request, \Closure $next)
    {
        $controller = str_replace('.', '\\', $request->controller());
        $class      = '\\app\\adminapi\\controller\\' . $controller . 'Controller';

        if (!class_exists($class)) {
            throw new HttpException(404, 'controller not exists: ' . $class);
        }

        $obj = invoke($class);
        if (!($obj instanceof BaseAdminController)) {
            throw new HttpException(404, 'controller not extends BaseAdminController: ' . $class);
        }

        $request->controllerObject = $obj;

        return $next($request);
    }
}
