<?php
declare(strict_types=1);

namespace app\common\service;

use think\Response;
use think\response\Json;
use think\exception\HttpResponseException;

class JsonService
{
    // 成功
    public static function success(string $msg = 'success', mixed $data = [], int $code = 20000): Json
    {
        return json(compact('code', 'msg', 'data'));
    }

    // 失败
    public static function fail(string $msg = 'fail', mixed $data = null, int $code = 40000): Json
    {
        return json(compact('code', 'msg', 'data'));
    }

    // 返回数据
    public static function data(mixed $data): Json
    {
        return json(['code' => 20000, 'msg' => 'success', 'data' => $data]);
    }

    // 分页列表
    public static function dataLists(array $lists, int $count, int $pageNo = 1, int $pageSize = 15): Json
    {
        return json([
            'code' => 20000,
            'msg'  => 'success',
            'data' => compact('lists', 'count', 'pageNo', 'pageSize'),
        ]);
    }

    // 抛出异常响应
    public static function throw(string $msg = 'fail', int $code = 40000): never
    {
        $response = Response::create(['code' => $code, 'msg' => $msg, 'data' => null], 'json');
        throw new HttpResponseException($response);
    }
}
