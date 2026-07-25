<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class BaseModel extends Model
{
    // 自动写入创建时间和更新时间（unix timestamp）
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
}
