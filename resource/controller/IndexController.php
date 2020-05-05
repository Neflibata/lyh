<?php
namespace plugins\Resource\controller;

use cmf\controller\PluginAdminBaseController;//引入此类
use think\Db;
use plugins\resource\validate\IndexValidate;
class IndexController extends PluginAdminBaseController
{

    public function index()
    {
        $param=input('');
        $where=[];
//        $where['parentIndexCode']=['eq','root000000'];
//        $where['abbr']=['exp','is not null'];//菜单不要第一级
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
            ->order('parentIndexCode desc,sort asc,is_show desc,id asc')
            ->select()
            ->each(function($item, $key){
                $item['parent_name'] = Db::name('statistics_regions')->where('indexCode',$item['parentIndexCode'])->value('name');
                return $item;
            });
        $this->assign('list',$list);
        return $this->fetch('/index/index');
    }
    public function create()
    {
        $select=Db::name('statistics_regions')->where('abbr','not null')->select();
        $this->assign('select',$select);
        return $this->fetch('/index/create');
    }
    public function edit()
    {
        $id=input('id');
        $find=Db::name('statistics_regions')->where('id',$id)->find();
//        $find['par_name']=Db::name('statistics_regions')->where('indexCode',$find['parentIndexCode'])->value('name');
        $select=Db::name('statistics_regions')->where('abbr','not null')->select();
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
        $indexV=new IndexValidate();
        if(!$indexV->check($param)){
            $this->error($indexV->getError());
        }
        $is_up=Db::name('statistics_regions')
            ->where('id',$param['id'])
            ->update([
                'parentIndexCode'=>trim($param['parentIndexCode']),
                'name'=>$param['name'],
                'sort'=>$param['sort'],
                'abbr'=>$param['py'],
                'indexCode'=>$param['code']
            ]);
        if($is_up!==false){
            $this->success('修改成功');
        }else{
            $this->error('修改失败');
        }
    }
    public function insert()
    {
        $param=input('');
        $indexV=new IndexValidate();
        if(!$indexV->check($param)){
            $this->error($indexV->getError());
        }
        $is_in=Db::name('statistics_regions')
            ->insert([
                'parentIndexCode'=>trim($param['parentIndexCode']),
                'name'=>$param['name'],
                'sort'=>$param['sort'],
                'abbr'=>$param['py'],
                'indexCode'=>$param['code']
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

    /**
     * 更新数据
     */
    public function renewal()
    {

        $postData = [
            "pageNo" => 1,
            "pageSize" => 200,
            "treeCode" => "0"
        ];
        $hk=new Haikang();
        $result = $hk->doCurl($postData, $hk->all_tree);
        $arr=json_decode($result,true);
        $list=Db::name('statistics_regions')->select();
        if(!isset($arr['data']['list'])){
            $this->error('获取失败，请重试');
        }
        $num=0;
        foreach ($arr['data']['list'] as $k=>$v){
            $i=0;
            foreach ($list as $key=>$val){
                if($v['indexCode']==$val['indexCode']){
                    $i=1;
                }
            }
            if($i<1){
                Db::name('statistics_regions')->insert($v);
                $num++;
            }
        }
        if($num>0){
            $this->success('更新成功，新增'.$num."条数据");
        }else{
            $this->error('未发现新的数据');
        }

    }

    /**
     * 显示隐藏
     */
    public function upStatus()
    {
        $param=input('');
        if(empty($param['id'])){
            $this->error('参数错误');
        }
        $is_up=Db::name('statistics_regions')->where('id',$param['id'])->update(['is_show'=>$param['code']]);
        if($is_up!==false){
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }

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
