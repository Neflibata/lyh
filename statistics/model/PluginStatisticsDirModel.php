<?php
/**
 * @Copyright 2019-2019 shuibo All Rights Reserved.
 *Support:http://www.shuibo.net
 * @Author:zfy
 * @Version 1.0 2019/10/11
 */
namespace plugins\statistics\model;

use think\Model;
class PluginStatisticsDirModel extends Model
{
    protected $table="cmf_statistics_dir";
    protected $pk = 'id';
    public function Cameras()
    {
        return $this->hasMany('PluginStatisticsCamerasModel','dir_id','id');
    }
}
