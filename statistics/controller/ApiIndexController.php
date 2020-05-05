<?php
namespace plugins\statistics\controller;
use cmf\controller\PluginRestBaseController;//引用插件基类
use think\Db;
use think\Request;
use plugins\statistics\model\BaseModel as base;
use plugins\statistics\validate\AdminEmphasisValidate;
use GatewayClient\Gateway;
/**
 * api控制器
 */
class ApiIndexController extends PluginRestBaseController
{
    public $hrefPath;
    public $uploadPath;
    //首页数据
    protected $config = array(
        'base_img' => 'http://111.3.64.34:81/ngx/proxy?i='//访问海康图片地址 base后这后面 原446
    );
    /**
     * 执行构造
     */
    function __construct()
    {
        header("content-type:text/html;charset=utf-8");
        Config('app_trace',false);
        parent::__construct();
        $this->hrefPath = ZY_APP_PATH."uploadFile/";
        $this->uploadPath = ROOT_PATH.'public/uploadFile';
    }

    public function index($isModule=false)//index(命名规范)
    {

    }
    /**
     *  提交人员资料
     */
    public function upData()
    {

        $data = $this->request->post();
        $neadArg = ["nickname"=>[true, 0, "请填写姓名"], "company"=>[true, 0, "请填写公司名称"], "mobile"=>[true, 1, "请填写手机号"], "face_thumb"=>[true, 0, "请上传人脸照片"],"health_card"=>[true, 0, "请上传健康证照片"] , "health_endtime"=>[true, 0, "请填写健康证到期时间"], "member_type"=>[true, 0, "请填写人员类别"],"health_id_card"=>[true, 0, "请输入健康证号"], "id_card"=>[true, 0,"请输入身份证号"]];
        $dataInfo = checkArg($neadArg, $data);
        $id_card = array_pop($dataInfo);
        $model = new base("member_info");
        $res = $model->get_one(["id_card"=>["=", $id_card]]);
        $upOrIn = !empty($res)?true:false; //true是数据库没有该数据，其他是有
        $dataInfo["addtime"] =time();
        $thumb_name = $dataInfo["company"] ."_". $dataInfo["nickname"];
        $retData = $this->uploadDistant($dataInfo["company"], $thumb_name, $this->hrefPath.$dataInfo["face_thumb"], $id_card, $upOrIn);
        if($retData[0])
        {
            $face_before = preg_replace("/[0-9|A-Z|a-z]+\./", "人脸_".$thumb_name.".", $dataInfo["face_thumb"]);
            $health_before = preg_replace("/[0-9|A-Z|a-z]+\./", "健康证_".$thumb_name.".", $dataInfo["health_card"]);
            if(empty($face_before) || empty($health_before))
                return zy_json_echo(false,"图片地址错误");
            rename($this->uploadPath."/".$dataInfo["face_thumb"], $this->uploadPath."/".iconv("utf-8", "gb2312",$face_before));
            rename($this->uploadPath."/".$dataInfo["health_card"], $this->uploadPath."/".iconv("utf-8", "gb2312",$health_before));
            $dataInfo["face_thumb"] = $face_before;
            $dataInfo["health_card"] = $health_before;
            $dataInfo['school_id']=Db::name('statistics_dir')->where('dirName',$dataInfo["company"])->value('id');
            $dataInfo['timeStr']=date("Y-m-d H:i:s",$dataInfo["addtime"]);
            if($upOrIn == 0)
            {
                $dataInfo["id_card"] = $id_card;
                $dataInfo["indexCode"]=$retData[1]['data']['indexCode'];
                $dataInfo["faceGroupIndexCode"]=$retData[1]['data']['faceGroupIndexCode'];
                $model->insert($dataInfo);
            }
            else
                $model->update($dataInfo, ["id_card"=>["=", $id_card]]);
            return zy_json_echo(true,"上传成功", $dataInfo);
        }
        else
        {
            return zy_json_echo(false, $retData[1]);
        }

    }

    private function uploadDistant($company, $thumb_name, $imgUrl, $id_card, $upOrIn = false){
        $member_upload_info = new base("member_upload_info");
        $info = $member_upload_info->get_one(["company"=>$company]);
        $haikang = new Haikang();
        $time = date("Y-m-d H:i:s",time());
        if(empty($info))
        {
            $groupInfo = $haikang->search_group($company);
            if($groupInfo["code"] == "0")
            {
                if(count($groupInfo["data"]) > 0)
                {
                    $indexCode = $groupInfo["data"][0]["indexCode"];
                }
                else
                {
                    $groupRetData = $haikang->add_group($company);
                    if($groupRetData["code"] == "0")
                        $indexCode = $groupRetData["data"]["indexCode"];
                    else
                        return [false, $groupRetData["msg"]];
                }
            }
            else
                return [false, $groupInfo["msg"]];
        }
        else
            $indexCode = $info["index_code"];
        if(!$upOrIn)
            $retData = $haikang->add_face_img($indexCode, ["name"=>$thumb_name,"certificateType"=>111,"certificateNum"=>$id_card], $imgUrl);
        else
        {
            $member_upload_log = new base("member_upload_log");
            $member_upload_log_info = $member_upload_log->get_one(["id_card"=>["=", $id_card ]]);
            $retData = $haikang->update_face_img($member_upload_log_info["face_index_code"], ["name"=>$thumb_name,"certificateType"=>111,"certificateNum"=>$id_card], $imgUrl);
        }
        if($retData["code"] == "0"  )
        {
            if(!$upOrIn){
                $member_upload_log = new base("member_upload_log");
                $member_upload_log->insert(["nickname"=>$thumb_name,"company"=>$company, "data"=>json_encode($retData["data"]), "face_index_code"=>$retData["data"]["indexCode"], "addtime"=>$time, "id_card"=>$id_card]);
                if(empty($info))
                    $member_upload_info->insert(["company"=>$company, "index_code"=>$indexCode, "addtime"=>$time]);
                else
                    $member_upload_info->update(["add_num"=>["+=", 1]], ["MUIID"=>["=", $info["MUIID"]]]);
            }

        }
        else {
            return [false, $retData["msg"]];
        }
        return [true, $retData];

    }

    public function uploadimg(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        //exit(dump($file));
        // 移动到框架应用根目录/public/uploads/ 目录下
        $file->validate(["ext"=>"jpg", "size"=>'204800']);
        if($file){
            $outpath = $this->uploadPath;
            $info = $file->move($outpath);
            if($info){
                // 成功上传后 获取上传信息
                // 输出 jpg
                //echo $info->getExtension();
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg

                $data = [
                    'code'=> 0,
                    'msg' => '',
                    'data' => [
                        'src'=> str_replace("\\","/",$info->getSaveName())
                    ]
                ];

                return zy_json_echo(true,$data);
                // 输出 42a79759f284b767dfcb2a0197904287.jpg
                //echo $info->getFilename();
            }else{
                // 上传失败获取错误信息
                return zy_json_echo(false, $file->getError());
            }
        }
    }
    /**
     * 地图展示-左侧菜单3级
     */
    public function getRegionCatalog()
    {
        $where=[];
//        $where['parentIndexCode']=['eq','root000000'];
//        $where['abbr']=['exp','is not null'];//菜单不要第一级
        $regions=Db::name('statistics_regions')
            ->where("is_show",1)
            ->field('*,name as label')
            ->order('id asc')->select()->toArray();
        $dir=Db::name('statistics_dir')->field('*,dirName as label')->select()->toArray();
        $cameras=Db::name('statistics_cameras')->where("is_show",1)->field('*,cameraName as label')->select()->toArray();
        foreach ($regions as $k=>$v){
            $code=0;
            foreach ($dir as $ke=>$val){
                if($v['indexCode']==$val['cameraIndexCode']){
                    $regions[$k]['children'][$code]=$val;
                    foreach ($cameras as $key=>$value){
                        if($value['dir_id']==$val['id']){
                            $regions[$k]['children'][$code]['children'][]=$value;
                        }
                    }
                    $code++;
                }
            }
            if(isset($regions[$k]['children'])){
                $i=count($regions[$k]['children']);
                $regions[$k]['children'][$i]['id']=-1;
                $regions[$k]['children'][$i]['label']="其他";
                $regions[$k]['children'][$i]['cameraIndexCode']=$v['indexCode'];
                $regions[$k]['children'][$i]['children']=Db::name('statistics_cameras')
                    ->where("regionIndexCode",$v['indexCode'])
                    ->where('dir_id','null')
                    ->where("is_show",1)
                    ->field("*,cameraName as label")
                    ->select();
            }
        }
        return zy_json_echo(true,'获取成功',$regions,200);
    }
    /**
     * 地图展示-左侧菜单3级
     */
    public function getSchoolDir()
    {
        $where=[];
//        $where['parentIndexCode']=['eq','root000000'];
//        $where['abbr']=['exp','is not null'];//菜单不要第一级
        $regions=Db::name('statistics_regions')->where("parentIndexCode","root000000")->where("type",1)->where('is_show',1)->field('*,name as label')->order('id asc')->select()->toArray();
//       $regions=Db::name('statistics_regions')->where($where)->field('*,name as label')->order('id asc')->select()->toArray();
        $dir=Db::name('statistics_dir')->field('*,dirName as label')->select()->toArray();
        $cameras=Db::name('statistics_cameras')->field('*,cameraName as label')->select()->toArray();
        foreach ($regions as $k=>$v){
            $code=0;
            foreach ($dir as $ke=>$val){
                if($v['indexCode']==$val['cameraIndexCode']){
                    $regions[$k]['children'][$code]=$val;
                    foreach ($cameras as $key=>$value){
                        if($value['dir_id']==$val['id']){
                            $regions[$k]['children'][$code]['last_child'][]=$value;
                        }
                    }
                    $code++;
                }
            }
            
        }
        return zy_json_echo(true,'获取成功',$regions,200);
    }
    /**
     * 学校目录id获取摄像点
     */
    public function getCameras($id=null)
    {
        if(empty($id)){
            return zy_json_echo(false,'参数错误','',-1);
        }
        $list=Db::name('statistics_cameras')->where('dir_id',$id)->select();
        return zy_json_echo(true,'获取成功',$list,200);
    }
    /**
     * 管理员后台-学校列表
     */
    public function getShoolList()
    {
        $list=Db::name('statistics_dir')->alias('a')
            ->join('cmf_statistics_regions b','a.cameraIndexCode=b.indexCode','left')
            ->select();
        return zy_json_echo(true,'获取成功',$list,200);
    }
    /**
     * 接收事件-保存陌生人
     */
    public function addStranger()
    {
        //模拟数据
        $param=input('');
        $data = $param['params']['events'];
        foreach ($data as $k=>$v){
            $cameras=Db::name('statistics_cameras')->where('encodeDevIndexCode',$v['data']['resInfo'][0]['indexCode'])->find();
            $dir_id=empty($cameras)?0:$cameras['dir_id'];
            $id=Db::name('statistics_face_stranger')->insertGetId([
                'ageGroup' => $v['data']['faceRecognitionResult']['snap']['ageGroup'],
                'gender' => $v['data']['faceRecognitionResult']['snap']['gender'],
                'glass' => $v['data']['faceRecognitionResult']['snap']['glass'],
                'bkgUrl' => $this->config['base_img'].base64_encode($v['data']['faceRecognitionResult']['snap']['bkgUrl']),
                'faceUrl' => $this->config['base_img'].base64_encode($v['data']['faceRecognitionResult']['snap']['faceUrl']),
                'faceTime' => $v['data']['faceRecognitionResult']['snap']['faceTime'],
                'resourceType' => $v['data']['resInfo'][0]['resourceType'],
                'indexCode' => $v['data']['resInfo'][0]['indexCode'],
                'cn' => $v['data']['resInfo'][0]['cn'],
                'srcIndex'=>$v['srcIndex'],
                'originalData'=>serialize($v['data']),
                'dir_id'=>$dir_id
            ]);
            $find=Db::name('statistics_face_stranger')->where('id',$id)->find();
            $find['dirName']=Db::name('statistics_dir')->where('id',$dir_id)->value('dirName');
            //添加到统计表
            $stat=Db::name('statistics_dir_stat')->where('dir_id',$dir_id)->where('month_section',date('Y-m', time()))->find();
            if(empty($stat)){
                Db::name('statistics_dir_stat')->insert([
                    'dir_id'=>$dir_id,
                    'cameraIndexCode'=>$cameras['regionIndexCode'],
                    'month_section'=>date('Y-m', time()),
                    'stranger_face_num'=>1
                ]);
            }else{
                Db::name('statistics_dir_stat')
                    ->where('dir_id',$dir_id)
                    ->where('month_section',date('Y-m', time()))
                    ->update(['stranger_face_num'=>$stat['stranger_face_num']+1]);
            }
            $module_info = getModuleConfig('statistics','config','config.json');
            $module_info = json_decode($module_info,true);
            $find['ageGroup']=$module_info['age'][$find['ageGroup']];
            $find['glass']=$module_info['glasses'][$find['glass']];
            $find['gender']=$module_info['sex'][$find['gender']];
            //提醒警报
            $msg=json_encode([
                'type'=>'stranger',
                'describe'=>'发现陌生人',
                'content'=> $find
            ]);
            $user=Db::name('user')->where('user_type','gt',1)->where('find_in_set('.$dir_id.',school)')->column('id');
            Gateway::sendToGroup([1],$msg);
            Gateway::sendToUid($user, $msg);
//            Gateway::sendToAll($msg);
        }
    }
    /**
     * 接收事件-保存重点人员
     */
    public function addEmphasis()
    {
        $param=input('');
        $data = $param['params']['events'];
        foreach ($data as $k=>$v){
            $cameras=Db::name('statistics_cameras')->where('cameraIndexCode',$v['data']['resInfo'][0]['indexCode'])->find();
            $dir_id=empty($cameras)?0:$cameras['dir_id'];
            $id=Db::name('statistics_face_emphasis')->insertGetId([
                'ageGroup' => $v['data']['faceRecognitionResult']['snap']['ageGroup'],
                'gender' => $v['data']['faceRecognitionResult']['snap']['gender'],
                'glass' => $v['data']['faceRecognitionResult']['snap']['glass'],
                'bkgUrl' => $this->config['base_img'].base64_encode($v['data']['faceRecognitionResult']['snap']['bkgUrl']),
                'faceUrl' => $this->config['base_img'].base64_encode($v['data']['faceRecognitionResult']['snap']['faceUrl']),
                'faceTime' => $v['data']['faceRecognitionResult']['snap']['faceTime'],
                'faceGroupCode' => $v['data']['faceRecognitionResult']['faceMatch'][0]['faceGroupCode'],
                'faceGroupName' => $v['data']['faceRecognitionResult']['faceMatch'][0]['faceGroupName'],
                'faceInfoCode' => $v['data']['faceRecognitionResult']['faceMatch'][0]['faceInfoCode'],
                'faceInfoName' => $v['data']['faceRecognitionResult']['faceMatch'][0]['faceInfoName'],
                'faceInfoSex' => $v['data']['faceRecognitionResult']['faceMatch'][0]['faceInfoSex'],
                'certificate' => $v['data']['faceRecognitionResult']['faceMatch'][0]['certificate'],
                'similarity' => $v['data']['faceRecognitionResult']['faceMatch'][0]['similarity'],
                'facePicUrl' => $v['data']['faceRecognitionResult']['faceMatch'][0]['facePicUrl'],
                'srcEventId' => $v['data']['srcEventId'],
                'resourceType' => $v['data']['resInfo'][0]['resourceType'],
                'indexCode' => $v['data']['resInfo'][0]['indexCode'],
                'cn' => $v['data']['resInfo'][0]['cn'],
                'originalData'=>serialize($v['data']),
                'dir_id'=>$dir_id
            ]);
            $find=Db::name('statistics_face_emphasis')->where('id',$id)->find();
            $find['dirName']=Db::name('statistics_dir')->where('id',$dir_id)->value('dirName');
            //更新统计表
            $stat=Db::name('statistics_dir_stat')->where('dir_id',$dir_id)->where('month_section',date('Y-m', time()))->find();
            if(empty($stat)){
                Db::name('statistics_dir_stat')->insert([
                    'dir_id'=>$dir_id,
                    'cameraIndexCode'=>$cameras['regionIndexCode'],
                    'month_section'=>date('Y-m', time()),
                    'emphasis_face_num'=>1
                ]);
            }else{
                Db::name('statistics_dir_stat')
                    ->where('dir_id',$dir_id)
                    ->where('month_section',date('Y-m', time()))
                    ->update(['emphasis_face_num'=>$stat['emphasis_face_num']+1]);
            }
            //提醒警报
            $msg=json_encode([
                'type'=>'emphasis',
                'describe'=>'发现重点人员',
                'content'=> $find
            ]);
            Gateway::sendToGroup([1],$msg);
        }
    }
    /**
     * 接收事件-保存GPS
     */
    public function addGps(){
        $param = input('');
        $data = $param['params']['events']['data'];
        Db::name('statistics_face_gps')->insert([
            'dataType' => $data['dataType'],
            'recvTime' => $data['recvTime'],
            'sendTime' => $data['sendTime'],
            'dateTime' => $data['dateTime'],
            'ipAddress' => $data['ipAddress'],
            'portNo' => $data['portNo'],
            'channelID' => $data['channelID'],
            'eventType' => $data['eventType'],
            'eventDescription' => $data['eventDescription'],
            'deviceIndexCode' => $data['gpsCollectione']['targetAttrs']['deviceIndexCode'],
            'decodeTag' => $data['gpsCollectione']['targetAttrs']['decodeTag'],
            'cameraIndexCode' => $data['gpsCollectione']['targetAttrs']['cameraIndexCode'],
            'cameraType' => $data['gpsCollectione']['targetAttrs']['cameraType'],
            'longitude' => $data['gpsCollectione']['longitude'],
            'latitude' => $data['gpsCollectione']['latitude'],
            'time' => $data['gpsCollectione']['time'],
            'direction' => $data['gpsCollectione']['direction'],
            'directionEW' => $data['gpsCollectione']['directionEW'],
            'directionNS' => $data['gpsCollectione']['directionNS'],
            'speed' => $data['gpsCollectione']['speed'],
            'satellites' => $data['gpsCollectione']['satellites']
        ]);
    }
    /**
     * 接收事件-抓拍和比对
     */
    public function addFace(){
//        $param=input('');
//        file_put_contents('C:\WWW\js2\lhyd\public\plugins\statistics\log\face.txt',  serialize($param), FILE_APPEND);
//        $data = $param['params']['events'][0]['data'];
//        Db::name('statistics_face_test')->insert([
//            'URL'=>$data['captureLibResult'][0]['faces'][0]['URL'],
//            'ageGroup'=>$data['captureLibResult'][0]['faces'][0]['age']['ageGroup'],
//            'height'=>$data['captureLibResult'][0]['faces'][0]['faceRect']['height'],
//            'width'=>$data['captureLibResult'][0]['faces'][0]['faceRect']['width'],
//            'x'=>$data['captureLibResult'][0]['faces'][0]['faceRect']['x'],
//            'y'=>$data['captureLibResult'][0]['faces'][0]['faceRect']['y'],
//            'originalData'=>$data
//        ]);
    }

    /**
     * 地图选择目录-返回区域、学校、摄像点列表
     */
    public function getMapSelectDir($uid=null)
    {
        $where=[];
        $where['parentIndexCode']=['eq','root000000'];
        $where['abbr']=['exp','is not null'];//菜单不要第一级
        $list['regions']=Db::name('statistics_regions')->where($where)->order('id asc')->select();
        $list['dir']=Db::name('statistics_dir')->select();
        $list['cameras']=Db::name('statistics_cameras')->field('id,dir_id,cameraName,regionIndexCode')->select();
        return zy_json_echo(true,'获取成功',$list,200);
    }
    /**
     * 查询学校
     */
    public function getSchool()
    {
        $param=input('');
        if(empty($param['indexCode'])){
            return zy_json_echo(false,'参数错误','',-1);
        }
        $list=Db::name('statistics_dir')->where('cameraIndexCode',$param['indexCode'])->select();
        return zy_json_echo(true,'获取成功',$list,200);
    }
    /**
     * 获取有拼音的地区
     */
    public function getAbbrArea()
    {
//        $where['abbr'] = ['exp','!= ""'];
//        $list=Db::name('statistics_regions')
//            ->where($where)
//            ->field('id,name,longitude,latitude,abbr,indexCode')
//            ->order('id asc')
//            ->select()
//            ->each(function($item, $key){
//                //每天陌生人数量
//                $dir=Db::name('statistics_dir')->where('cameraIndexCode',$item['indexCode'])->column('id');
//                $item['stranger_num']=0;//陌生人
//                $item['chef_people']=0;//后厨
//                $item['food_people']=0;//食品安全管理员
//                $item['damp_people']=0;//超市人员
//                $item['health']=0;//健康证
//                foreach ($dir as $k=>$v){
//                    $item['chef_people']+=Db::name('member_info' )->where('school_id',$v)->where('member_type',"后厨人员")->count();
//                    $item['food_people']+=Db::name('member_info' )->where('school_id',$v)->where('member_type',"食品安全管理员")->count();
//                    $item['damp_people']+=Db::name('member_info' )->where('school_id',$v)->where('member_type',"超市人员")->count();
//                    $item['health']+=Db::name('member_info' )->where('school_id',$v)->count();
//                    $num=Db::name('statistics_face_stranger')->whereTime('faceTime', 'd')->where('dir_id',$v)->count();
//                    if($num>0){
//                        $item['stranger_num']+=1;
//                    }
//                }
//                if($item['stranger_num']>0){
//                    $item['is_normal']=0;
//                }else{
//                    $item['is_normal']=1;
//                }
//                return $item;
//            });
        //查询区域
        $regions=Db::name('statistics_regions')->where("parentIndexCode","root000000")->where("type",1)->where('is_show',1)->select()->toArray();
        foreach ($regions as $k=>$v){

            $regions[$k]['stranger_num']=0;//陌生人
            $regions[$k]['chef_people']=0;//后厨
            $regions[$k]['food_people']=0;//食品安全管理员
            $regions[$k]['damp_people']=0;//超市人员
            $regions[$k]['health']=0;//健康证
            $sub=Db::name('statistics_regions')->where("parentIndexCode",$v['indexCode'])->where("name","学校")->find();
                $dir=Db::name('statistics_dir')->where('cameraIndexCode',$sub['indexCode'])->select();
                foreach ($dir as $keys=>$value){
                    $regions[$k]['chef_people']+=Db::name('member_info' )->where('school_id',$value['id'])->where('member_type',"后厨人员")->count();
                    $regions[$k]['food_people']+=Db::name('member_info' )->where('school_id',$value['id'])->where('member_type',"食品安全管理员")->count();
                    $regions[$k]['damp_people']+=Db::name('member_info' )->where('school_id',$value['id'])->where('member_type',"超市人员")->count();
                    $regions[$k]['health']+=Db::name('member_info' )->where('school_id',$value['id'])->count();
                    $num=Db::name('statistics_face_stranger')->whereTime('faceTime', 'd')->where('dir_id',$value['id'])->count();
                    if($num>0){
                        $regions[$k]['stranger_num']+=1;
                    }
                }
            if($regions[$k]['stranger_num']>0){
                $regions[$k]['is_normal']=0;
            }else{
                $regions[$k]['is_normal']=1;
            }
        }
        return zy_json_echo(true,'获取成功',$regions,200);
    }
    public function test()
    {
        $a=base::year_face(24);
        halt($a);
    }
    /**
     * 获取所有学校
     */
    public function getMapSchool($indexCode=null)
    {
        if(empty($indexCode)){
            return zy_json_echo(false,'参数错误','',-1);
        }
        $indexCode=Db::name('statistics_regions')->where("parentIndexCode",$indexCode)->where("name","学校")->value("indexCode");
        $dir=Db::name('statistics_dir')
            ->where('cameraIndexCode',$indexCode)
            ->field('id,dirName,map_id,longitude,latitude')
            ->select()->toArray();
        foreach ($dir as $k=>$v){
            $dir[$k]['chef_people']=Db::name('member_info' )->where('school_id',$v['id'])->where('member_type',"后厨人员")->count();
            $dir[$k]['food_people']=Db::name('member_info' )->where('school_id',$v['id'])->where('member_type',"食品安全管理员")->count();
            $dir[$k]['damp_people']=Db::name('member_info' )->where('school_id',$v['id'])->where('member_type',"超市人员")->count();
            $dir[$k]['health']=Db::name('member_info' )->where('school_id',$v['id'])->count();
            $dir[$k]['stranger_num']=Db::name('statistics_face_stranger')->whereTime('faceTime', 'd')->where('dir_id',$v['id'])->count();
            if($dir[$k]['stranger_num']>0){
                $dir[$k]['is_normal']=0;
            }else{
                $dir[$k]['is_normal']=1;
            }
        }
        return zy_json_echo(true,'获取成功',$dir,200);
    }
    /**
     * 陌生人监控
     */
    public function manageStranger()
    {
        $param=input('');
        $paginate=empty($param['paginate'])?10:$param['paginate'];
        $page=empty($param['page'])?1:$param['page'];

        $where['stranger_status']=['lt',2];
        if(empty($param['uid'])){
            return zy_array(false,'请检查uid','',300,false);
        }
        $where = '1 = 1 ';
        $where .= " and stranger_status<2";
        if(!empty($param['dir_id'])){
            $where .= " and b.dir_id = '".$param['dir_id']."'";
        }
        if(!empty($param['indexCode'])){
            $where .= " and c.indexCode = '".$param['indexCode']."'";
        }
        if(!empty($param['start_time'])){
            $where .= " and a.faceTime >= '".$param['start_time']."'";
        }
        if(!empty($param['end_time'])){
            $where .= " and a.faceTime <= '".$param['end_time']."'";
        }
        $list = Db::name('statistics_face_stranger')->alias('a')
            ->join('cmf_statistics_cameras b', 'a.srcIndex=b.cameraIndexCode', 'left')
            ->join('cmf_statistics_regions c', 'b.regionIndexCode=c.indexCode', 'left')
            ->where($where)
            ->field('a.id,a.faceUrl,a.bkgUrl,a.faceTime,a.stranger_status,b.cameraName,a.ageGroup,a.glass,a.gender,c.name')
            ->order('a.id desc')
            ->paginate($paginate,false,['page'=>$page])
            ->each(function($item, $key){
                $module_info = getModuleConfig('statistics','config','config.json');
                $module_info = json_decode($module_info,true);
                $item['ageGroup']=$module_info['age'][$item['ageGroup']];
                $item['glass']=$module_info['glasses'][$item['glass']];
                $item['gender']=$module_info['sex'][$item['gender']];
                $url=$item['faceUrl'];
                unset($item['faceUrl']);
                $item['faceUrl'][0]=$url;
                $url=$item['bkgUrl'];
                unset($item['bkgUrl']);
                $item['bkgUrl'][0]=$url;
                return $item;
            });
        return zy_json_echo(true,'获取成功',$list,200);
    }

    /**
     * 陌生人监控-上方学校被拍陌生人统计
     */
    public function schoolStrangerStat()
    {
        $param=input('');
        if(empty($param['num'])){
            $param['num']=10;
        }
        $arr=[];
        $str=Db::name('statistics_face_stranger')->where('dir_id','>',0)->whereTime('faceTime', 'm')->group('dir_id')->limit(0,$param['num'])->select();
        foreach ($str as $k=>$v) {
            $arr[$k]['school_name'] =Db::name('statistics_dir')->where('id',$v['dir_id'])->value('dirName');
            $arr[$k]['day'] = Db::name('statistics_face_stranger')->where('dir_id', $v['dir_id'])->whereTime('faceTime', 'd')->count();
            $arr[$k]['week'] = Db::name('statistics_face_stranger')->where('dir_id', $v['dir_id'])->whereTime('faceTime', 'w')->count();
            $arr[$k]['month'] = Db::name('statistics_face_stranger')->where('dir_id', $v['dir_id'])->whereTime('faceTime', 'm')->count();
        }
        $last_names = array_column($arr,'month');
        array_multisort($last_names,SORT_DESC,$arr);
        //如果没有数据，放10个
        if(empty($arr)){
            $list=Db::name('statistics_dir')->limit(0,$param['num'])->select();
            foreach ($list as $k=>$v){
                $arr[$k]['school_name']=$v['dirName'];
                $arr[$k]['day']=0;
                $arr[$k]['week']=0;
                $arr[$k]['month']=0;
            }
        }
        //如果数据不够10个

        $count=count($arr);
        if($count<$param['num']){
            $i=$param['num']-$count;
            while($i>0) {
                $dirname=Db::name('statistics_dir')->limit(1)->order('rand()')->value('dirName');
                foreach ($arr as $v){
                    if($dirname!==$v['school_name']){
                        $k=count($arr);
                        $arr[$k]["school_name"]=$dirname;
                        $arr[$k]['day']=0;
                        $arr[$k]['week']=0;
                        $arr[$k]['month']=0;
                        $i--;
                        break;
                    }
                }
            }
        }
        return zy_json_echo(true,'获取成功',$arr,200);
    }
    /**
     *陌生人监控-忽略陌生人
     */
    public function overStranger($id=null)
    {
        if(empty($id)){
            return zy_json_echo(false,'参数错误','',-1);
        }
        $in=Db::name('statistics_face_stranger')->where('id',$id)->update(['stranger_status'=>2]);
        if($in!==false){
            return zy_json_echo(true,'操作成功',[],200);
        }else{
            return zy_json_echo(false,'操作失败','',-1);
        }
    }
    /**
     * 陌生人监控-提醒学校网格员
     */
    public function reShoolStranger($id=null,$uid=null)
    {
        if(empty($id)||empty($uid)){
            return zy_json_echo(false,'参数错误','',-1);
        }
        $uid=$this->verify_power($uid,true);
        $find = Db::name('statistics_face_stranger')->alias('a')
            ->join('cmf_statistics_cameras b', 'a.srcIndex=b.encodeDevIndexCode', 'left')
            ->join('cmf_statistics_regions c', 'b.regionIndexCode=c.indexCode', 'left')
            ->where('a.id',$id)
            ->field('a.id,a.faceUrl,a.faceTime,a.stranger_status,b.cameraName,a.dir_id,c.name')
            ->find();
        $url=$find['faceUrl'];
        unset($find['faceUrl']);
        $find['faceUrl'][0]=$url;
        $user=Db::name('user')->where('user_type','neq',1)->where('find_in_set('.$find['dir_id'].',school)')->column('id');
        $msg = [
            'type'=>'admin_stranger',
            'describe'=>'管理员提醒网格员学校陌生人',
            'content'=>$find
        ];
        Gateway::sendToUid($user, json_encode($msg));
        return zy_json_echo(true,'操作成功',[],200);
    }
    /**
     * 重点人员监控-添加重点人员
     */
    public function addEmphasisInfo()
    {
        $param=input('');
        $param['uid']=$this->verify_power($param['uid'],true);
//        if(empty($user_name)||empty($id_card)||empty($face_img)){
//            return zy_json_echo(false,'请填写完整信息','',-1);
//        }
        $adminV=new AdminEmphasisValidate();
        if(!$adminV->check($param)){
//            $this->error($adminV->getError());
            return zy_json_echo(false,$adminV->getError(),'',-1);
        }
        $is_find=Db::name('statistics_emphasis')->where('id_card',$param['id_card'])->find();
        if(!empty($is_find)){
            return zy_json_echo(false,'该身份证已存在','',-1);
        }
//        $img_size=filesize($face_img);
        $postData = [
            "faceGroupIndexCode"=> '5fd42a66-8e55-46fc-9f36-1ffb4f80558f',
            "faceInfo"=>[
                "name"=>$param['user_name'],
                "certificateType"=>"111",
                "certificateNum"=>$param['id_card'],
            ],
            "facePic"=>["faceUrl"=>$param['face_img']]
        ];
        $hk=new Haikang();
        $arr = json_decode($hk->doCurl($postData, $hk->face_list_url),true);
        if(empty($arr['data'])){
            return zy_json_echo(false,'添加失败，图片大小为10kb至200kb',$arr,-1);
        }
        $in=Db::name('statistics_emphasis')
            ->insert([
                'user_name'=>$param['user_name'],
                'id_card'=>$param['id_card'],
                'face_img'=>$param['face_img'],
                'indexCode'=>$arr['data']['indexCode'],
                'faceGroupIndexCode'=>'5fd42a66-8e55-46fc-9f36-1ffb4f80558f',
                'create_time'=>time()
            ]);
        if($in>0){
            return zy_json_echo(true,'操作成功',[],200);
        }else{
            return zy_json_echo(false,'添加失败，请重试','',-1);
        }
    }
    /**
     * 重点人员监控-重点人员列表
     */
    public function manageEmphasis()
    {
        $param=input('');
        $param['uid']=$this->verify_power($param['uid'],true);
        $where=[];
        $paginate=empty($param['paginate'])?10:$param['paginate'];
        $page=empty($param['page'])?1:$param['page'];
        $where = '1 = 1 ';
        if (!empty($param['user_name'])) {
            $where .= " and user_name like '%".$param['user_name']."%'";
        }
        if (!empty($param['id_card'])) {
            $where .= " and id_card like '%".$param['id_card']."%'";
        }
        if (!empty($param['start_time'])) {
            $where .= " and create_time >= '" . strtotime($param['start_time']) . "'";
        }
        if (!empty($param['end_time'])) {
            $where .= " and create_time <= '" . strtotime($param['end_time']) . "'";
        }
        $list=Db::name('statistics_emphasis')
            ->order('create_time desc')
            ->where($where)
            ->paginate($paginate,false,['page'=>$page])
            ->each(function($item, $key){
                $item['create_time']=date("Y-m-d h:i:s",$item['create_time']);
                $url=$item['face_img'];
                unset($item['face_img']);
                $item['face_img'][0]=$url;
                return $item;
            });
        return zy_json_echo(true,'获取成功',$list,200);
    }
    /**
     * 重点人员监控-被抓拍的重点人员
     */
    public function comparisonEmphasis()
    {
        $param=input('');
        $paginate=empty($param['paginate'])?10:$param['paginate'];
        $page=empty($param['page'])?1:$param['page'];
        $where = '1 = 1 ';
        if (!empty($param['indexCode'])) {
            $where .= " and e.indexCode = '" . $param['indexCode'] . "'";
        }
        if (!empty($param['dir_id'])) {
            $where .= " and d.id= '" . $param['dir_id'] . "'";
        }
        if (!empty($param['start_time'])) {
            $where .= " and a.create_time >= '" . strtotime($param['start_time']) . "'";
        }
        if (!empty($param['end_time'])) {
            $where .= " and a.create_time <= '" . strtotime($param['end_time']) . "'";
        }
        $list=Db::name('statistics_emphasis')->alias('a')
            ->join('statistics_face_emphasis b','a.indexCode=b.faceInfoCode','left')
            ->join('statistics_cameras c','b.indexCode=c.cameraIndexCode','left')
            ->join('statistics_dir d','c.dir_id=d.id','left')
            ->join('statistics_regions e','d.cameraIndexCode=e.indexCode','left')
            ->where($where)
            ->where('b.faceUrl','not null')
            ->field('b.id,a.user_name,a.id_card,a.face_img,b.faceUrl,b.faceTime,d.dirName,e.name')
            ->order('id desc')
            ->paginate($paginate,false,['page'=>$page])
            ->each(function($item, $key){
//                $item['faceTime']=date("Y-m-d h:i:s",$item['faceTime']);
                $url=$item['faceUrl'];
                $img=$item['face_img'];
                unset($item['faceUrl']);
                unset($item['face_img']);
                $item['faceUrl'][0]=$url;
                $item['face_img'][0]=$img;
                return $item;
            });

        return zy_json_echo(true,'获取成功',$list,200);
    }
    /**
     * 重点人员监控-修改重点人员
     */
    public function updateEmphasis()
    {
        $param = input('');
        if (empty($param['id'])||empty($param['user_name'])||empty($param['id_card'])||empty($param['face_img'])) {
            return zy_json_echo(false,'请填写完整信息','',-1);
        }
        $adminV=new AdminEmphasisValidate();
        if(!$adminV->check($param)){
//            $this->error($adminV->getError());
            return zy_json_echo(false,$adminV->getError(),'',-1);
        }
        $find=Db::name('statistics_emphasis')->where('id',$param['id'])->find();
        $postData = [
            "indexCode"=> $find['indexCode'],
            "faceInfo"=>[
                "name"=>$param['user_name'],
                "certificateType"=>"111",
                "certificateNum"=>$param['id_card'],
            ],
            "facePic"=>["faceUrl"=>$param['face_img']]
        ];
        $hk=new Haikang();
        $arr = json_decode($hk->doCurl($postData, $hk->upload_face_list_url),true);
        if($arr['code']!=0){
            return zy_json_echo(false,'请检查图片',$arr,-1);
        }
        $is_up=Db::name('statistics_emphasis')
            ->where('id',$param['id'])
            ->update([
                'user_name'=>$param['user_name'],
                'id_card'=>$param['id_card'],
                'face_img'=>$param['face_img'],
                'update_time'=>time()
            ]);
        if($is_up!==false){
            return zy_json_echo(true,'修改成功','',200);
        }else{
            return zy_json_echo(false,'修改失败','',-1);
        }
    }
    /**
     * 重点人员监控-删除
     */
    public function deleteEmphasis()
    {
        $param = input('');
        if(empty($param['id'])){
            return zy_json_echo(false,'参数错误','',-1);
        }
        $find=Db::name('statistics_emphasis')->where('id',$param['id'])->find();
        $postData = [
            "faceGroupIndexCode"=>$find['faceGroupIndexCode'],
            "indexCodes"=>[$find['indexCode']]
        ];
        $hk=new Haikang();
        $arr = $hk->doCurl($postData, $hk->delete_face);
        $arr=json_decode($arr,true);
        if($arr['code']!=0){
            return zy_json_echo(false,'删除失败，请重试',$arr,-1);
        }
        $result=Db::name('statistics_emphasis')->where('id',$param['id'])->delete();
        if($result===false){
            return zy_json_echo(false,'删除失败，请重试','',-1);
        }else{
            return zy_json_echo(true,'删除成功','',200);
        }
    }
    /**
     * 重点人员监控-批量删除
     */
    public function deleteEmphasisArr()
    {
        $param=input('');
        if(count($param['arr'])<1){
            return zy_json_echo(false,'请选择内容','',-1);
        }
        foreach ($param['arr'] as $item) {
            $find=Db::name('statistics_emphasis')->where('id',$item)->find();
            $postData = [
                "faceGroupIndexCode"=>$find['faceGroupIndexCode'],
                "indexCodes"=>[$find['indexCode']]
            ];
            $hk=new Haikang();
            $arr = $hk->doCurl($postData, $hk->delete_face);
            Db::name('statistics_emphasis')->where('id',$item)->delete();
        }
        return zy_json_echo(true,'删除成功','',200);
    }
    /**
     * 数据对比
     */
    public function dataContrast()
    {
        $param=input('');
        if(empty($param['indexCode'])){
            $param['indexCode']='1bc31038-779f-44fd-bc0d-995a9dab0636';//默认杜桥
        }
        $dir=Db::name('statistics_dir')->where('cameraIndexCode', $param['indexCode'])->select();
        $result=[];
        foreach ($dir as $k=>$v){
            $result['dir'][$k]['school_name']=$v['dirName'];
            $result['top_chart'][$k][0]=$v['dirName'];
            //当月健康证
            $month_health=base::month_health($v['id']);
            $result['dir'][$k]['nor_ratio']=$month_health['nor_ratio'];
            if($month_health['nor_ratio']>0){
                $result['top_chart'][$k][1]=100-$month_health['nor_ratio'];
            }else{
                $result['top_chart'][$k][1]=0;
            }

            //上月健康证未过期比例
            $last_month_health=base::last_month_health($v['id']);
            $result['dir'][$k]['last_ratio']=$last_month_health['last_ratio'];
            //当月人脸抓拍
            $month_face=base::month_face($v['id']);
            $result['dir'][$k]['face_num']= $month_face['face_num'];
            $result['dir'][$k]['dis_pro']= $month_face['dis_pro'];
            $result['top_chart'][$k][2]=$month_face['dis_pro'];
            $result['top_chart'][$k][3]=$month_face['face_num'];
            $result['top_chart'][$k][4]=$v['id'];
            $result['top_chart'][$k][5]=$v['cameraIndexCode'];
            //上月人脸抓拍
            $last_month_face=base::last_month_face($v['id']);
            $result['dir'][$k]['last_face_num']= $last_month_face['last_face_num'];
            $result['dir'][$k]['last_dis_pro']= $last_month_face['last_dis_pro'];
        }
        return zy_json_echo(true,'获取成功',$result,200);
    }
    /**
     * 数据对比-学校上月当月信息表格
     */
    public function getSchoolHealth($dir_id=null,$code=1,$is_car=false)
    {
        if(empty($dir_id)||empty($code)){
            return zy_json_echo(false,'参数错误','',-1);
        }
        $find=Db::name('statistics_dir')->where('id', $dir_id)->find();
        //当月健康证未过期比例
        $arr['dirName']=$find['dirName'];

        $month_health=base::month_health($dir_id);
        $last_month_health=base::last_month_health($dir_id);
        $month_face=base::month_face($dir_id);
        $last_month_face=base::last_month_face($dir_id);
        if($code==1){
        $arr['data1'][0]['name']="健康证总数";
        $arr['data1'][0]['value']=$month_health['all_num'];
        $arr['data1'][0]['selected']=true;
        $arr['data1'][1]['name']="重点人员告警总数";
        $arr['data1'][1]['value']=$month_face['emphasis_face_num'];
        $arr['data1'][2]['name']="陌生人员抓拍总数";
        $arr['data1'][2]['value']=$month_face['stranger_face_num'];
        $arr['data2'][0]['name']="健康证未过期数";
        $arr['data2'][0]['value']=$month_health['nor_num'];
        $arr['data2'][1]['name']="健康证过期数";
        $arr['data2'][1]['value']=$month_health['all_num']-$month_health['nor_num'];
        $arr['data2'][2]['name']="重点人员告警总数";
        $arr['data2'][2]['value']=$month_face['emphasis_face_num'];
        $arr['data2'][3]['name']="陌生人脸及时处理数";
        $arr['data2'][3]['value']=$month_face['dis_stranger'];
        $arr['data2'][4]['name']="陌生人脸未及时处理数";
        $arr['data2'][4]['value']=$month_face['stranger_face_num']-$month_face['dis_stranger'];
        }else{
            $arr['data1'][0]['name']="健康证总数";
            $arr['data1'][0]['value']=$last_month_health['last_all_num'];
            $arr['data1'][0]['selected']=true;
            $arr['data1'][1]['name']="重点人员告警总数";
            $arr['data1'][1]['value']=$last_month_face['last_emphasis_face_num'];
            $arr['data1'][2]['name']="陌生人员抓拍总数";
            $arr['data1'][2]['value']=$last_month_face['last_stranger_face_num'];
            $arr['data2'][0]['name']="健康证未过期数";
            $arr['data2'][0]['value']=$last_month_health['last_nor_num'];
            $arr['data2'][1]['name']="健康证过期数";
            $arr['data2'][1]['value']=$last_month_health['last_all_num']-$last_month_health['last_nor_num'];
            $arr['data2'][2]['name']="重点人员告警总数";
            $arr['data2'][2]['value']=$last_month_face['last_emphasis_face_num'];
            $arr['data2'][3]['name']="陌生人脸及时处理数";
            $arr['data2'][3]['value']=$last_month_face['last_dis_stranger'];
            $arr['data2'][4]['name']="陌生人脸未及时处理数";
            $arr['data2'][4]['value']=$last_month_face['last_stranger_face_num']-$last_month_face['last_dis_stranger'];
        }
        if($is_car){
            return $arr;
        }
        return zy_json_echo(true,'获取成功',$arr,200);
    }
    /**
     * 数据对比-区域上月当月信息表格
     */
    public function getRegionsHealth()
    {
        $param=input('');
        if(empty($param['indexCode'])||empty($param['code'])){
            return zy_json_echo(false,'参数错误','',-1);
        }
        $cameras=Db::name('statistics_regions')->where('indexCode',$param['indexCode'])->find();
        $dir=Db::name('statistics_dir')->where('cameraIndexCode', $param['indexCode'])->select();
        $arr['dirName']=$cameras['name'];
        $arr['data1'][0]['name']="健康证总数";
        $arr['data1'][0]['value']=0;
        $arr['data1'][0]['selected']=true;
        $arr['data1'][1]['name']="重点人员告警总数";
        $arr['data1'][1]['value']=0;
        $arr['data1'][2]['name']="陌生人员抓拍总数";
        $arr['data1'][2]['value']=0;
        $arr['data2'][0]['name']="健康证未过期数";
        $arr['data2'][0]['value']=0;
        $arr['data2'][1]['name']="健康证过期数";
        $arr['data2'][1]['value']=0;
        $arr['data2'][2]['name']="重点人员告警总数";
        $arr['data2'][2]['value']=0;
        $arr['data2'][3]['name']="陌生人脸及时处理数";
        $arr['data2'][3]['value']=0;
        $arr['data2'][4]['name']="陌生人脸未及时处理数";
        $arr['data2'][4]['value']=0;
        foreach ($dir as $k=>$v){
            $data=$this->getSchoolHealth($v['id'],$param['code'],true);
            $arr['data1'][0]['value']+=$data['data1'][0]['value'];
            $arr['data1'][0]['selected']=true;
            $arr['data1'][1]['value']+=$data['data1'][1]['value'];
            $arr['data1'][2]['value']+=$data['data1'][2]['value'];
            $arr['data2'][0]['value']+=$data['data2'][0]['value'];
            $arr['data2'][1]['value']+=$data['data2'][1]['value'];
            $arr['data2'][2]['value']+=$data['data2'][2]['value'];
            $arr['data2'][3]['value']+=$data['data2'][3]['value'];
            $arr['data2'][4]['value']+=$data['data2'][4]['value'];
        }

        return zy_json_echo(true,'获取成功',$arr,200);
    }
    /**
     * 数据对比-区域
     */
    public function dataRegionsContrast()
    {
        $regions=Db::name('statistics_regions')->where('abbr','not null')->where('parentIndexCode','root000000')->select();
        $dir=Db::name('statistics_dir')->select();
        $arr=[];
        foreach ($regions as $k=>$v){
            $arr[$k][0]=$v['name'];
            $arr[$k][1]=0;
            $arr[$k][2]=0;
            $arr[$k][3]=0;
            $arr[$k][4]=$v['indexCode'];
            $all=0;
            $nor_num=0;//未过期
            foreach ($dir as $key=>$val){
                if($v['indexCode']==$val['cameraIndexCode']){
                    //当月健康证
                    $month_health=base::month_health($val['id']);
                    $all+=$month_health['all_num'];
                    $nor_num+=$month_health['nor_num'];
//                    $arr[$k][1]+=$month_health['nor_ratio'];
                    //当月人脸抓拍
                    $month_face=base::month_face($val['id']);
                    $arr[$k][2]+= $month_face['dis_pro'];
                    $arr[$k][3]+= $month_face['face_num'];
                }
            }
            $alr_num=$all-$nor_num;//已过期
            if($nor_num==0){
                $arr[$k][1]=0;
            }else{
                $arr[$k][1]=round($alr_num/$all*100);
            }
        }
        return zy_json_echo(true,'获取成功',$arr,200);
    }
    /**
     *学校列表展示
     */
    public function schoolList()
    {
        $param=input('');
        $paginate=empty($param['paginate'])?10:$param['paginate'];
        $page=empty($param['page'])?1:$param['page'];
        $where = '1 = 1 ';
        if(!empty($param['indexCode'])){
            $where .= " and indexCode = '".$param['indexCode']."'";
        }
        if(!empty($param['dir_id'])){
            $where .= " and b.id = '".$param['dir_id']."'";
        }
        if(!empty($param['start_time'])){
            $where .= " and health_endtime >= '".$param['start_time']."'";
        }
        if(!empty($param['end_time'])){
            $where .= " and health_endtime <= '".$param['end_time']."'";
        }
        $list=Db::name('member_info')->alias('a')
            ->join('statistics_dir b','a.school_id=b.id','left')
            ->join('statistics_regions c','b.cameraIndexCode=c.indexCode','left')
            ->where($where)
            ->field('a.*,b.dirName,c.name')
            ->order('MIID desc')
            ->paginate($paginate,false,['page'=>$page])
            ->each(function($item, $key){
//                $item['addtime']=date('Y-m-d h:i:s',$item['addtime']);
                $face=$item['face_thumb'];
                $card=$item['health_card'];
                unset($item['face_thumb']);
                unset($item['health_card']);
                $item['face_thumb'][0]=$this->request->domain().$this->request->root().'uploadFile/'.$face;
                $item['health_card'][0]=$this->request->domain().$this->request->root().'uploadFile/'.$card;
                $zero1=strtotime (date("y-m-d")); //当前时间
                $zero2=strtotime ($item['health_endtime']);  //健康证时间
                $day = ($zero2-$zero1)>604800?false:true;
                if($zero1>$zero2){
                    //过期时间
                    $item['is_over'] = -1;
                }elseif($day){
                    //快到期时间
                    $item['is_over'] = 1;
                }else{
                    //正常时间
                    $item['is_over'] = 2;
                }
                return $item;
            });
        return zy_json_echo(true,'获取成功',$list,200);
    }
    /**
     * 学校id查网格员
     */
    public function getGridUser()
    {
        $dir_id=input('dir_id');
        if(empty($dir_id)){
            return zy_json_echo(false,'参数错误','',-1);
        }
        $user=Db::name('user')->where('user_type',3)->where('find_in_set('.$dir_id.',school)')->field('id as user_id,user_login,mobile,region,school')->select();
        return zy_json_echo(true,'获取成功',$user,200);
    }
    /**
     * 违规数据
     */
    public function illegalSchool()
    {
        $param=input('');
        $paginate=empty($param['paginate'])?10:$param['paginate'];
        $page=empty($param['page'])?1:$param['page'];
        $where = '1 = 1 ';
        if(!empty($param['indexCode'])){
            $where .= " and d.indexCode >= '".$param['indexCode']."'";
        }
        if(!empty($param['dir_id'])){
            $where .= " and b.id = '".$param['dir_id']."'";
        }
        if(!empty($param['user_id'])){
            $where .= " and c.id = '".$param['user_id']."'";
        }
        if(!empty($param['start_time'])){
            $where .= " and a.time >= '". strtotime($param['start_time'])."'";
        }
        if(!empty($param['end_time'])){
            $where .= " and a.time <= '". strtotime($param['end_time'])."'";
        }
        $list=Db::name('report_school')->alias('a')
            ->join('statistics_dir b','a.school_id=b.id','left')
            ->join('statistics_regions d','b.cameraIndexCode=d.indexCode','left')
            ->join('user c','a.userId=c.id','left')
            ->field('a.*,b.dirName,d.name')
            ->where($where)
            ->order('a.id desc')
            ->paginate($paginate,false,['page'=>$page])
            ->each(function($item, $key) {
                if(!empty($item['enclosure'])){
                    $item['enclosure']= explode(",",$item['enclosure']);
                    foreach ($item['enclosure'] as $k=>$v){
                        $item['enclosure'][$k]=$this->request->domain().$this->request->root().'uploadFile/'.$v;
                    }
                }else{
                    $item['enclosure']=[];
                }

                if(!empty($item['re_path'])){
                    $item['re_path']= explode(",",$item['re_path']);
                    foreach ($item['re_path'] as $k=>$v){
                        $item['re_path'][$k]=$this->request->domain().$this->request->root().'uploadFile/'.$v;
                    }
                }

                $item['time']=date('Y-m-d h:i:s',$item['time']);
                return $item;
            });
        return zy_json_echo(true,'获取成功',$list,200);
    }
    /**
     * 首页数据大屏
     */
    public function indexStat($uid=null)
    {
        $uid=$this->verify_power($uid,true);
        $regions=Db::name('statistics_regions')->where("parentIndexCode","root000000")->where("type",1)->where('is_show',1)->select();
        //数据概况
        $list['general']=Db::name('statistics_general')->where('show_status',1)->field('title,value')->order('sort asc,id asc')->select();
        //健康证状况
        $dir=Db::name('statistics_dir')->select();
        $arr=[];
        foreach ($regions as $k=>$v){
            $arr[$k]['name']=$v['name'];
            $arr[$k]['nor']=0;
            $arr[$k]['ano']=0;
            $indexCode=Db::name('statistics_regions')->where("parentIndexCode",$v['indexCode'])->where("name","学校")->value("indexCode");

                foreach ($dir as $key=>$val){
                    if($indexCode==$val['cameraIndexCode']){
                        $arr[$k]['nor']+=Db::name('member_info')->where('school_id',$val['id'])->where('health_endtime','> time',date('Y-m-d',time()))->count();
                        $arr[$k]['ano']+=Db::name('member_info')->where('school_id',$val['id'])->where('health_endtime','<= time',date('Y-m-d',time()))->count();
                    }
                }


            $arr[$k]['ratio']=$arr[$k]['ano']<1?0:round($arr[$k]['ano']/($arr[$k]['ano']+$arr[$k]['nor'])*100);
        }
        $last_names = array_column($arr,'ratio');
        array_multisort($last_names,SORT_ASC,$arr);
//        $arr=array_slice($arr, 0, 5);
        foreach ($arr as $k=>$v){
            $list['area_health'][$k][0]=rand(0,100);
            $list['area_health'][$k][1]=$v['ratio'];
            $list['area_health'][$k][2]=$v['name'];
        }
        //区域摄像头
        $where['parentIndexCode']=['eq','root000000'];
        $where['abbr'] = ['exp','!= ""'];
        $regions=Db::name('statistics_regions')->where($where)->order('id asc')->select();
        $arr=[];
        foreach ($regions as $k=>$v){
            $arr[$k]['name']=$v['name'];
            $arr[$k]['indexCode']=$v['indexCode'];
            $arr[$k]['camera_num']=Db::name('statistics_cameras')->where('regionIndexCode',$v['indexCode'])->count();
        }
        $list['regions_map']=$arr;
        //各学校视频状况
        foreach ($list['regions_map'] as $k=>$v){
            $list['regions_chart'][$k][0]=$v['name'];
            $list['regions_chart'][$k][1]=$v['camera_num'];
            $list['regions_chart'][$k][2]=0;
        }
        return zy_json_echo(true,'获取成功',$list,200);
    }
    /**
     * 首页数据大屏-学校重点人员和陌生人员
     */
//    public function indexStatSchool()
//    {
//        $param=input('');
//        $uid=$this->verify_power($param['uid'],true);
//        if(empty($param['indexCode'])){
//            $param['indexCode']='1bc31038-779f-44fd-bc0d-995a9dab0636';//默认杜桥
//        }
//        $dir=Db::name('statistics_dir')->where('cameraIndexCode', $param['indexCode'])->select();
//        $arr=[];
//        foreach ($dir as $k=>$v){
//            $arr[$k][0]=$v['dirName'];
//            $stranger=Db::name('statistics_dir_stat')->where('month_section',date('Y-m', time()))->where('dir_id',$v['id'])->value('stranger_face_num');
//            $emphasis=Db::name('statistics_dir_stat')->where('month_section',date('Y-m', time()))->where('dir_id',$v['id'])->value('emphasis_face_num');
//            $arr[$k][1]=empty($stranger)?0:$stranger;
//            $arr[$k][2]=empty($emphasis)?0:$emphasis;
//        }
//        return zy_json_echo(true,'获取成功',$arr,200);
//    }
    /**
     * 首页数据大屏-未点击区域的数据
     */
    public function indexStatRegion()
    {
        $param=input('');
        $uid=$this->verify_power($param['uid'],true);
        //当月重点人员陌生人员
        $regions=Db::name('statistics_regions')->where("parentIndexCode","root000000")->where("type",1)->where('is_show',1)->select();
        $arr=[];
        foreach ($regions as $k=>$v){
            $arr['stat_people'][$k][0]=$v['name'];
            $arr['stat_people'][$k][1]=0;
            $indexCode=Db::name('statistics_regions')->where("parentIndexCode",$v['indexCode'])->where("name","学校")->value("indexCode");
            $arr['stat_people'][$k][1]+=Db::name('statistics_dir_stat')->where('cameraIndexCode',$indexCode)->where('month_section',date('Y-m', time()))->sum('stranger_face_num');
        }
        //健康证图表
//        $arr['area_chart'][0]['name']="总数";
        $area_chart=Db::name('member_info')->count();
        $arr['area_chart'][0]['name']="正常健康证数";
        $arr['area_chart'][0]['value']=Db::name('member_info')->where('health_endtime','> time',date('Y-m-d',time()))->count();
        $arr['area_chart'][1]['name']="异常健康证数";
        $arr['area_chart'][1]['value']= $area_chart-$arr['area_chart'][0]['value'];
        //学校概况
        $school_general=Db::name('statistics_dir')->where('cameraIndexCode','not null')->count();
        $arr['school_general'][0]['name']="有异常的学校";
        $arr['school_general'][0]['value']=Db::name('statistics_face_stranger')->where('dir_id','gt',0)->whereTime('faceTime', 'd')->group('dir_id')->count();
        $arr['school_general'][1]['name']="没有异常的学校";
        $arr['school_general'][1]['value']= $school_general-$arr['school_general'][0]['value'];
        return zy_json_echo(true,'获取成功',$arr,200);
    }
    /**
     * 首页数据大屏-点击区域的数据
     */
    public function indexStatSchool()
    {
        $param=input('');
        $uid=$this->verify_power($param['uid'],true);
        if(empty($param['indexCode'])||empty($param['uid'])){
            return zy_json_echo(true,'参数错误','',-1);
        }
        //当月重点人员陌生人员
        $indexCode=Db::name('statistics_regions')->where("parentIndexCode",$param['indexCode'])->where("name","学校")->value("indexCode");
        $dir=Db::name('statistics_dir')->where('cameraIndexCode', $indexCode)->select();
        $arr=[];
        $area_chart=0;
        $arr['area_chart'][0]['name']="正常健康证数";
        $arr['area_chart'][0]['value']=0;
        $arr['area_chart'][1]['name']="异常健康证数";
//        $arr['school_general'][0]['name']="学校个数";
        $arr['school_general'][0]['name']="有异常的学校";
        $arr['school_general'][0]['value']=0;
        $arr['school_general'][1]['name']="没有异常的学校";
        $arr['school_general'][1]['value']=0;
        foreach ($dir as $k=>$v){
            $arr['stat_people'][$k][0]=$v['dirName'];
            $emphasis=Db::name('statistics_dir_stat')->where('month_section',date('Y-m', time()))->where('dir_id',$v['id'])->value('emphasis_face_num');
            $stranger=Db::name('statistics_dir_stat')->where('month_section',date('Y-m', time()))->where('dir_id',$v['id'])->value('stranger_face_num');
//            $arr['stat_people'][$k][1]=empty($emphasis)?0:$emphasis;
            $arr['stat_people'][$k][1]=empty($stranger)?0:$stranger;
            //健康证图表
            $area_chart+=Db::name('member_info')->where('school_id',$v['id'])->count();
            $arr['area_chart'][0]['value']+=Db::name('member_info')->where('school_id',$v['id'])->where('health_endtime','> time',date('Y-m-d',time()))->count();
            $anomaly_school=Db::name('statistics_face_stranger')->where('dir_id',$v['id'])->whereTime('faceTime', 'd')->count();
            if(!empty($anomaly_school)){
                $arr['school_general'][0]['value']+=1;
            }
        }
        $arr['area_chart'][1]['value']= $area_chart-$arr['area_chart'][0]['value'];
        //学校概况
        $school_general=count($dir);
        $arr['school_general'][1]['value']=  $school_general- $arr['school_general'][0]['value'];
        return zy_json_echo(true,'获取成功',$arr,200);
    }
    /**
     *  首页数据大屏-学校违规信息
     */
    public function schoolViolation($uid=null)
    {
        $uid=$this->verify_power($uid,true);
//        $count=Db::query('SELECT school_id,COUNT(school_id) AS num FROM cmf_report_school GROUP BY school_id');
//        $arr=[];
//        foreach ($count as $k=>$v){
//            $arr[$k]=Db::name('report_school')
//                ->where('school_id',$v['school_id'])
//                ->field('id,school_id,name,violation')
//                ->find();
//            $arr[$k]['code']=1;
//            if($v['num']>15){
//                $arr[$k]['code']=3;
//            }else if($v['num']>5){
//                $arr[$k]['code']=2;
//            }
//            $arr[$k]['num']=$v['num'];
//        }
//        return zy_json_echo(true,'获取成功',$arr,200);
        $list=Db::query('select id,name,violation,school_id,COUNT(school_id) AS num from cmf_report_school where to_days(timeStr) = to_days(now()) group by school_id');
        foreach ($list as $k=>$v){
            $list[$k]['code']=1;
            if($v['num']>15){
                $list[$k]['code']=3;
            }else if($v['num']>5){
                $list[$k]['code']=2;
            }
        }
        return zy_json_echo(true,'获取成功',$list,200);
    }
    /**
     * 首页区域下学校摄像头数
     */
    public function getSchoolCameraNum()
    {
        $param=input('');
        if(empty($param['indexCode'])){
            return zy_json_echo(false,'参数错误','',-1);
        }
        $dir=Db::name('statistics_dir')->where('cameraIndexCode',$param['indexCode'])->select();
        $arr=[];
        foreach ($dir as $k=>$v){
            $arr[$k]['school_name']=$v['dirName'];
            $arr[$k]['school_camera_num']=Db::name('statistics_cameras')->where('dir_id',$v['id'])->count();
        }
        return zy_json_echo(true,'获取成功',$arr,200);
    }
    /**
     * 绑定uid并分组
     */
    public function bindUser($uid=null,$client_id=null)
    {
        if(empty($uid)||empty($client_id)){
            return zy_json_echo(true,'参数错误',[],200);
        }
        $data = zy_userid_jwt($uid,'de');
        if(isset($data['data'])){
            $uid=$data['data'];
        }else{
            return zy_json_echo(false,'错误信息',$data,-1);
        }
        $user=Db::name('user')->where('id',$uid)->find();
        Gateway::bindUid($client_id, $uid);//绑定uid
        Gateway::joinGroup($client_id, $user['user_type']);//绑定分组
        return zy_json_echo(true,'绑定成功','',200);
    }
    //学校列表展示
    public function getSchoolList()
    {
        $param=input('');
        $paginate=empty($param['paginate'])?10:$param['paginate'];
        $page=empty($param['page'])?1:$param['page'];
        $where = [];
        if(!empty($param['dirName'])){
            $where['a.dirName']=['like','%'.trim($param['dirName']).'%'];
        }
        if(!empty($param['company'])){
            $where['a.company']=['like','%'.trim($param['company']).'%'];
        }
        if(!empty($param['personCharge'])){
            $where['a.personCharge']=['like','%'.trim($param['personCharge']).'%'];
        }
        if(!empty($param['street'])){
            $where['a.street']=['like','%'.trim($param['street']).'%'];
        }
        $list=Db::name('statistics_dir')->alias('a')
            ->join('cmf_statistics_regions b','a.cameraIndexCode=b.indexCode','left')
            ->where('a.dir_abbr','not null')
            ->where($where)
            ->field('a.*,b.name')
            ->paginate($paginate,false,['page'=>$page]);
        return zy_json_echo(true,'获取成功',$list,200);
    }

    /**
     * 学校今天的重点陌生人报警
     */
    public function indexAlarm($uid=null)
    {
        $uid=$this->verify_power($uid,true);
        $arr=[];
        $stranger=Db::name('statistics_face_stranger')->alias('a')
            ->join('cmf_statistics_dir b','a.dir_id=b.id','left')
            ->whereTime('faceTime', 'd')
            ->select();
        foreach ($stranger as $k=>$v){
            $value['dirName']=empty($v['dirName'])?'大田小学':$v['dirName'];
            $value['type']='发现陌生人';
            $value['faceTime']=$v['faceTime'];
            $arr[]=$value;
        }
        $emphasis=Db::name('statistics_face_emphasis')->alias('a')
            ->join('cmf_statistics_dir b','a.dir_id=b.id','left')
            ->whereTime('faceTime', 'd')
            ->select();
        foreach ($emphasis as $k=>$v){
            $value['dirName']=empty($v['dirName'])?'大田小学':$v['dirName'];
            $value['type']='发现重点人员';
            $value['faceTime']=$v['faceTime'];
            $arr[]=$value;
        }

        $ctime_str = array();
        foreach($arr as $key=>$v){
            $arr[$key]['faceTime'] = strtotime($v['faceTime']);
            $ctime_str[] = $arr[$key]['faceTime'];
        }
        array_multisort($ctime_str,SORT_DESC,$arr);
        foreach ($arr as $k=>$v){
            $arr[$k]['faceTime']=date('Y-m-d h:i:s',$v['faceTime']);
        }
        return $this->zy_json(true,'获取成功',$arr,200);
    }
    /**
     * 网格员下的学校
     */
    public function getGridSchool()
    {
        $param=input('');
        if(empty($param['uid'])){
            return zy_json_echo(false,'参数错误',[],-1);
        }

        $data = zy_userid_jwt($param['uid'],'de');
        if(isset($data['data'])){
            $param['uid']=$data['data'];
        }else{
            return zy_json_echo(false,'解密出错',$data,-1);
        }
        $user=Db::name('user')->where('id',$param['uid'])->find();
        if($user['user_type']!==3){
            return zy_json_echo(false,'该用户不是网格员','',-1);
        }
        $dir_arr=explode(',',$user['school']);
        $arr=[];
        foreach ($dir_arr as $k=>$v){
            $arr[]=Db::name('statistics_dir')->where('id',$v)->field('id,dirName')->find();
        }
        return $this->zy_json(true,'获取成功',$arr,200);

    }
    /**
     * 导出excel
     */
    public function out_excel(){
    $param=input('');
    if(!empty($param['out_img'])){
        $img_arr=explode(",", $param['out_img']);
        foreach ($img_arr as $k=>$v){
            $img_arr[$k]=$param['xlsCell'][$v][0];
            foreach ($param['xlsData'] as $key=>$val){
                $param['xlsData'][$key][$img_arr[$k]]=$this->download($val[$img_arr[$k]]);
            }
        }
    }
    $xlsName = $param['xlsName'];//表格标题
    $isImg=$param['isImg'];//第几列是图片
    //注意  数组第一个字段必须是小写  数组第二个（列标题）根据你的情况填写
    $xlsCell = $param['xlsCell'];
    if(empty($isImg) || !isset($isImg)){
        $isImg = 999;
    }
    $xlsData = $param['xlsData'];
    $this->exportExcel($xlsName,$xlsCell,$xlsData,$isImg);
    }

    /**
     * 导出excel2
     */
    public function export_excel()
    {
        $param=input('');

    }
    private function exportExcel($expTitle,$expCellName,$expTableData,$isImg){
        vendor("phpoffice.phpexcel.Classes.PHPExcel");
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $xlsTitle;//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        $objPHPExcel = new \PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle.' 导出时间:'.date('Y-m-d H:i:s'));
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->getActiveSheet()->getColumnDimension($cellName[$i])->setWidth(30);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]);
        }

        // Miscellaneous glyphs, UTF-8
        for($i=0;$i<$dataNum;$i++){
            for($j=0;$j<$cellNum;$j++){
                $imgstr = explode(',', $isImg);
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3), $expTableData[$i][$expCellName[$j][0]]);
                foreach ($imgstr as $item) {
                    if($j == $item){
                        $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(80);
                        $dir = 'uploadFile/';
                        $fileNameStr = $expTableData[$i][$expCellName[$j][0]];
                        // 图片生成
                        $names = iconv("utf-8", "gb2312",$fileNameStr);
                        if (file_exists($dir.$names)) {
                            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3),"");
                            $objDrawing[$j] = new \PHPExcel_Worksheet_Drawing();
                            $objDrawing[$j]->setPath($dir.$names);
                            // 设置宽度高度
                            $objDrawing[$j]->setHeight(80);//照片高度
                            $objDrawing[$j]->setWidth(80); //照片宽度
                            /*设置图片要插入的单元格*/
                            $objDrawing[$j]->setCoordinates($cellName[$j].($i+3));
                            // 图片偏移距离
                            $objDrawing[$j]->setOffsetX(12);
                            $objDrawing[$j]->setOffsetY(12);
                            $objDrawing[$j]->setWorksheet($objPHPExcel->getActiveSheet());
                        }else{
                            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3),"");
                        }
                    }
                }

            }
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $time = date("Ymd",time());
        if(!file_exists(('uploadFile/'.$time.'/'))){
            mkdir('uploadFile/'.$time.'/');
        }
        $download_path = "uploadFile/".$time."/".time().".xlsx";
        $objWriter->save($download_path);
        return zy_array(true,'导出成功',$this->request->domain().$this->request->root().$download_path,200,false);
    }
    private function download($url)
    {
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,30);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);//信任任何证书
        $file=curl_exec($ch);
        curl_close($ch);
        $img_url="temp_img\\".$this->getfour_str(8).".png";
        $url = fopen(ROOT_PATH."public\uploadFile\\".$img_url,"w");//打开文件准备写入
        fwrite($url,$file);//写入
        fclose($url);//关闭
        return $img_url;
    }
    private function getfour_str($len)
    {
        $chars_array = array(
            "0", "1", "2", "3", "4", "5", "6", "7", "8", "9",
        );
        $charsLen = count($chars_array) - 1;
        $outputstr = "";
        for ($i=0; $i<$len; $i++)
        {
            $outputstr .= $chars_array[mt_rand(0, $charsLen)];
        }
        return $outputstr;
    }
    /**
     * 警报监测-获取学校摄像头
     */
    public function getSchoolTake()
    {
        $param=input('');
        if(empty($param['uid'])){
            return zy_json_echo(false,'参数错误',[],-1);
        }
        $data = zy_userid_jwt($param['uid'],'de');
        if(isset($data['data'])){
            $param['uid']=$data['data'];
        }else{
            return zy_json_echo(false,'错误信息',$data,-1);
        }
        $user=Db::name('user')->where('id',$param['uid'])->find();
        $arr=[];
        if($user['user_type']==3){//网格员
            $dir_arr=explode(',',$user['school']);
            foreach ($dir_arr as $k=>$v){
                $cam=Db::name('statistics_cameras')->where('dir_id',$v)->field('id,cameraName,encodeDevIndexCode')->select();
                foreach ($cam as $val){
                    $arr[]=$val;
                }
            }
        }else if($user['user_type']==2){//学校
            $cam=Db::name('statistics_cameras')->where('dir_id',$user['school'])->field('id,cameraName,encodeDevIndexCode')->select();
            foreach ($cam as $val){
                $arr[]=$val;
            }
        }
        return $this->zy_json(true,'获取成功',$arr,200);
    }

    /**
     * 警报监测-陌生人信息
     */
    public function getStrangerAlarm()
    {
        $param=input('');
        if(empty($param['uid'])){
            return zy_json_echo(false,'参数错误',[],-1);
        }
        $param['uid'] = $this->verify_power($param['uid']);
        $paginate=empty($param['paginate'])?10:$param['paginate'];
        $page=empty($param['page'])?1:$param['page'];
        $where = '1 = 1 ';
        if(!empty($param['encodeDevIndexCode'])){
            $where .= " and b.encodeDevIndexCode = '".$param['encodeDevIndexCode']."'";
        }
        if(!empty($param['ageGroup'])){
            $where .= " and a.ageGroup = '".$param['ageGroup']."'";
        }
        if(!empty($param['gender'])){
            $where .= " and a.gender = '".$param['gender']."'";
        }
        if(!empty($param['glass'])){
            $where .= " and a.glass = '".$param['glass']."'";
        }
        if(!empty($param['start_time'])){
            $where .= " and a.faceTime >= '". strtotime($param['start_time'])."'";
        }
        if(!empty($param['end_time'])){
            $where .= " and a.faceTime <= '". strtotime($param['end_time'])."'";
        }
        $module_info = getModuleConfig('statistics','config','config.json');
        $module_info = json_decode($module_info,true);
        $user=Db::name('user')->where('id',$param['uid'])->find();
        $list=Db::name('statistics_face_stranger')->alias('a')
            ->join('cmf_statistics_cameras b','a.srcIndex=b.encodeDevIndexCode','left')
            ->where($where)
            ->field('a.*,b.cameraName')
            ->where('a.dir_id','in',$user['school'])
            ->order('stranger_status asc,id desc')
            ->paginate($paginate,false,['page'=>$page])->toArray();

        foreach ($list['data'] as $k=>$val){
            $list['data'][$k]['ageGroup']=$module_info['age'][$val['ageGroup']];
            $list['data'][$k]['glass']=$module_info['glasses'][$val['glass']];
            $list['data'][$k]['gender']=$module_info['sex'][$val['gender']];
            $url=$list['data'][$k]['faceUrl'];
            unset($list['data'][$k]['faceUrl']);
            $list['data'][$k]['faceUrl'][0]=$url;
            $url=$list['data'][$k]['bkgUrl'];
            unset($list['data'][$k]['bkgUrl']);
            $list['data'][$k]['bkgUrl'][0]=$url;
        }
        return $this->zy_json(true,'获取成功',$list,200);

    }
    /**
     * 警报监测-上报陌生人
     */
    public function upStrangerAlarm()
    {
        $param=input('');
        if(empty($param['uid'])||empty($param['id'])){
            return zy_json_echo(false,'请检查参数','',-1);
        }
        $param['uid'] = $this->verify_power($param['uid']);
        if(empty($param['examine_title'])||empty($param['examine_content'])){
            return zy_json_echo(false,'请填写上报内容','',-1);
        }
        $is_up=Db::name('statistics_face_stranger')
            ->where('id',$param['id'])
            ->update([
                "stranger_status"=>1,
                "examine_title"=>$param['examine_title'],
                "examine_content"=>$param['examine_content'],
                "examine_img"=>$param['examine_img'],
                "examine_uid"=>$param['uid'],
                "examine_time"=>time()
        ]);
        $find=Db::name('statistics_face_stranger')->alias('a')
            ->join('statistics_dir b','a.dir_id=b.id','left')
            ->join('user c','a.examine_uid=c.id','left')
            ->field('a.*,b.dirName,c.user_login,c.user_type')
            ->where('a.id',$param['id'])
            ->find();
        $time=time()-strtotime($find['faceTime']);
        if($time<86400){
            $facetime=substr($find['faceTime'],0,7);
            Db::name('statistics_dir_stat')->where('dir_id',$find['dir_id'])->where('month_section',$facetime)->setInc('dis_stranger');
        }
        $module_info = getModuleConfig('statistics','config','config.json');
        $module_info = json_decode($module_info,true);
        $find['ageGroup']=$module_info['age'][$find['ageGroup']];
        $find['glass']=$module_info['glasses'][$find['glass']];
        $find['gender']=$module_info['sex'][$find['gender']];
        $msg=json_encode([
            'type'=>'examine_stranger',
            'describe'=>'陌生人上报',
            'content'=> $find
        ]);
        Gateway::sendToGroup([1],$msg);
        if($is_up!==false){
            return $this->zy_json(true,'上报成功','',200);
        }
    }
    /**
     * 查看上报信息
     */
    public function getIdStrangerAlarm()
    {
        $param=input('');
        if(empty($param['uid'])||empty($param['id'])){
            return zy_json_echo(false,'请检查参数','',-1);
        }
        $param['uid'] = $this->verify_power($param['uid'],true);
        $find=Db::name('statistics_face_stranger')->alias('a')
            ->join('user b','a.examine_uid=b.id','left')
            ->where('a.id',$param['id'])
            ->field('a.*,b.user_login')
            ->find();
        $find['examine_time']=date("Y-m-d H:i:s",$find['examine_time']);
        $find['examine_content']=htmlspecialchars_decode($find['examine_content']);
        $find['examine_content']=cmf_replace_content_file_url($find['examine_content']);
        if(!empty($find['examine_img'])){
            $find['examine_img']=explode(",",$find['examine_img']);
            foreach ($find['examine_img'] as $k=>$v){
                $find['examine_img'][$k]=$this->request->domain().$this->request->root().'uploadFile/'.$v;
            }
        }
        return $this->zy_json(true,'获取成功',$find,200);
    }
    /**
     * 密码修改
     */
    public function upPassword()
    {
        $param=input('');
        if(empty($param['uid'])||empty($param['password'])||empty($param['new_pass'])||empty($param['again_pass'])){
            return zy_json_echo(false,'请检查参数','',-1);
        }
        $uid=$this->verify_power($param['uid']);
        if(strlen($param['new_pass'])<6){
            return zy_json_echo(false,'请输入6位以上密码','',-1);
        }
        if($param['new_pass']!==$param['again_pass']){
            return zy_json_echo(false,'两次密码不相同','',-1);
        }
        $password=cmf_password($param['password']);
        $new_password=cmf_password($param['new_pass']);
        $find=Db::name('user')->where('id',$uid)->find();
        if($find['user_pass']!==$password){
            return zy_json_echo(false,'密码错误','',-1);
        }
        if($password==$new_password){
            return zy_json_echo(false,'新密码不能与原密码相同','',-1);
        }
        $is_up=Db::name('user')->where('id',$uid)->update(['user_pass'=>$new_password]);
        if($is_up!==false){
            return zy_json_echo(true,'操作成功','',200);
        }else{
            return zy_json_echo(false,'修改失败，请重试','',-1);
        }

    }
    /**
     * 获取录像
     */
    public function getVideoUrl()
    {
        $find=Db::name('statistics_config')->where('id',1)->find();
//        $module_info = getModuleConfig('statistics','config','video_config.json');
//        $find = json_decode($module_info,true);
        return $this->zy_json(true,'获取成功',$find,200);
    }
    /**
     * 保存录像
     */
    public function upVideoUrl()
    {
        $param=input('');
        if(empty($param['uid'])||empty($param['video_url'])||empty($param['img_url'])||empty($param['history_video_url'])){
            return zy_json_echo(false,'请检查参数','',-1);
        }
//        $config = [
//            'video_url'=>$param['video_url'],
//            'img_url'=>$param['img_url'],
//            'history_video_url'=>$param['history_video_url'],
//            'history_img_url'=>$param['history_img_url']
//        ];
//
//        saveModuleConfigData('statistics','config','video_config.json',$config);
//        $this->success("修改成功");
        $is_up=Db::name('statistics_config')->where('id',1)->update([
            'video_url'=>$param['video_url'],
            'img_url'=>$param['img_url'],
            'history_video_url'=>$param['history_video_url'],
            'history_img_url'=>$param['history_img_url']
        ]);

        if($is_up!==false){
            return zy_json_echo(true,'操作成功','',200);
        }else{
            return zy_json_echo(false,'修改失败，请重试','',-1);
        }
    }
    /**
     * 学校折线图
     */
    public function schoolLineChart()
    {
        $param = input('');
        $param['uid']=$this->verify_power($param['uid'],true);
        if (empty($param['dir_id'])) {
            return zy_json_echo(false, '请检查参数', '', -1);
        }
//        $dir = Db::name('statistics_dir')->where('cameraIndexCode', $param['indexCode'])->select();
        $arr = [];
            $year = Base::year_face($param['dir_id']);
//            $arr[$k]['dirName'] = $v['dirName'];
//            $arr[$k]['id'] = $v['id'];
            foreach ($year as $key => $val) {
                $arr[0][$key] = $val['month_section'];
                $arr[1][$key] = $val['stranger_face_num'] + $val['emphasis_face_num'];
                $arr[2][$key] = $val['dis_pro'];
            }
        return zy_json_echo(true,'获取成功',$arr,200);
    }
    /**
     * 每区域折线图
     */
    public function regionsLineChart()
    {
        $param = input('');
        $param['uid']=$this->verify_power($param['uid'],true);
        if(empty($param['indexCode'])){
            return zy_json_echo(false, '请检查参数', '', -1);
        }
        $dir = Db::name('statistics_dir')->where('cameraIndexCode', $param['indexCode'])->select();
        $arr=[];
        for ($i=0;$i<12;$i++) {
            $month = date('Y-m', strtotime(-$i . 'month'));
            $arr[0][$i] = $month;
            $arr[1][$i] = 0;
            $dis_num = 0;
            foreach ($dir as $k=>$v){
                $stat=Db::name('statistics_dir_stat')->where('dir_id',$v['id'])->where('month_section',$month)->find();
                if(empty($stat)){
                    $stat['id']=$v['id'];
                    $stat['month_section']=$month;
                    $stat['stranger_face_num']=0;
                    $stat['emphasis_face_num']=0;
                    $stat['dis_stranger']=0;
                    $stat['dis_emphasis']=0;
                }
                $arr[1][$i]+=$stat['stranger_face_num']+$stat['emphasis_face_num'];
                $dis_num += $stat['dis_stranger']+$stat['dis_emphasis'];
            }
//            $arr[2][$i]['face_dis'] = $dis_num/$arr[1][$i]['face_num'];
            $arr[2][$i]=$dis_num<1?0:round($dis_num/$arr[1][$i]*100);

        }
        return zy_json_echo(true,'获取成功',$arr,200);
    }
    /**
     * 区域下网格员和学校
     */
    public function userList()
    {
        $param=input('');
        $regions=Db::name('statistics_regions')->where('abbr','not null')->where('parentIndexCode','root000000')->select();
        $arr=[];
        foreach ($regions as $ka=>$va){
            $arr[$ka]['label']=$va['name'];
            $arr[$ka]['value']=$va['indexCode'];
            $arr[$ka]["children"][0]['label']="网格员";
            $arr[$ka]["children"][0]['value']=3;
            $arr[$ka]["children"][0]['children']=$this->getschoolIdUser($va['indexCode'],3);
            $arr[$ka]["children"][1]['label']="学校";
            $arr[$ka]["children"][1]['value']=2;
            $arr[$ka]["children"][1]['children']=$this->getschoolIdUser($va['indexCode'],2);
        }
        return zy_json_echo(true,'获取成功',$arr,200);
    }
    /**
     * 区域下网格员或学校
     */
    public function getschoolIdUser($indexCode=null,$user_type=null)
    {
        $dir=Db::name('statistics_dir')->where("cameraIndexCode",$indexCode)->select();
        $arr=[];
        $index=0;
        foreach ($dir as $k=>$v){
            $user=Db::name('user')->where('user_type',$user_type)->where('find_in_set('.$v['id'].',school)')->select()->toArray();
            foreach ($user as $key=>$val){
                $arr[$index]['label']=$val['user_login'];
                $arr[$index]['value']=$val['id'];
                $index++;
            }
        }
        $arr=array_unique($arr,SORT_REGULAR);
        return $arr;
    }
    /**
     * 管理员添加工单
     */
    public function upQuestion()
    {
        $param=input('');
        if(empty($param['id_str'])||empty($param['title'])||empty($param['content'])){
            return zy_json_echo(false,'请检查参数','',-1);
        }
        $uid=$this->verify_power($param['uid'],true);
        $id_arr=explode(",", $param['id_str']);
        foreach ($id_arr as $k=>$v){
            $id= Db::name("statistics_question")->insertGetId([
                "question_sn"=>$this->get_order_sn().rand(10000,99999),
                "send_uid"=>$uid,
                "rec_uid"=>$v,
                "status"=>0,
                "create_time"=>time(),
                "add_time"=>time()
            ]);
            Db::name("statistics_ques_detail")->insert([
                "title"=>$param['title'],
                "content"=>$param['content'],
                "file"=>empty($param['file'])?"":$param['file'],
                "que_id"=>$id,
                "msg_time"=>time()
            ]);
        }
        return zy_json_echo(true,'操作成功',"",200);
    }
    /**
     * 管理员显示提问。
     */
    public function getQuestionList()
    {
        $param=input('');
        $uid=$this->verify_power($param['uid']);
        $paginate=empty($param['paginate'])?10:$param['paginate'];
        $page=empty($param['page'])?1:$param['page'];
        $where = '1 = 1 ';
        if(!empty($param['question_sn'])){
            $where .= " and question_sn like '%".$param['question_sn']."%'";
        }
        if(!empty($param['user_login'])){
            $where .= " and b.user_login like '%".$param['user_login']."%'";
        }

        if(!empty($param['star_reply_time'])){
            $where .= " and reply_time >= '". strtotime($param['star_reply_time'])."'";
        }
        if(!empty($param['end_reply_time'])){
            $where .= " and reply_time <= '". strtotime($param['end_reply_time'])."'";
        }

        $type=Db::name('user')->where('id',$uid)->value('user_type');
        if($type==1){
            if(!empty($param['star_create_time'])){
                $where .= " and a.create_time >= '". strtotime($param['star_create_time'])."'";
            }
            if(!empty($param['end_create_time'])){
                $where .= " and a.create_time <= '". strtotime($param['end_create_time'])."'";
            }
            $list=Db::name("statistics_question")
                ->alias('a')
                ->join("user b","a.rec_uid=b.id","left")
                ->field("a.*,b.user_login")
                ->where("send_uid",$uid)
                ->where($where)
                ->order("a.id desc")
                ->paginate($paginate,false,['page'=>$page])
                ->each(function($item, $key) {
                    $item['create_time']=empty($item['create_time'])?"":date('Y-m-d h:i:s',$item['create_time']);
                    $item['reply_time']=empty( $item['reply_time'])?"":date('Y-m-d h:i:s',$item['reply_time']);
                    $item['add_time']=empty( $item['add_time'])?"":date('Y-m-d h:i:s',$item['add_time']);
                    $file = $item['file'];
                    $reply_file = $item['reply_file'];
                    unset($item['file']);
                    unset($item['reply_file']);
                    if(!empty($file)){
                        $file=explode(',',$file);
                        foreach ($file as $k=>$v){
                            $item['file'][$k]=$this->request->domain() . $this->request->root() . 'uploadFile/' . $v;
                        }
                    }
                    if(!empty($reply_file)){
                        $reply_file=explode(',',$reply_file);
                        foreach ($reply_file as $k=>$v){
                            $item['reply_file'][$k]=$this->request->domain() . $this->request->root() . 'uploadFile/' . $v;
                        }
                    }
                    return $item;
                });
        }else{
            if(!empty($param['star_create_time'])){
                $where .= " and create_time >= '". strtotime($param['star_create_time'])."'";
            }
            if(!empty($param['end_create_time'])){
                $where .= " and create_time <= '". strtotime($param['end_create_time'])."'";
            }
            $list=Db::name("statistics_question")
                ->where("rec_uid",$uid)
                ->where($where)
                ->order("id desc")
                ->paginate($paginate,false,['page'=>$page])
                ->each(function($item, $key) {
                    $item['create_time']=empty($item['create_time'])?"":date('Y-m-d h:i:s',$item['create_time']);
                    $item['reply_time']=empty( $item['reply_time'])?"":date('Y-m-d h:i:s',$item['reply_time']);
                    $file = $item['file'];
                    $reply_file = $item['reply_file'];
                    unset($item['file']);
                    unset($item['reply_file']);
                    if(!empty($file)){
                        $file=explode(',',$file);
                        foreach ($file as $k=>$v){
                            $item['file'][$k]=$this->request->domain() . $this->request->root() . 'uploadFile/' . $v;
                        }
                    }
                    if(!empty($reply_file)){
                        $reply_file=explode(',',$reply_file);
                        foreach ($reply_file as $k=>$v){
                            $item['reply_file'][$k]=$this->request->domain() . $this->request->root() . 'uploadFile/' . $v;
                        }
                    }
                    return $item;
                });
        }
        return zy_json_echo(true,'获取成功',$list,200);

    }

    /**
     * 管理员查看列表
     */
    public function adminQuestionList()
    {
        $param=input('');
        $uid=$this->verify_power($param['uid']);
        $paginate=empty($param['paginate'])?10:$param['paginate'];
        $page=empty($param['page'])?1:$param['page'];
        $where = '1 = 1 ';
        if(!empty($param['question_sn'])){
            $where .= " and question_sn like '%".$param['question_sn']."%'";
        }
        if(!empty($param['user_login'])){
            $where .= " and b.user_login like '%".$param['user_login']."%'";
        }
        if(!empty($param['star_reply_time'])){
            $where .= " and reply_time >= '". strtotime($param['star_reply_time'])."'";
        }
        if(!empty($param['end_reply_time'])){
            $where .= " and reply_time <= '". strtotime($param['end_reply_time'])."'";
        }
        if(!empty($param['star_add_time'])){
            $where .= " and a.add_time >= '". strtotime($param['star_add_time'])."'";
        }
        if(!empty($param['end_add_time'])){
            $where .= " and a.add_time <= '". strtotime($param['end_add_time'])."'";
        }
        $list=Db::name("statistics_question")
            ->alias('a')
            ->join("user b","a.rec_uid=b.id","left")
            ->field("a.*,b.user_login")
            ->where("send_uid",$uid)
            ->where($where)
            ->order("a.id desc")
            ->paginate($paginate,false,['page'=>$page])
            ->each(function($item, $key) {
                $item['create_time']=empty($item['create_time'])?"":date('Y-m-d H:i:s',$item['create_time']);
                $item['reply_time']=empty($item['reply_time'])?"":date('Y-m-d H:i:s',$item['reply_time']);
                $item['add_time']=empty( $item['add_time'])?"":date('Y-m-d H:i:s',$item['add_time']);
                return $item;
            });
        return zy_json_echo(true,'获取成功',$list,200);
    }

    /**
     * 网格员和学校查看
     */
    public function lowQuestionList()
    {
        $param=input('');
        $uid=$this->verify_power($param['uid']);
        $paginate=empty($param['paginate'])?10:$param['paginate'];
        $page=empty($param['page'])?1:$param['page'];
        $where = '1 = 1 ';
        if(!empty($param['question_sn'])){
            $where .= " and question_sn like '%".$param['question_sn']."%'";
        }
        if(!empty($param['star_reply_time'])){
            $where .= " and reply_time >= '". strtotime($param['star_reply_time'])."'";
        }
        if(!empty($param['end_reply_time'])){
            $where .= " and reply_time <= '". strtotime($param['end_reply_time'])."'";
        }
        if(!empty($param['star_create_time'])){
            $where .= " and create_time >= '". strtotime($param['star_create_time'])."'";
        }
        if(!empty($param['end_create_time'])){
            $where .= " and create_time <= '". strtotime($param['end_create_time'])."'";
        }
        $list=Db::name("statistics_question")
            ->where("rec_uid",$uid)
            ->where($where)
            ->order("id desc")
            ->paginate($paginate,false,['page'=>$page])
            ->each(function($item, $key) {
                $item['create_time']=empty($item['create_time'])?"":date('Y-m-d H:i:s',$item['create_time']);
                $item['reply_time']=empty($item['reply_time'])?"":date('Y-m-d H:i:s',$item['reply_time']);
                $item['add_time']=empty( $item['add_time'])?"":date('Y-m-d h:i:s',$item['add_time']);
                return $item;
            });
        return zy_json_echo(true,'获取成功',$list,200);
    }
    /**
     * 学校和网格员回复
     */
    public function replyQuestion()
    {
        $param=input('');
        $param['uid']=$this->verify_power($param['uid']);
        if(empty($param['id'])||empty($param['reply_title'])||empty($param['reply_content'])){
            return zy_json_echo(false,'请检查参数','',-1);
        }
        $time=time();
            Db::name("statistics_question")
            ->where("id",$param['id'])
            ->update([
                "status"=>1,
                "reply_time"=>$time
            ]);
            $is_up=Db::name("statistics_ques_detail")
                ->insert([
                    "title"=>$param['reply_title'],
                    "content"=>$param['reply_content'],
                    "file"=>empty($param['file'])?"":$param['file'],
                    "msg_time"=>$time,
                    "que_id"=>$param['id'],
                    "flow"=>1
                ]);
        if($is_up!==false){
            return zy_json_echo(true,'回复成功',"",200);
        }else{
            return zy_json_echo(false,'回复失败','',-1);
        }
    }
    /**
     * 查看交流
     */
    public function getMsgList()
    {
        $param=input('');
        if(empty($param['uid'])||empty($param['id'])){
            return zy_json_echo(false,'请检查参数','',-1);
        }
        $list=Db::name("statistics_ques_detail")->alias('a')
            ->join("statistics_question b","a.que_id=b.id","left")
            ->join("user c","b.send_uid=c.id","left")
            ->join("user d","b.rec_uid=d.id","left")
            ->where("que_id",$param['id'])
            ->order("id asc")
            ->field("a.*,c.user_login as send_name,d.user_login as rec_name")
            ->select()
            ->each(function($item, $key) {
                $item['content']=htmlspecialchars_decode($item['content']);
                $item['content']=cmf_replace_content_file_url($item['content']);
                $item['msg_time']=empty($item['msg_time'])?"":date('Y-m-d H:i:s',$item['msg_time']);
                $file = $item['file'];
                unset($item['file']);
                if(!empty($file)){
                    $file=explode(',',$file);
                    foreach ($file as $k=>$v){
                        $item['file'][$k]=$this->request->domain() . $this->request->root() . 'uploadFile/' . $v;
                    }
                }
                return $item;
            });
        return zy_json_echo(true,'获取成功',$list,200);
    }
    /**
     * 关闭提问
     */
    public function closeQuestion()
    {
        $param=input("");
        if(empty($param['id_str'])){
            return zy_json_echo(false,'参数错误','',-1);
        }
        $id_arr=explode(',',$param['id_str']);
        foreach ($id_arr as $k=>$v){
            $is_up=Db::name("statistics_question")
                ->where("id",$v)
                ->update([
                    "status"=>2
                ]);
        }
        return zy_json_echo(true,'关闭成功',"",200);
    }
    /**
     *批量追加提问
     */
    public function repetitionQuestion()
    {
        $param=input('');
        if(empty($param['id_str'])||empty($param['title'])||empty($param['content'])){
            return zy_json_echo(false,'请检查参数','',-1);
        }
        $uid=$this->verify_power($param['uid'],true);
        $id_arr=explode(",", $param['id_str']);
        $time=time();
        foreach ($id_arr as $k=>$v){
             Db::name("statistics_question")->where('id',$v)->update([
                "status"=>0,
                "create_time"=>$time
            ]);
            Db::name("statistics_ques_detail")->insert([
                "title"=>$param['title'],
                "content"=>$param['content'],
                "file"=>empty($param['file'])?"":$param['file'],
                "que_id"=>$v,
                "msg_time"=>$time
            ]);

        }
        return zy_json_echo(true,'操作成功',"",200);
    }
    /**
     * 获取区域摄像头在线
     */
    public function online($indexCode=null)
    {
        if(empty($indexCode)){
            $indexCode="root000000";
        }
        $postData = [
            "regionId"=>$indexCode,
            "includeSubNode"=>1,
            "pageNo"=> 1,
            "pageSize"=> 2000,
            "treeCode"=> "0",
            "resourceType"=> "camera"
        ];
        $hk=new Haikang();
        $result = $hk->doCurl($postData, $hk->online_get);
        $arr=json_decode($result,true);
        $on=0;
        $off=0;
        if(isset($arr['data']['list'])){
            foreach ($arr['data']['list'] as $k=>$v){
                if($v['online']==1){
                    $on+=1;
                }else{
                    $off+=1;
                }
            }
        }
        return zy_json_echo(true,'操作成功',[["name"=>"在线","value"=>$on],["name"=>"不在线","value"=>$off]],200);
    }
    /**
     * 获取学校下摄像头是否在线
     */
    public function schoolOnline($dir_id=null)
    {
        if(empty($dir_id)){
            return zy_json_echo(false,'请输入dir_id','',-1);
        }
        $list=Db::name("statistics_cameras")->where("dir_id",$dir_id)->select()->toArray();
        $code=[];
        foreach ($list as $k=>$v){
            $code[]=$v['cameraIndexCode'];
        }
        $postData = [
            "indexCodes"=>$code,
            "pageNo"=> 1,
            "pageSize"=> 2000,
            "treeCode"=> "0",
            "resourceType"=> "camera"
        ];
        $hk=new Haikang();
        $result = $hk->doCurl($postData, $hk->online_get);
        $arr=json_decode($result,true);
        foreach ($list as $k=>$v){
            foreach ($arr['data']['list'] as $key=>$val){
                if($v['cameraIndexCode']==$val['indexCode']){
                    $list[$k]['value']=$val["online"]+1;
                    $list[$k]['label']=$v["cameraName"];
                }
            }
        }
        return zy_json_echo(true,'获取成功',$list,200);
    }
    /**
     * 验证权限
     */
    public function verify_power($uid=null,$is_admin=false)
    {
        $data = zy_userid_jwt($uid,'de');
        if(isset($data['data'])){
            $uid=$data['data'];
        }else{
            return zy_json_echo(false,'错误信息',$data,-1);
        }
        if($is_admin){
            $user=Db::name('user')->where('id',$uid)->find();
            if($user['user_type']!==1){
                return zy_json_echo(false,'您没有权限查看','',-1);
            }
        }
        return $uid;
    }
    private function zy_json($status=false,$message="",$data=[],$code=0)
    {
        $datas['status']=$status?'success':'error';
        $datas['message']=$message;
        $datas['code']=$status?200:$code;
        $datas['data']=$data;

        return json_encode($datas,JSON_UNESCAPED_UNICODE);
    }
    private function get_order_sn()
    {
        mt_srand((double) microtime() * 1000000);
        return date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

}
