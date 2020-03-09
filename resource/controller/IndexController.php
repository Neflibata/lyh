<?php
namespace plugins\Resource\controller;

use cmf\controller\PluginAdminBaseController;//引入此类
use think\Db;

class IndexController extends PluginAdminBaseController
{

    public function index()
    {
        $list=Db::name('statistics_regions')->select();
        $newlist=[];
        foreach ($list as $k=>$v){
            $newlist[$k]=$v;
            foreach ($list as $key=>$val){
                if($v['parentIndexCode']==$val['indexCode']){
                    $newlist[$k]['parent_name']=$val['name'];
                    break;
                }
            }
        }
//        halt($newlist);
        $this->assign('list',$newlist);
        return $this->fetch('/index/index');
    }
    function getTree($arr,$parent_id){
        $tree = [];
        foreach ($arr as $k=>$v){
            if ($v['parentIndexCode'] == $parent_id){
                $v['son'] = $this->getTree($arr,$v['indexCode']);
                if ($v['son'] == null){
                    unset($v['son']);
                }
                $tree[] = $v;
            }
        }
        return $tree;
    }
}
