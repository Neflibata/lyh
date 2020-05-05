<?php
namespace plugins\Resource\validate;

use think\Validate;

class IndexValidate extends Validate
{
    protected $rule = [
        'name'          => 'require',
        'code'   => 'require',
        'sort'   => 'require'
    ];

    protected $message = [
        'name.require'          => "地区不能为空！",
        'code.require'       => "唯一标识不能为空",
        'sort.require'       => "排序不能为空"
    ];

}