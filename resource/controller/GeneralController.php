<?php
namespace plugins\Resource\controller;

use cmf\controller\PluginAdminBaseController;//引入此类
use think\Db;

class GeneralController extends PluginAdminBaseController
{
    /**
     * 学校信息
     */
    public function index()
    {
        $list=Db::name('statistics_general')->order('sort asc,id asc')->select();
        $this->assign('list',$list);
        return $this->fetch('/general/index');

    }
    public function create()
    {
        return $this->fetch('/general/create');
    }
    public function insert()
    {
        $param=input('');
        $is_in=Db::name('statistics_general')->insert([
            'title'=>$param['title'],
            'value'=>$param['value'],
            'sort'=>$param['sort'],
            'create_time'=>time()
        ]);
        if($is_in>0){
            $this->success('添加成功');
        }else{
            $this->error('添加失败，请重试');
        }
    }
    public function upStatus()
    {
        $param=input('');
        if(empty($param['id'])){
            $this->error('参数错误');
        }
        $is_up=Db::name('statistics_general')->where('id',$param['id'])->update(['show_status'=>$param['code']]);
        if($is_up!==false){
            $this->success('操作成功');
        }else{
          $this->error('操作失败');
        }
    }
    public function edit()
    {
        $param=input('');
        $find=Db::name('statistics_general')->where('id',$param['id'])->find();
        $this->assign('find',$find);
        return $this->fetch('/general/edit');
    }
    public function update()
    {
        $param=input('');
        if(empty($param['id'])){
            $this->error('参数错误');
        }
        $is_up=Db::name('statistics_general')->where('id',$param['id'])->update([
            'title'=>$param['title'],
            'value'=>$param['value'],
            'sort'=>$param['sort']
        ]);
        if($is_up!==false){
            $this->success('修改成功');
        }else{
            $this->error('修改失败，请重试');
        }
    }
    public function deleteArr()
    {
        $param=input('');
        if(empty($param['arr'])){
            $this->error('id不存在！');
        }
        foreach ($param['arr'] as $item) {
            Db::name('statistics_general')->where('id',$item)->delete();
        }
        $this->success('删除成功');
    }
    public function sortArr()
    {
        $param=input('');
        foreach ($param['arr'] as $k=>$v){
            Db::name('statistics_general')->where('id',$v['id'])->update(['sort'=>$v['sort']]);
        }
        $this->success("操作成功", '');
    }
}
