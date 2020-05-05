<?php
namespace plugins\Resource\controller;

use cmf\controller\PluginAdminBaseController;//引入此类
use think\Db;
use plugins\Resource\controller\Py;
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
            ->join('cmf_statistics_regions c','b.parentIndexCode=c.indexCode','LEFT')
            ->where($where)
//            ->where('b.parentIndexCode','root000000')
            ->field('a.*,c.name,c.id as r_id')
            ->paginate(20,false,['query'=>request()->param()]);
        $page = $dir->render();
        $this->assign('list',$dir);
        $this->assign('page',$page);
        return $this->fetch('/school/index');
    }
    public function create()
    {
        $select=Db::name('statistics_regions')->where("parentIndexCode","root000000")->where("type",1)->where('is_show',1)->select();
        $this->assign('select',$select);
        return $this->fetch('/school/create');
    }
    public function insert()
    {
        $param=input('');
        if(empty($param['dir_name'])){
            $this->error('请输入学校名称');
        }
        $indexCode=Db::name('statistics_regions')->where("parentIndexCode",$param["indexCode"])->where("name","学校")->find();
        $is_in=Db::name('statistics_dir')->insert([
                "cameraIndexCode"=>$indexCode["indexCode"],
                "dirName"=>$param["dir_name"],
                'school_cover'=>empty($param['img_url'])?'':$param['img_url'],
                'student_num'=>$param['student_num'],
                'company'=>$param['company'],
                'street'=>$param['street'],
                'personCharge'=>$param['personCharge'],
                'personChargePhone'=>$param['personChargePhone'],
                'canteen_nickname'=>$param['canteen_nickname'],
                'canteen_phone'=>$param['canteen_phone'],
                'teacher_num'=>$param['teacher_num'],
                'canteen_num'=>$param['canteen_num'],
                "longitude"=>$param["longitude"],
                "latitude"=>$param["latitude"],
                'dir_abbr'=>$param['py'],
                'char_abbr'=>$param['mc']
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
        $find=Db::name('statistics_dir')->alias("a")
            ->join("statistics_regions b","a.cameraIndexCode=b.indexCode","left")
            ->join("statistics_regions c","b.parentIndexCode=c.indexCode","left")
            ->where('a.id',$id)
            ->field("a.*,c.indexCode as code")
            ->find();
        $select=Db::name('statistics_regions')->where("parentIndexCode","root000000")->where("type",1)->where('is_show',1)->select();
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
            'cameraIndexCode'=>$param['cameraIndexCode'],
            'encodeDevIndexCode'=>$param['encodeDevIndexCode'],
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
        $find['dirName']=Db::name('statistics_dir')->where('indexCode',$find['encodeDevIndexCode'])->value('dirName');
        $this->assign('vo',$find);
        return $this->fetch('/school/edit_camer');
    }
    public function updateCamer()
    {
        $param=input('');
        $is_up=Db::name('statistics_cameras')
            ->where('id',$param['id'])
            ->update([
                'cameraName'=>$param['camera_name'],
                'cameraIndexCode'=>$param['cameraIndexCode'],
                'encodeDevIndexCode'=>$param['encodeDevIndexCode'],
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
        if(empty($param['dir_name'])){
            $this->error('请输入学校名称');
        }
        $indexCode=Db::name('statistics_regions')->where("parentIndexCode",$param["indexCode"])->where("name","学校")->find();
        $is_up=Db::name('statistics_dir')
            ->where('id',$param['id'])
            ->update([
                "cameraIndexCode"=>$indexCode['indexCode'],
                "dirName"=>$param["dir_name"],
                'school_cover'=>empty($param['img_url'])?'':$param['img_url'],
                'student_num'=>$param['student_num'],
                'company'=>$param['company'],
                'street'=>$param['street'],
                'personCharge'=>$param['personCharge'],
                'personChargePhone'=>$param['personChargePhone'],
                'canteen_nickname'=>$param['canteen_nickname'],
                'canteen_phone'=>$param['canteen_phone'],
                'teacher_num'=>$param['teacher_num'],
                'canteen_num'=>$param['canteen_num'],
                "longitude"=>$param["longitude"],
                "latitude"=>$param["latitude"],
                'dir_abbr'=>$param['py'],
                'char_abbr'=>$param['mc']
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
        Db::name('statistics_cameras')->where('dir_id',$id)->delete();
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
        $dir=Db::name('statistics_dir')->where("id",$id)->find();
        $list=Db::name('statistics_cameras')->alias('a')
            ->join('cmf_statistics_regions b','a.regionIndexCode=b.indexCode','LEFT')
            ->join('cmf_statistics_regions c','b.parentIndexCode=c.indexCode','LEFT')
            ->field('a.id,a.cameraName,c.name,c.indexCode')
            ->where('a.encodeDevIndexCode',$dir['indexCode'])
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
    public function map(){
        $param=input('');

        $find=Db::name('statistics_dir')->where('id',$param['id'])->find();
        $reg=Db::name('statistics_regions')->where("parentIndexCode","root000000")->where("type",1)->where('is_show',1)->select();

        $dir=Db::name('statistics_dir')->where('cameraIndexCode',$param['indexCode'])->select();
        $this->assign('find',$find);
        $this->assign('reg',$reg);
        $this->assign('dir',$dir);
        return $this->fetch('/map/index');
    }
    public function getSchool()
    {
        $param=input('');
        $list=Db::name('statistics_dir')->where('cameraIndexCode',$param['code'])->select();
        return zy_json_echo(true,'获取成功',$list,200);
    }
    public function upxy()
    {
        $param=input('');
        if(empty($param['id'])||empty($param['long'])||empty($param['lat'])){
            $this->error('参数错误');
        }
        $is_up=Db::name('statistics_dir')
            ->where('id',$param['id'])
            ->update([
                'longitude'=>$param['long'],
                'latitude'=>$param['lat'],
                'dir_abbr'=>$param['py'],
                'char_abbr'=>$param['mc']
            ]);
        if($is_up!==false){
            $this->success('修改成功');
        }else{
            $this->error('修改失败，请重试');
        }
    }
    /**
     * 更新学校数据
     */
    public function renewal()
    {

        $list=Db::name("statistics_regions")->where("parentIndexCode","<>","root000000")->select();
//        halt($list);
        $hk=new Haikang();
        foreach ($list as $k=>$v){
            $postData = [
                "regionIndexCode"=>$v['indexCode'],
                "resourceType"=>"encodeDevice",
                "pageSize"=>1000,
                "pageNo"=>1
            ];

            $result = $hk->doCurl($postData, $hk->sub_resources);
            $arr=json_decode($result,true);
            $dir=Db::name("statistics_dir")->select();
            $num=0;
            foreach ($arr['data']['list'] as $k=>$v){
                $i=0;
                foreach ($dir as $key=>$val){
                    if($v['indexCode']==$val['indexCode']){
                        $i=1;
                    }
                }
                if($i<1){
                    Db::name('statistics_dir')->insert([
                        "indexCode"=>$v['indexCode'],
                        "dirName"=>$v['name'],
                        "regionIndexCode"=>$v['regionIndexCode'],
                        "cameraIndexCode"=>$v['regionIndexCode'],
                        "dir_abbr"=>Py::utf8_to($v['name'],true)
                    ]);
                    $num++;
                }
            }

        }
        if($num>0){
            $this->success('更新成功，新增'.$num."条数据");
        }else{
            $this->error('未发现新的数据');
        }
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
