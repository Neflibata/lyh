<?php
namespace plugins\statistics\validate;
use think\Validate;

class AdminEmphasisValidate extends Validate
{
    protected $rule = [
        'user_name'          => 'require|chs',
        'id_card'       => 'require|alphaNum',
        'face_img'   => 'require',
    ];
    protected $message = [
        'user_name.require'          => "请输入用户名",
        'user_name.chs'          => "请检查姓名",
        'id_card.require'       => "请输入身份证信息",
        'id_card.alphaNum'       => "请检查身份证格式",
        'face_img.require'       => "请上传图片"
    ];
}