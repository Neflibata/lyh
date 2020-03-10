<?php
namespace plugins\Resource\controller;

use cmf\controller\PluginAdminBaseController;//引入此类
use think\Db;

class SchoolController extends PluginAdminBaseController
{
    /**
     * 学校信息
     */
    public function index()
    {
        $param=input('');
        $where=[];
        if(!empty($param['text'])){
            switch ($param['search_key']) {
                case 1:
                    $where['a.id']=['eq',$param['text']];
                    break;
                case 2:
                    $where['b.name']=['like','%'.trim($param['text']).'%'];
                    break;
                case 3:
                    $where['a.dirName']=['like','%'.trim($param['text']).'%'];
                    break;
            }
        }
        $dir=Db::name('statistics_dir')->alias('a')
            ->join('cmf_statistics_regions b','a.cameraIndexCode=b.indexCode','LEFT')
            ->where($where)
            ->field('a.*,b.name')
            ->paginate(20,false,['query'=>request()->param()]);
        $page = $dir->render();
        $this->assign('list',$dir);
        $this->assign('page',$page);
        return $this->fetch('/school/index');

    }
    public function create()
    {
        $select=Db::name('statistics_regions')->select();
        $this->assign('select',$select);
        return $this->fetch('/school/create');
    }
    public function insert()
    {
        $param=input('');
        $is_in=Db::name('statistics_dir')->insert([
                "cameraIndexCode"=>$param["indexCode"],
                "dirName"=>$param["school_name"],
                "longitude"=>$param["longitude"],
                "latitude"=>$param["latitude"]
                ]);
        if($is_in>0){
            $this->success('创建成功');
        }else{
            $this->error('创建失败');
        }
    }
    public function edit()
    {
        $id=input('id');
        $find=Db::name('statistics_dir')->where('id',$id)->find();
        $select=Db::name('statistics_regions')->select();
        $this->assign('vo',$find);
        $this->assign('select',$select);
        return $this->fetch('/school/edit');
    }
    public function createCamer()
    {
        $id=input('id');
        $find=Db::name('statistics_dir')->where('id',$id)->find();
        $this->assign('vo',$find);
        return $this->fetch('/school/create_camer');

    }
    public function insertCamer()
    {
        $param=input('');
        $is_in=Db::name('statistics_cameras')->insert([
            "dir_id"=>$param["id"],
            "cameraName"=>$param["camera_name"],
            "cameraTypeName"=>"枪机",
            "channelTypeName"=>"模拟通道",
            "cameraType"=>0,
            "regionIndexCode"=>Db::name('statistics_dir')->where('id',$param['id'])->value('cameraIndexCode')
        ]);
        if($is_in>0){
            $this->success('创建成功');
        }else{
            $this->error('创建失败');
        }
    }
    public function editCamer()
    {
        $id=input('id');
        $find=Db::name('statistics_cameras')->where('id',$id)->find();
        $find['dirName']=Db::name('statistics_dir')->where('id',$find['dir_id'])->value('dirName');
        $this->assign('vo',$find);
        return $this->fetch('/school/edit_camer');
    }
    public function updateCamer()
    {
        $param=input('');
        $is_up=Db::name('statistics_cameras')
            ->where('id',$param['id'])
            ->update([
                'cameraName'=>$param['camera_name']
            ]);
        if($is_up!==false){
            $this->success('修改成功');
        }else{
            $this->error('修改失败');
        }
    }
    public function update()
    {
        $param=input('');
        $is_up=Db::name('statistics_dir')
            ->where('id',$param['id'])
            ->update([
                'cameraIndexCode'=>trim($param['indexCode']),
                'dirName'=>$param['school_name'],
                "longitude"=>$param["longitude"],
                "latitude"=>$param["latitude"]
            ]);
        if($is_up!==false){
            $this->success('修改成功');
        }else{
            $this->error('修改失败');
        }
    }
    public function deleteDir()
    {
        $id=input('id');
        $is_de=Db::name('statistics_dir')->where('id',$id)->delete();
        if($is_de===false){
            $this->error('删除失败');
        }else{
            $this->success('删除成功');
        }
    }
    public function deleteCamer()
    {
        $id=input('id');
        $is_de=Db::name('statistics_cameras')->where('id',$id)->delete();
        if($is_de===false){
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
            Db::name('statistics_dir')->where('id',$item)->delete();
        }
        $this->success('删除成功');
    }
    /**
     *
     * 修改目录信息
     */
    public function editDir()
    {
        halt('123');
    }
    /**
     * 获取
     */
    public function getCameras()
    {
        $id=input('id');
        $list=Db::name('statistics_cameras')->alias('a')
            ->join('cmf_statistics_regions b','a.regionIndexCode=b.indexCode','LEFT')
            ->field('a.*,b.name,b.indexCode')
            ->where('dir_id',$id)
            ->select();

        if(count($list)<1){
            return zy_json_echo(false,'暂无数据',$list,-1);
        }
        return zy_json_echo(true,'获取成功',$list,200);
    }
    /**
     * 删除
     */
    public function delete()
    {

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
