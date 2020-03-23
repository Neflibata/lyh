<?php
namespace plugins\statistics\controller;
use cmf\controller\PluginRestBaseController;//引用插件基类
use think\Db;
use think\Request;
use plugins\statistics\model\BaseModel as base;
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
        'base_img' => 'https://111.3.64.34:446/ngx/proxy?i=',//访问海康图片地址 base后这后面
        'hq_name'=>'临海市',
        'rank'=>'县级市',
        'regions'=>16,
        'school'=>168,
        'no_school'=>20,
        'yes_school'=>148,
        'kitchen'=>323,
        'damp'=>23,
        'food_admin'=>472,
        'kitchen'=>20,
        'damp_admin'=>212
    );
    /**
     * 执行构造
     */
    function __construct()
    {
        header("content-type:text/html;charset=utf-8");
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
        $neadArg = ["nickname"=>[true, 0, "请填写姓名"], "company"=>[true, 0, "请填写公司名称"], "mobile"=>[true, 1, "请填写手机号"], "face_thumb"=>[true, 0, "请上传人脸照片"],"health_card"=>[true, 0, "请上传健康证照片"] , "health_endtime"=>[true, 0, "请填写健康证到期时间"], "member_type"=>[true, 0, "请填写人员类别"],"health_id_card"=>[true, 1, "请输入健康证号"], "id_card"=>[true, 1,"请输入身份证号"]];
        $dataInfo = checkArg($neadArg, $data);
        $id_card = array_pop($dataInfo);
        $model = new base("member_info");
        $res = $model->get_one(["id_card"=>["=", $id_card]]);
        $upOrIn = !empty($res)?true:false; //true是数据库没有该数据，其他是有
        $dataInfo["addtime"] = date("Y-m-d H:i:s",time());
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
            if($upOrIn == 0)
            {
                $dataInfo["id_card"] = $id_card;
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
        else
            return [false, $retData["msg"]];
        return [true, ""];

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
        $where['parentIndexCode']=['eq','root000000'];
        $where['name']=['notlike','%FD%'];//菜单不要第一级
        $regions=Db::name('statistics_regions')->where($where)->field('*,name as label')->order('id asc')->select()->toArray();
        $dir=Db::name('statistics_dir')->field('*,dirName as label')->select()->toArray();
        $cameras=Db::name('statistics_cameras')->field('*,cameraName as label')->select()->toArray();
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
        }
        return zy_json_echo(true,'获取成功',$regions,200);
    }
    /**
     * 地图展示-左侧菜单3级
     */
    public function getSchoolDir()
    {
        $where=[];
        $where['parentIndexCode']=['eq','root000000'];
        $where['name']=['notlike','%FD%'];//菜单不要第一级
        $regions=Db::name('statistics_regions')->where($where)->field('*,name as label')->order('id asc')->select()->toArray();
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
        $param = [
            'method' => 'OnEventNotify',
            'params' => [
                'ability' => 'event_face_recognition',
                'events' => [
                    [
                        'data' => [
                            'faceRecognitionResult' => [
                                'snap' => [
                                    'ageGroup' => 'young',
                                    'bkgUrl' => 'http://10.67.184.86:80/picture/Streaming/tracks/103/?name=ch0001_03000001329060946534400136268&size=136268',
                                    'faceTime' => '2019-05-27 14:17:11',
                                    'faceUrl' => 'http://10.67.184.86:80/picture/Streaming/tracks/103/?name=ch0001_03000001329060945868800006112&size=6112',
                                    'gender' => 'male',
                                    'glass' => 'yes',
                                ],
                                'srcEventId' => '9409397C-26E4-48AB-8DB0-D4A48B00E32B',
                            ],
                            'resInfo' => [
                                [
                                    'cn' => '184.156_6024_抓拍',
                                    'indexCode' => '86f0d4c232fc4807af9f7b98490a5891',
                                    'resourceType' => 'camera',
                                ],
                            ],
                            'srcEventId' => '9409397C-26E4-48AB-8DB0-D4A48B00E32B',
                        ],
                        'eventId' => '709260f8-13ad-405e-a405-c5bb652cd2e5',
                        'eventType' => 1644171265,
                        'happenTime' => '2019-05-27T14:17:11.000+08:00',
                        'srcIndex' => '86f0d4c232fc4807af9f7b98490a5891',
                        'srcName' => '184.156_6024_抓拍',
                        'srcType' => 'camera',
                        'status' => 0,
                        'timeout' => 0,
                    ],
                ],
                'sendTime' => '2019-05-27T14:17:05.761+08:00',
            ],
        ];
//        file_put_contents('C:\WWW\js2\lhyd\public\plugins\statistics\log\stranger.txt', serialize($param), FILE_APPEND);
        $data = $param['params']['events'];
        foreach ($data as $k=>$v){
            $id=Db::name('statistics_face_stranger')->insertGetId([
                'ageGroup' => $v['data']['faceRecognitionResult']['snap']['ageGroup'],
                'gender' => $v['data']['faceRecognitionResult']['snap']['gender'],
                'glass' => $v['data']['faceRecognitionResult']['snap']['glass'],
                'bkgUrl' => $v['data']['faceRecognitionResult']['snap']['bkgUrl'],
                'faceUrl' => $v['data']['faceRecognitionResult']['snap']['faceUrl'],
                'faceTime' => $v['data']['faceRecognitionResult']['snap']['faceTime'],
                'resourceType' => $v['data']['resInfo'][0]['resourceType'],
                'indexCode' => $v['data']['resInfo'][0]['indexCode'],
                'cn' => $v['data']['resInfo'][0]['cn'],
                'srcIndex'=>$v['srcIndex']
            ]);
            $find=Db::name('statistics_face_stranger')->where('id',$id)->find();
            $find['bkgUrl']=$this->config['base_img'].base64_encode($find['bkgUrl']);
            $find['faceUrl']=$this->config['base_img'].base64_encode($find['bkgUrl']);
            $msg = [
                'type'=>'stranger',
                'content'=>$find
            ];
            Gateway::sendToAll(json_encode($msg));
        }
    }
    /**
     * 接收事件-保存重点人员
     */
    public function addEmphasis()
    {
        $param = [
            'method' => 'OnEventNotify',
            'params' => [
                'ability' => 'event_face_recognition',
                'events' => [
                    [
                        'data' => [
                            'faceRecognitionResult' => [
                                'faceMatch' => [
                                    [
                                        'certificate' => '342623198710177156',
                                        'faceGroupCode' => '6a4f52d1024f4b5ea391452e4c07d05c',
                                        'faceGroupName' => '人脸分组1',
                                        'faceInfoCode' => '7159c1037d434b93bb6af01f5bfaf685',
                                        'faceInfoName' => '秦华',
                                        'faceInfoSex' => 'male',
                                        'facePicUrl' => 'http://10.67.184.86:80/FDLib?FDID=5EC907D0E97C49188342020A274AA9BE&pId=48D135B74EE447E7A336A7B5DEA97E5A&size=66455',
                                        'similarity' => 0.03,
                                    ],
                                ],
                                'snap' => [
                                    'ageGroup' => 'young',
                                    'bkgUrl' => 'http://10.67.184.86:80/picture/Streaming/tracks/103/?name=ch0001_03000001329061154662400130164&size=130164',
                                    'faceTime' => '2019-05-27 14:17:56',
                                    'faceUrl' => 'http://10.67.184.86:80/picture/Streaming/tracks/103/?name=ch0001_03000001329061153843200007456&size=7456',
                                    'gender' => 'female',
                                    'glass' => 'yes',
                                ],
                                'srcEventId' => 'BE9A6EB8-4CCC-4D53-87FC-66BB28DA4A5A',
                            ],
                            'resInfo' => [
                                [
                                    'cn' => '184.156_6024_抓拍',
                                    'indexCode' => '86f0d4c232fc4807af9f7b98490a5891',
                                    'resourceType' => 'camera',
                                ],
                            ],
                            'srcEventId' => 'BE9A6EB8-4CCC-4D53-87FC-66BB28DA4A5A',
                        ],
                        'eventId' => 'e91d79f5-543c-4e9f-8c4a-82a113e8d384',
                        'eventType' => 1644175361,
                        'happenTime' => '2019-05-27T14:17:56.000+08:00',
                        'srcIndex' => '6a4f52d1024f4b5ea391452e4c07d05c',
                        'srcName' => '人脸分组1',
                        'srcType' => 'facegroup',
                        'status' => 0,
                        'timeout' => 0,
                    ],
                ],
                'sendTime' => '2019-05-27T14:17:50.747+08:00',
            ],
        ];
//        file_put_contents('C:\WWW\js2\lhyd\public\plugins\statistics\log\emphasis.txt', serialize($param), FILE_APPEND);
        $data = $param['params']['events'];
        foreach ($data as $k=>$v){
            $id=Db::name('statistics_face_emphasis')->insertGetId([
                'ageGroup' => $v['data']['faceRecognitionResult']['snap']['ageGroup'],
                'gender' => $v['data']['faceRecognitionResult']['snap']['gender'],
                'glass' => $v['data']['faceRecognitionResult']['snap']['glass'],
                'bkgUrl' => $v['data']['faceRecognitionResult']['snap']['bkgUrl'],
                'faceUrl' => $v['data']['faceRecognitionResult']['snap']['faceUrl'],
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
                'cn' => $v['data']['resInfo'][0]['cn']

            ]);
            $find=Db::name('statistics_face_emphasis')->where('id',$id)->find();
            $find['bkgUrl']=$this->config['base_img'].base64_encode($find['bkgUrl']);
            $find['faceUrl']=$this->config['base_img'].base64_encode($find['bkgUrl']);

            $msg = [
                'type'=>'emphasis',
                'content'=>$find
            ];
            Gateway::sendToAll(json_encode($msg));
        }
    }
    /**
     * 接收事件-保存GPS
     */
    public function addGps(){
        $param = input('');
        file_put_contents('C:\WWW\js2\lhyd\public\plugins\statistics\log\gps.txt', serialize($param), FILE_APPEND);
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

        $param=input('');
//        file_put_contents('C:\WWW\js2\lhyd\public\plugins\statistics\log\face.txt',  serialize($param), FILE_APPEND);
        file_put_contents('D:\phpstudy\PHPTutorial\WWW\lhyd\public\plugins\statistics\log\face.txt',  serialize($param), FILE_APPEND);
        $data = $param['params']['events'][0]['data'];
        Db::name('statistics_face_test')->insert([
            'URL'=>$data['captureLibResult'][0]['faces'][0]['URL'],
            'ageGroup'=>$data['captureLibResult'][0]['faces'][0]['age']['ageGroup'],
            'height'=>$data['captureLibResult'][0]['faces'][0]['faceRect']['height'],
            'width'=>$data['captureLibResult'][0]['faces'][0]['faceRect']['width'],
            'x'=>$data['captureLibResult'][0]['faces'][0]['faceRect']['x'],
            'y'=>$data['captureLibResult'][0]['faces'][0]['faceRect']['y'],
        ]);
    }
    /**
     * 添加计划
     */
    public function addEmphasisPlan()
    {
        $postData = [
            "name"=> "重点人员测试计划",
            "faceGroupIndexCodes"=>["f77b25c4-8a25-4d78-91a1-700320320449","5fd42a66-8e55-46fc-9f36-1ffb4f80558f"],//人脸分组
            "recognitionResourceType"=> "FACE_RECOGNITION_SERVER",//资源类型
            "recognitionResourceIndexCodes"=>[],//识别资源
            "cameraIndexCodes"=>["54d7449b3e69444886ffb09e2e75ae69"],//抓拍点通道
            "threshold"=>70,//重点人员相似度报警，范围[1, 100)
            "description"=>"测试识别计划",
            "timeBlockList"=>[]
        ];

        $hk=new Haikang();
        $result = $hk->doCurl($postData, $hk->black_addition);
        $arr=json_decode($result,true);
        halt($arr);
    }
    /**
     * 地图选择目录-返回区域、学校、摄像点列表
     */
    public function getMapSelectDir()
    {
        $where=[];
        $where['parentIndexCode']=['eq','root000000'];
        $where['name']=['notlike','%FD%'];
        $list['regions']=Db::name('statistics_regions')->where($where)->order('id asc')->select()->toArray();
        $list['dir']=Db::name('statistics_dir')->select()->toArray();
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
        $where['abbr'] = ['exp','!= ""'];
        $list=Db::name('statistics_regions')
            ->where($where)
            ->field('id,name,longitude,latitude,abbr')
            ->order('id asc')
            ->select();
        return zy_json_echo(true,'获取成功',$list,200);
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
            return zy_array(false,'请传入用户id(user_id)','',300,false);
        }
        if(!empty($param['dir_id'])){
            $where['b.dir_id']=['eq',$param['dir_id']];
        }
        if(!empty($param['indexCode'])){
            $where['c.indexCode']=['eq',$param['indexCode']];
        }
        if(!empty($param['start_time'])&&!empty($param['end_time'])){
            $where['a.faceTime']=['between time',[$param['start_time'],$param['end_time']]];
        }
        $list = Db::name('statistics_face_stranger')->alias('a')
            ->join('cmf_statistics_cameras b', 'a.srcIndex=b.cameraIndexCode', 'left')
            ->join('cmf_statistics_regions c', 'b.regionIndexCode=c.indexCode', 'left')
            ->where($where)
            ->field('a.id,a.faceUrl,a.faceTime,a.stranger_status,b.cameraName,c.name')
            ->paginate($paginate,false,['page'=>$page])
            ->each(function($item, $key){
                $item['faceTime']=date("Y-m-d h:i:s",$item['faceTime']);
                $url=$item['faceUrl'];
                unset($item['faceUrl']);
                $item['faceUrl'][0]=$url;
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
        $school=Db::name('statistics_dir')->select();
        $cameras=Db::name('statistics_cameras')->select();
        $arr=[];
        foreach ($school as $k=>$v){
            $arr[$k]['school_name']=$v['dirName'];
            $arr[$k]['day']=0;
            $arr[$k]['week']=0;
            $arr[$k]['month']=0;
            foreach($cameras as $key=>$val){
                if($v['id']==$val['dir_id']){
                    $arr[$k]['indexCode']=$v['cameraIndexCode'];
                    $arr[$k]['dir_id']=$val['dir_id'];
                    $arr[$k]['day']+=Db::name('statistics_face_stranger')->where('srcIndex',$val['cameraIndexCode'])->where('stranger_status','lt',2)->whereTime('faceTime', 'd')->count();
                    $arr[$k]['week']+=Db::name('statistics_face_stranger')->where('srcIndex',$val['cameraIndexCode'])->where('stranger_status','lt',2)->whereTime('faceTime', 'w')->count();
                    $arr[$k]['month']+=Db::name('statistics_face_stranger')->where('srcIndex',$val['cameraIndexCode'])->where('stranger_status','lt',2)->whereTime('faceTime', 'm')->count();
                }
            }
        }
        $num=empty($param['num'])?999:$param['num'];
        if(!empty($param['code'])){
            $arrb=array_column($arr, 'day');
            array_multisort($arrb, SORT_DESC, $arr);
            $arr=array_slice($arr,0,$num);
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
     * 陌生人监控-提醒学校
     */
    public function reShoolStranger($id=null)
    {
        if(empty($id)){
            return zy_json_echo(false,'参数错误','',-1);
        }
        $find = Db::name('statistics_face_stranger')->alias('a')
            ->join('cmf_statistics_cameras b', 'a.srcIndex=b.encodeDevIndexCode', 'left')
            ->join('cmf_statistics_regions c', 'b.regionIndexCode=c.indexCode', 'left')
            ->where('a.id',$id)
            ->field('a.id,a.faceUrl,a.faceTime,a.stranger_status,b.cameraName,b.dir_id,c.name')
            ->find();
        $find['faceTime']=date("Y-m-d h:i:s",$find['faceTime']);
        $url=$find['faceUrl'];
        unset($find['faceUrl']);
        $find['faceUrl'][0]=$url;
        $user=Db::name('user')->where('user_type',2)->where('find_in_set('.$find['dir_id'].',school)')->column('id');
        $msg = [
            'type'=>'stranger',
            'content'=>$find
        ];
        Gateway::sendToUid($user, json_encode($msg));
    }
    /**
     * 重点人员监控-添加重点人员
     */
    public function addEmphasisInfo($user_name=null,$id_card=null,$face_img=null)
    {
        if(empty($user_name)||empty($id_card)||empty($face_img)){
            return zy_json_echo(false,'请填写完整信息','',-1);
        }
        $is_find=Db::name('statistics_emphasis')->where('id_card',$id_card)->find();
        if(!empty($is_find)){
            return zy_json_echo(false,'该身份证已存在','',-1);
        }
        $in=Db::name('statistics_emphasis')
            ->insert([
                'user_name'=>$user_name,
                'id_card'=>$id_card,
                'face_img'=>$face_img,
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
        $where=[];
        $paginate=empty($param['paginate'])?10:$param['paginate'];
        $page=empty($param['page'])?1:$param['page'];
        if(!empty($param['user_name'])){
            $where['user_name']=['like','%'.trim($param['user_name']).'%'];
        }
        if(!empty($param['id_card'])){
            $where['id_card']=['like','%'.trim($param['id_card']).'%'];
        }
        if(!empty($param['start_time'])&&!empty($param['end_time'])){
            $where['create_time'] = ['between time',[$param['start_time'],$param['end_time']]];
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
        $where=[];
        if(!empty($param['indexCode'])){
            $where['e.indexCode']=['eq',$param['indexCode']];
        }
        if(!empty($param['dir_id'])){
            $where['d.id']=['eq',$param['dir_id']];
        }
        if(!empty($param['start_time'])&&!empty($param['end_time'])){
            $where['faceTime'] = ['between time',[$param['start_time'],$param['end_time']]];
        }
        $list=Db::name('statistics_emphasis')->alias('a')
            ->join('statistics_face_emphasis b','a.infoCode=b.faceInfoCode','left')
            ->join('statistics_cameras c','b.indexCode=c.encodeDevIndexCode','left')
            ->join('statistics_dir d','c.dir_id=d.id','left')
            ->join('statistics_regions e','d.cameraIndexCode=e.indexCode','left')
            ->where($where)
            ->where('b.faceUrl','not null')
            ->field('a.id,a.user_name,a.id_card,a.face_img,b.faceUrl,b.faceTime,d.dirName,e.name')
            ->order('id desc')
            ->paginate($paginate,false,['page'=>$page])
            ->each(function($item, $key){
                $item['faceTime']=date("Y-m-d h:i:s",$item['faceTime']);
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
            $result['top_chart'][$k][1]=$month_health['nor_ratio'];
            //上月健康证未过期比例
            $last_month_health=base::last_month_health($v['id']);
            $result['dir'][$k]['last_ratio']=$last_month_health['last_ratio'];
            //当月人脸抓拍
            $month_face=base::month_face($v['id']);
            $result['dir'][$k]['face_num']= $month_face['face_num'];
            $result['dir'][$k]['dis_pro']= $month_face['dis_pro'];
            $result['top_chart'][$k][2]=$month_face['face_num'];
            $result['top_chart'][$k][3]=$month_face['dis_pro'];
            $result['top_chart'][$k][4]=$v['id'];
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
    public function getSchoolHealth()
    {
        $dir_id=input('dir_id');
        if(empty($dir_id)){
            return zy_json_echo(false,'参数错误','',-1);
        }
        $find=Db::name('statistics_dir')->where('id', $dir_id)->find();
        //当月健康证未过期比例
        $arr[0][0]=$find['dirName'];
        $month_health=base::month_health($dir_id);
        $arr[0][1]=$month_health['nor_ratio'];
        //上月健康证未过期比例
        $last_month_health=base::last_month_health($dir_id);
        $arr[0][2]=$last_month_health['last_ratio'];
        //当月人脸抓拍
        $month_face=base::month_face($dir_id);
        $arr[1][0]=$find['dirName'];
        $arr[1][1]= $month_face['face_num'];
        $arr[2][0]=$find['dirName'];
        $arr[2][1]= $month_face['dis_pro'];
        //上月人脸抓拍
        $last_month_face=base::last_month_face($dir_id);
        $arr[1][2]=$last_month_face['last_face_num'];
        $arr[2][2]= $last_month_face['last_dis_pro'];
        return zy_json_echo(true,'获取成功',$arr,200);
    }
    /**
     * 数据对比-区域上月当月信息表格
     */
    public function getRegionsHealth()
    {
        $param=input('');
        if(empty($param['indexCode'])){
            $param['indexCode']='1bc31038-779f-44fd-bc0d-995a9dab0636';//默认杜桥
        }
        $cameras=Db::name('statistics_regions')->where('indexCode',$param['indexCode'])->find();
        $dir=Db::name('statistics_dir')->where('cameraIndexCode', $param['indexCode'])->select();
        $arr[0][0]=$cameras['name'];
        $arr[0][1]=0;
        $arr[0][2]=0;
        $arr[1][0]=$cameras['name'];
        $arr[1][1]=0;
        $arr[1][2]=0;
        $arr[2][0]=$cameras['name'];
        $arr[2][1]=0;
        $arr[2][2]=0;
        foreach ($dir as $k=>$v){
            //当月健康证未过期比例
            $month_health=base::month_health($v['id']);
            $arr[0][1]+=$month_health['nor_ratio'];
            //上月健康证未过期比例
            $last_month_health=base::last_month_health($v['id']);
            $arr[0][2]+=$last_month_health['last_ratio'];
            //当月人脸抓拍
            $month_face=base::month_face($v['id']);
            $arr[1][1]+= $month_face['face_num'];
            $arr[2][1]+= $month_face['dis_pro'];
            //上月人脸抓拍
            $last_month_face=base::last_month_face($v['id']);
            $arr[1][2]+= $last_month_face['last_face_num'];
            $arr[2][2]+= $last_month_face['last_dis_pro'];
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
            foreach ($dir as $key=>$val){
                if($v['indexCode']==$val['cameraIndexCode']){
                    //当月健康证
                    $month_health=base::month_health($v['id']);
                    $arr[$k][1]+=$month_health['nor_ratio'];
                    //当月人脸抓拍
                    $month_face=base::month_face($v['id']);
                    $arr[$k][2]+= $month_face['face_num'];
                    $arr[$k][3]+= $month_face['dis_pro'];
                }
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
            $where .= " and indexCode >= '".$param['indexCode']."'";
        }
        if(!empty($param['dir_ide'])){
            $where .= " and b.id >= '".$param['dir_id']."'";
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
            ->paginate($paginate,false,['page'=>$page])
            ->each(function($item, $key){
                $item['addtime']=date('Y-m-d h:i:s',$item['addtime']);
                $face=$item['face_thumb'];
                $card=$item['health_card'];
                unset($item['face_thumb']);
                unset($item['health_card']);
                $item['face_thumb'][0]=$face;
                $item['health_card'][0]=$card;
                $is_over = 0;
                $zero1=strtotime (date("y-m-d")); //当前时间
                $zero2=strtotime ($item['health_endtime']);  //健康证时间
                $cut_time = $zero2-$zero1;
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
            ->paginate($paginate,false,['page'=>$page])
            ->each(function($item, $key) {
                $item['enclosure']= explode(",",$item['enclosure']);
                $item['time']=date('Y-m-d h:i:s',$item['time']);
                return $item;
            });

        return zy_json_echo(true,'获取成功',$list,200);
    }
    /**
     * 首页数据大屏
     */
    public function indexStat()
    {
        $regions=Db::name('statistics_regions')->where('abbr','not null')->where('parentIndexCode','root000000')->select();
        //数据概况
        $config=$this->config;
        $list['general']['hq_name']=$config['hq_name'];
        $list['general']['rank']=$config['rank'];
        $list['general']['regions']=$config['regions'];
        $list['general']['school']=$config['school'];
        $list['general']['no_school']=$config['no_school'];
        $list['general']['yes_school']=$config['yes_school'];
        $list['general']['kitchen']=$config['kitchen'];
        $list['general']['damp']=$config['damp'];
        $list['general']['food_admin']=$config['food_admin'];
        $list['general']['kitchen']=$config['kitchen'];
        $list['general']['damp_admin']=$config['damp_admin'];
        //健康证状况
        $dir=Db::name('statistics_dir')->select();
        $arr=[];
        foreach ($regions as $k=>$v){
            $arr[0][$k]=$v['name'];
            $nor=0;
            $ano=0;
            foreach ($dir as $key=>$val){
                if($v['indexCode']==$val['cameraIndexCode']){
                    $nor+=Db::name('member_info')->where('school_id',$val['id'])->where('health_endtime','> time',date('Y-m-d',time()))->count();
                    $ano+=Db::name('member_info')->where('school_id',$val['id'])->where('health_endtime','<= time',date('Y-m-d',time()))->count();
                }
            }
            $arr[1][$k]=$nor;
            $arr[2][$k]=$ano;
        }
        $list['area_health']=$arr;
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
        //健康证表格
        $list['area_chart']['all']=Db::name('member_info')->count();
        $list['area_chart']['normal']=Db::name('member_info')->where('health_endtime','> time',date('Y-m-d',time()))->count();
//        halt($list['area_chart']['normal']);
        $list['area_chart']['anomaly']=$list['area_chart']['all']-$list['area_chart']['normal'];
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
    public function indexStatSchool()
    {
        $param=input('');
        if(empty($param['indexCode'])){
            $param['indexCode']='1bc31038-779f-44fd-bc0d-995a9dab0636';//默认杜桥
        }
        $dir=Db::name('statistics_dir')->where('cameraIndexCode', $param['indexCode'])->select();
        $arr=[];
        foreach ($dir as $k=>$v){
            $arr[$k][0]=$v['dirName'];
            $stranger=Db::name('statistics_dir_stat')->where('month_section',date('Y-m', time()))->where('dir_id',$v['id'])->value('stranger_face_num');
            $emphasis=Db::name('statistics_dir_stat')->where('month_section',date('Y-m', time()))->where('dir_id',$v['id'])->value('emphasis_face_num');
            $arr[$k][1]=empty($stranger)?0:$stranger;
            $arr[$k][2]=empty($emphasis)?0:$emphasis;
        }
        return zy_json_echo(true,'获取成功',$arr,200);
    }
    /**
     *  首页数据大屏-学校违规信息
     */
    public function schoolViolation()
    {
        $count=Db::query('SELECT school_id,COUNT(school_id) AS num FROM cmf_report_school GROUP BY school_id');
        $arr=[];
        foreach ($count as $k=>$v){
            $arr[$k]=Db::name('report_school')
                ->where('school_id',$v['school_id'])
                ->field('id,school_id,name,violation')
                ->find();
            $arr[$k]['code']=1;
            if($arr[$k]['violation']>5){
                $arr[$k]['code']=2;
            }else if($arr[$k]['violation']>15){
                $arr[$k]['code']=3;
            }
            $arr[$k]['num']=$v['num'];
        }
        return zy_json_echo(true,'获取成功',$arr,200);
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
    public function test()
    {
        $num=date('Y-m-t', strtotime('-1 month'));
        halt($num);
    }
    /**
     * 导出excel
     */
    public function out_excel(){
        dump('该方法已删除');
    }
    /**
     * 绑定uid并分组
     */
    public function bindUser($uid=null,$client_id=null)
    {
        if(empty($uid)||empty($client_id)){
            return zy_json_echo(true,'参数错误',[],200);
        }
        $user=Db::name('user')->where('id',$uid)->find();
        Gateway::bindUid($client_id, $uid);//绑定uid
        Gateway::joinGroup($client_id, $user['user_type']);//绑定分组
        return zy_json_echo(true,'绑定成功',[],200);
    }
    public function sendMsg($group=1,$msg='123')
    {
        Gateway::sendToGroup($group, $msg);
    }
}