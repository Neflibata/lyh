<?php
namespace plugins\Resource\controller;

use cmf\controller\PluginAdminBaseController;//引入此类
use think\Db;
use plugins\resource\validate\IndexValidate;
class CameraController extends PluginAdminBaseController
{
    protected $config = array(
        'operation_status' => array(
            '0'=>'未处理',
            '1'=>'已处理'
        ),
        'status_color' => array(
            '0'=>'red',
            '1'=>'LimeGreen'
        )
    );
    public function index()
    {
        $param=input('');
        $where = '1 = 1 ';
        if(!empty($param['text'])){
            switch ($param['search_key']) {
                case 1:
                    $where .= " and a.id = '".$param['text']."'";
                    break;
                case 2:
                    $where .= " and a.cameraName like '%".$param['text']."%'";
                    break;
                case 3:
                    $where .= " and d.name like '%".$param['text']."%'";
                    break;
                case 4:
                    $where .= " and c.name like '%".$param['text']."%'";
                    break;
                case 5:
                    $where .= " and b.dirName like '%".$param['text']."%'";
                    break;
            }
        }
        $list=Db::name("statistics_cameras")->alias("a")
            ->join("statistics_dir b","a.encodeDevIndexCode=b.indexCode","left")
            ->join("statistics_regions c","b.cameraIndexCode=c.indexCode","left")
            ->join("statistics_regions d","c.parentIndexCode=d.indexCode","left")
            ->where($where)
            ->field("a.*,b.dirName,c.name as subName,d.name")
            ->order("a.is_show desc,a.operation_status asc,a.id desc")
            ->paginate(20,false,['query'=>request()->param()]);
        $page = $list->render();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('operation_status',$this->config["operation_status"]);
        $this->assign("status_color",$this->config['status_color']);
        return $this->fetch('/camera/index');
    }
    public function edit()
    {
        $id=input('id');
        $find=Db::name('statistics_cameras')->alias('a')
            ->join("statistics_regions b","a.regionIndexCode=b.indexCode","left")
            ->join("statistics_regions c","b.parentIndexCode=c.indexCode","left")
            ->join("statistics_dir d","a.encodeDevIndexCode=d.indexCode","left")
            ->field("a.*,b.name as subName,b.indexCode as subCode,c.indexCode,d.id as dir_id,c.name,d.dirName")
            ->where('a.id',$id)
            ->find();
        $def_reg=Db::name('statistics_regions')->where("parentIndexCode","root000000")->where("type",1)->where('is_show',1)->select();
        $sub_reg=Db::name("statistics_regions")->where("parentIndexCode",$find['indexCode'])->select()->toArray();
        $def_dir=Db::name("statistics_dir")->where("cameraIndexCode",$find['subCode'])->select()->toArray();

        $this->assign('reg',$def_reg);
        $this->assign('subreg',$sub_reg);
        $this->assign('dir',$def_dir);
        $this->assign('find',$find);
        return $this->fetch('/camera/edit');
    }
    public function getSchool()
    {
        $param=input('');
        $list=Db::name('statistics_dir')->where('cameraIndexCode',$param['code'])->select();
        return zy_json_echo(true,'获取成功',$list,200);
    }
    public function update()
    {
        $param=input('');
        if(empty($param['cameraName'])){
            $this->error('请填写摄像点名');
        }
        if(empty($param["cameraIndexCode"])){
            $this->error('请填写监控点编号');
        }
        $is_up=Db::name('statistics_cameras')
            ->where('id',$param['id'])
            ->update([
                'regionIndexCode'=>$param['sub'],
                'cameraName'=>$param['cameraName'],
                'cameraIndexCode'=>$param["cameraIndexCode"],
                'encodeDevIndexCode'=>$param['dir'],
                'operation_status'=>1
            ]);
        if($is_up!==false){
            $this->success('修改成功');
        }else{
            $this->error('修改失败');
        }
    }
    public function delete()
    {
        $param=input('');
        if(empty($param['id'])){
            $this->error('参数错误');
        }
        $isde=Db::name('statistics_cameras')->where('id',$param['id'])->delete();
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
            Db::name('statistics_cameras')->where('id',$item)->delete();
        }
        $this->success('删除成功');
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
        $is_up=Db::name('statistics_cameras')->where('id',$param['id'])->update(['is_show'=>$param['code']]);
        if($is_up!==false){
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }

    }
    /**
     * 更新数据
     */
    public function renewal()
    {

        $postData = [
            "pageNo"=> 2,
            "pageSize"=> 1000,
            "treeCode"=> "0"
        ];
        $hk=new Haikang();
        $result = $hk->doCurl($postData, $hk->resource_cameras);
        $arr=json_decode($result,true);
        $list=Db::name('statistics_cameras')->select();
        if(!isset($arr['data']['list'])){
            $this->error('获取失败，请重试');
        }
        $num=0;
        foreach ($arr['data']['list'] as $k=>$v){
            $i=0;
            foreach ($list as $key=>$val){
                if($v['cameraIndexCode']==$val['cameraIndexCode']){
                    $i=1;
                }
            }
            if($i<1){
                Db::name('statistics_cameras')->insert($v);
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
     * 批量标记已处理
     */
    public function disposeArr()
    {
        $param=input('');
        if(empty($param['arr'])){
            $this->error('id不存在！');
        }
        foreach ($param['arr'] as $item) {
            Db::name('statistics_cameras')->where('id',$item)->update(["operation_status"=>1]);
        }
        $this->success('标记成功');
    }
    /**
     * 批量隐藏
     */
    public function hideArr()
    {
        $param=input('');
        if(empty($param['arr'])){
            $this->error('id不存在！');
        }
        foreach ($param['arr'] as $item) {
            Db::name('statistics_cameras')->where('id',$item)->update(["is_show"=>0]);
        }
        $this->success('操作成功');
    }
    /**
     * 批量显示
     */
    public function showArr()
    {
        $param=input('');
        if(empty($param['arr'])){
            $this->error('id不存在！');
        }
        foreach ($param['arr'] as $item) {
            Db::name('statistics_cameras')->where('id',$item)->update(["is_show"=>1]);
        }
        $this->success('操作成功');
    }
    public function sortArr()
    {
        $param=input('');

        foreach ($param['arr'] as $k=>$v){
            Db::name('statistics_regions')->where('id',$v['id'])->update(['sort'=>$v['sort']]);
        }
        $this->success("操作成功", '');
    }

    /**
     * 修改-根据区域获取下拉框目录
     */
    public function getSubReg()
    {
        $param=input('');
        $list["sub"]=Db::name("statistics_regions")->where("parentIndexCode",$param['code'])->select();
        $list["dir"]=[];
        foreach ($list['sub'] as $k=>$v){
            if($v['name']=="学校"){
                $list["dir"]=Db::name("statistics_dir")->where("cameraIndexCode",$v['indexCode'])->select();
            }
        }
        return zy_json_echo(true,'获取成功',$list,200);
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
