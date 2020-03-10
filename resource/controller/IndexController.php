<?php
namespace plugins\Resource\controller;

use cmf\controller\PluginAdminBaseController;//引入此类
use think\Db;

class IndexController extends PluginAdminBaseController
{

    public function index()
    {
        $param=input('');
        $where=[];
        if(!empty($param['text'])){
            switch ($param['search_key']) {
                case 1:
                    $where['id']=['eq',$param['text']];
                    break;
                case 2:
                    $where['name']=['like','%'.trim($param['text']).'%'];
                    break;
                case 3:
                    $whereb['name']=['like','%'.trim($param['text']).'%'];
                    $indexCode=Db::name('statistics_regions')->where($whereb)->find();
                    $where['parentIndexCode']=$indexCode['indexCode'];
                    break;
            }
        }
        $list=Db::name('statistics_regions')
            ->where($where)
            ->order('sort asc,id asc')
            ->select()
            ->each(function($item, $key){
                $item['parent_name'] = Db::name('statistics_regions')->where('indexCode',$item['parentIndexCode'])->value('name');
                return $item;
            });
//        $newlist=[];
//        foreach ($list as $k=>$v){
//            $newlist[$k]=$v;
//            foreach ($list as $key=>$val){
//                if($v['parentIndexCode']==$val['indexCode']){
//                    $newlist[$k]['parent_name']=$val['name'];
//                    break;
//                }
//            }
//        }
        $this->assign('list',$list);
        return $this->fetch('/index/index');
    }
    public function create()
    {
        $select=Db::name('statistics_regions')->select();
        $this->assign('select',$select);
        return $this->fetch('/index/create');
    }
    public function edit()
    {
        $id=input('id');
        $find=Db::name('statistics_regions')->where('id',$id)->find();
//        $find['par_name']=Db::name('statistics_regions')->where('indexCode',$find['parentIndexCode'])->value('name');
        $select=Db::name('statistics_regions')->select();
        $this->assign('vo',$find);
        $this->assign('select',$select);
        return $this->fetch('/index/edit');
    }
    public function update()
    {
        $param=input('');
        if($param['id']==1){
            $this->error('不可修改当前地区');
        }
        $is_up=Db::name('statistics_regions')
            ->where('id',$param['id'])
            ->update(['parentIndexCode'=>trim($param['parentIndexCode']),'name'=>$param['name'],'sort'=>$param['sort']]);
        if($is_up!==false){
            $this->success('修改成功');
        }else{
            $this->error('修改失败');
        }
    }
    public function insert()
    {
        $param=input('');

        $is_in=Db::name('statistics_regions')
            ->insert([
                'parentIndexCode'=>trim($param['parentIndexCode']),
                'name'=>$param['name'],
                'sort'=>$param['sort'],
                'indexCode'=>$this->getfour_str(8).'-'.$this->getfour_str(4).'-'.$this->getfour_str(4).'-'.$this->getfour_str(12)
            ]);
        if($is_in>0){
            $this->success('创建成功');
        }else{
            $this->error('创建失败');
        }
    }
    public function delete()
    {
        $param=input('');
        if(empty($param['id'])){
            $this->error('参数错误');
        }
        $find=Db::name('statistics_regions')->where('id',$param['id'])->find();
        if(!empty($find)){
            $is_exist =Db::name('statistics_regions')->where('parentIndexCode',$find['indexCode'])->find();
            if(!empty($is_exist)){
                $this->error('请先删除下级区域');
            }
        }
        $isde=Db::name('statistics_regions')->where('id',$param['id'])->delete();
        if($isde===false){
            $this->error('删除失败');
        }else{
            $this->success('删除成功');
        }
    }
    /**
     * 批量删除
     */
    public function deleteArr()
    {
        $param=input('');
        if(empty($param['arr'])){
            $this->error('id不存在！');
        }
        foreach ($param['arr'] as $item) {
            $find=Db::name('statistics_regions')->where('id',$item)->find();
            if(!empty($find)){
                $is_exist =Db::name('statistics_regions')->where('parentIndexCode',$find['indexCode'])->find();
                if(!empty($is_exist)){
                    $this->error('请先删除下级区域');
                }
            }
            Db::name('statistics_regions')->where('id',$item)->delete();
        }
        $this->success('删除成功');
    }
    public function sortArr()
    {
        $param=input('');

        foreach ($param['arr'] as $k=>$v){
            Db::name('statistics_regions')->where('id',$v['id'])->update(['sort'=>$v['sort']]);
        }
        $this->success("操作成功", '');
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
    /**
     * 取随机数方式
     *key  0所有1数字
     */
    public function getfour_str($len)
    {
        $chars_array = array(
            "0", "1", "2", "3", "4", "5", "6", "7", "8", "9",
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
            "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
            "w", "x", "y", "z",
        );
        $charsLen = count($chars_array) - 1;
        $outputstr = "";
        for ($i=0; $i<$len; $i++)
        {
            $outputstr .= $chars_array[mt_rand(0, $charsLen)];
        }
        return $outputstr;
    }
}
