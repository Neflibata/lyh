<?php
namespace plugins\statistics\controller; //Demo插件英文名，改成你的插件英文就行了
use cmf\controller\PluginBaseController;
use think\Db;
use plugins\statistics\controller\Haikang as haikang;
use GatewayClient\Gateway;

class StatisticsBeforeController extends PluginBaseController
{
    public $video_url = "/artemis//api/video/v1/cameras/previewURLs";//搜索分组

    public $all_tree = "/artemis/api/resource/v1/regions";//所有区域树
    public $sub_regions ="/artemis/api/resource/v1/regions/subRegions";//区域树下级
    function index()
    {

        return $this->fetch("foreHtml/index");
    }
    function from()
    {
//      $schoolInfo = getModuleConfig("statistics", "config", "schoolInfo.json");
        $schoolInfo=Db::name('statistics_dir')->where('dir_abbr','not null')->field('dirName as company')->order('id asc')->column('dirName');
//        halt(json_encode($schoolInfo));
        $this->uploadPath = ZY_APP_PATH."uploadFile/";
        $this->assign("uploadPath",  $this->uploadPath);
        $this->assign("schoolInfo",  json_encode($schoolInfo));
        return $this->fetch("foreHtml/from");
    }
    function change_to_quotes($str) {
        return sprintf("'%s'", $str);
    }
    function cs()
    {
        $s = new haikang();
        $d = $s->get_person_list(["pageNo"=>1]);
        echo $d;
    }

    /**
     * 监控资源信息
     */
    public function cs_1()
    {
        $page=input('page');
        if(empty($page)){
            $page=1;
        }
        $postData = [
            "pageNo"=> $page,
            "pageSize"=> 1000,
            "treeCode"=> "0"
        ];

        $hk=new Haikang();
        $result = $hk->doCurl($postData, $hk->resource_cameras);
        $arr=json_decode($result,true);
halt($arr);
        $index=0;
//        for ($i=486;$i<1000;$i++){
//            $v=$arr['data']['list'][$i];
//            Db::name('statistics_cameras')->insert([
//                    "altitude"=>$v["altitude"],
//                    "cameraIndexCode"=>$v["cameraIndexCode"],
//                    "cameraName"=>$v["cameraName"],
//                    "cameraType"=>$v["cameraType"],
//                    "cameraTypeName"=>$v["cameraTypeName"],
//                    "capabilitySet"=>$v["capabilitySet"],
//                    "capabilitySetName"=>$v["capabilitySetName"],
//                    "intelligentSet"=>$v["intelligentSet"],
//                    "intelligentSetName"=>$v["intelligentSetName"],
//                    "channelNo"=>$v["channelNo"],
//                    "channelType"=>$v["channelType"],
//                    "channelTypeName"=>$v["channelTypeName"],
//                    "createTime"=>$v["createTime"],
//                    "encodeDevIndexCode"=>$v["encodeDevIndexCode"],
//                    "encodeDevResourceType"=>$v["encodeDevResourceType"],
//                    "encodeDevResourceTypeName"=>$v["encodeDevResourceTypeName"],
//                    "gbIndexCode"=>$v["gbIndexCode"],
//                    "installLocation"=>$v["installLocation"],
//                    "keyBoardCode"=>$v["keyBoardCode"],
//                    "latitude"=>$v["latitude"],
//                    "longitude"=>$v["longitude"],
//                    "pixel"=>$v["pixel"],
//                    "ptz"=>$v["ptz"],
//                    "ptzName"=>$v["ptzName"],
//                    "ptzController"=>$v["ptzController"],
//                    "ptzControllerName"=>$v["ptzControllerName"],
//                    "recordLocation"=>$v["recordLocation"],
//                    "recordLocationName"=>$v["recordLocationName"],
//                    "regionIndexCode"=>$v["regionIndexCode"],
//                    "status"=>$v["status"],
//                    "statusName"=>$v["statusName"],
//                    "transType"=>$v["transType"],
//                    "transTypeName"=>$v["transTypeName"],
//                    "treatyType"=>$v["treatyType"],
//                    "treatyTypeName"=>$v["treatyTypeName"],
//                    "viewshed"=>$v["viewshed"],
//                    "updateTime"=>$v["updateTime"]
//                ]);
//        }
//        die;
        foreach ($arr['data']['list'] as $k=>$v){
//            $is_exis=Db::name('statistics_cameras')->where("cameraIndexCode",$v['cameraIndexCode'])->find();
//
//            if(empty($is_exis)){
//                dump($index.".".$v['cameraName']);
//                $index++;
//            }
//            if(strstr($v['cameraName'], "阳光厨房")!= false){
//                Db::name('statistics_cameras')->insert([
//                    "altitude"=>$v["altitude"],
//                    "cameraIndexCode"=>$v["cameraIndexCode"],
//                    "cameraName"=>$v["cameraName"],
//                    "cameraType"=>$v["cameraType"],
//                    "cameraTypeName"=>$v["cameraTypeName"],
//                    "capabilitySet"=>$v["capabilitySet"],
//                    "capabilitySetName"=>$v["capabilitySetName"],
//                    "intelligentSet"=>$v["intelligentSet"],
//                    "intelligentSetName"=>$v["intelligentSetName"],
//                    "channelNo"=>$v["channelNo"],
//                    "channelType"=>$v["channelType"],
//                    "channelTypeName"=>$v["channelTypeName"],
//                    "createTime"=>$v["createTime"],
//                    "encodeDevIndexCode"=>$v["encodeDevIndexCode"],
//                    "encodeDevResourceType"=>$v["encodeDevResourceType"],
//                    "encodeDevResourceTypeName"=>$v["encodeDevResourceTypeName"],
//                    "gbIndexCode"=>$v["gbIndexCode"],
//                    "installLocation"=>$v["installLocation"],
//                    "keyBoardCode"=>$v["keyBoardCode"],
//                    "latitude"=>$v["latitude"],
//                    "longitude"=>$v["longitude"],
//                    "pixel"=>$v["pixel"],
//                    "ptz"=>$v["ptz"],
//                    "ptzName"=>$v["ptzName"],
//                    "ptzController"=>$v["ptzController"],
//                    "ptzControllerName"=>$v["ptzControllerName"],
//                    "recordLocation"=>$v["recordLocation"],
//                    "recordLocationName"=>$v["recordLocationName"],
//                    "regionIndexCode"=>$v["regionIndexCode"],
//                    "status"=>$v["status"],
//                    "statusName"=>$v["statusName"],
//                    "transType"=>$v["transType"],
//                    "transTypeName"=>$v["transTypeName"],
//                    "treatyType"=>$v["treatyType"],
//                    "treatyTypeName"=>$v["treatyTypeName"],
//                    "viewshed"=>$v["viewshed"],
//                    "updateTime"=>$v["updateTime"]
//                ]);
//            }
        }
    }

    /**
     * 获取视频
     */
    public function cs_2()
    {
        $postData = [
            "cameraIndexCode"=> '91b4e4cf3d49478fa86f8d4ee6d64de2',
            "protocol"=> 'hls'
        ];
        $hk=new Haikang();
        $result = $hk->doCurl($postData, $this->video_url);
        $arr=json_decode($result,true);
        halt($arr);
    }
    /**
     * 获取区域树
     */
    public function cs_3()
    {
        $postData = [
            "pageNo" => 1,
            "pageSize" => 200,
            "treeCode" => "0"
        ];
        $arr = $this->getHik($postData, $this->all_tree);
        halt($arr);
//        Db::name('statistics_regions')->insertAll($arr['data']['list']);
    }

    /**
     *获取区域树下级
     */
    public function cs_4()
    {
        $postData = [
            "parentIndexCode" => "73cc5fb7-e3ac-4230-b2b0-60d9bf4337b6",
            "treeCode" => 0
        ];
        $arr = $this->getHik($postData, $this->sub_regions);
        halt($arr);
    }
    /**
     * 查询人脸
     */
    public function cs_5()
    {
        $postData = [
//            "indexCodes"=> [
//                "eefca283-a78d-4a9b-8d1c-e5f91f3e960e"
//            ]
        ];
        $hk=new Haikang();
        $arr = $this->getHik($postData, $hk->api_face);
        halt($arr);
    }
    /**
     * 查询人脸【第二种】
     */
    public function cs_6()
    {
        $src="https://111.3.64.34:446/pic?2dd802ifb-e*2e64782f9f4a--5fc610f2fcad8i3b1*=*d8d0i*s1d=i0p1t=pe*m5i19=1-861b0584z34bs=3ib3=&AccessKeyId=3kdKixyL3e3UcCiv&Expires=1583552086&Signature=nC8BZ35AxozvyWdM87jLgbdRDwI=";
        $base_url=base64_encode($src);
        //另一种访问方式 https://111.3.64.34:446/ngx/proxy?i=aHR0cHM6Ly8xMTEuMy42NC4zNDo0NDYvcGljPzJkZDgwMmlmYi1lKjJlNjQ3ODJmOWY0YS0tNWZjNjEwZjJmY2FkOGkzYjEqPSpkOGQwaSpzMWQ9aTBwMXQ9cGUqbTVpMTk9MS04NjFiMDU4NHozNGJzPTNpYjM9JkFjY2Vzc0tleUlkPTNrZEtpeHlMM2UzVWNDaXYmRXhwaXJlcz0xNTgzNTUyMDg2JlNpZ25hdHVyZT1uQzhCWjM1QXhvenZ5V2RNODdqTGdiZFJEd0k9
        halt($base_url);
    }
    /**
     * 查询根区域
     */
    public function cs_7()
    {
        $postData = [
            "pageNo"=> 1,
            "pageSize"=> 500,
            "treeCode"=> "0"
        ];
        $hk=new Haikang();
        $arr = $hk->doCurl($postData, $hk->regions_root);
//        halt($arr);
        return zy_json_echo(true,'获取成功',$arr,200);
    }
    /**
     * 订阅事件
     */
    public function cs_8()
    {
        $hk=new Haikang();
        $url="http://js2.300c.cn/lhyd/public";
        $postData = [
            "eventTypes"=> [851969],
	        "eventDest"=> "/plugin/statistics/api_index/addGps"
        ];
        $arr = $hk->doCurl($postData, $hk->event_byeventtypes);
        halt($arr);
    }
    /**
     * 查看已订阅事件
     */
    public function cs_9()
    {
        $hk=new Haikang();
        $arr = $hk->doCurl([], $hk->event_view);
        halt($arr);
    }
    /**
     * 取消订阅事件
     */
    public function cs_10()
    {
        $postData = [
            "eventTypes"=> [131614,131659,1644175361,1644171265]
        ];
        $hk=new Haikang();
        $arr = $hk->doCurl($postData, $hk->event_un_byeventtypes);
        halt($arr);
    }
    /**
     * 添加人脸分组
     */
    public function cs_11()
    {
        $postData = [
            "name"=> '重点人员',
            "description"=>'描述'
        ];
        $hk=new Haikang();
        $arr = json_decode($hk->doCurl($postData, $hk->single_addition),true);
        if($arr['code']==0){
            Db::name('statistics_face_group')->insert([
                'indexCode'=>$arr['data']['indexCode'],
                'name'=>$arr['data']['name'],
                'description'=>$arr['data']['description'],
                'create_time'=>time()
            ]);
            return zy_json_echo(true,'添加分组成功',$arr,200);
        }else{
            return zy_json_echo(false,$arr['msg'],[],-1);
        }
    }
    /**
     * 查询人脸分组
     */
    public function cs_12()
    {
        $postData = [
            "indexCodes"=> [],
            "name"=>"重点"
        ];
        $hk=new Haikang();
        $arr = $hk->doCurl($postData, $hk->face_group);
        halt($arr);
    }
    /**
     * 识别资源
     */
    public function cs_13()
    {
        $postData = [
            "indexCodes"=> [],
            "name"=>"",
            "recognitionResourceType"=>""
        ];
        $hk=new Haikang();
        $arr = $hk->doCurl($postData, $hk->resource_recognition);
        halt($arr);
    }
    /**
     * 添加计划
     */
    public function cs_14()
    {
        $postData = [
            "name"=> "重点人员测试计划",
            "faceGroupIndexCodes"=>["f77b25c4-8a25-4d78-91a1-700320320449","5fd42a66-8e55-46fc-9f36-1ffb4f80558f"],//人脸分组
            "recognitionResourceType"=> "FACE_RECOGNITION_SERVER",//资源类型
            "recognitionResourceIndexCodes"=>["4e7aa02c-9aac-4e2e-a8b9-e1b49d8800cd"],//识别资源
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
    public function cs_15()
    {
        $postData = [
            "resourceType"=> "camera",
            "resourceIndexCode"=>"86f0d4c232fc4807af9f7b98490a5891"//人脸分组
        ];
        $hk=new Haikang();
        $result = $hk->doCurl($postData, $hk->new);
        $arr=json_decode($result,true);
        halt($arr);
    }
    /**
     * 测试用-接受到陌生人脸
     */
    public function cs_16()
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
                                    'indexCode' => 'f20cfe9c0fed4d65a7289cee435356aa',
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
        $data = $param['params']['events'];
        foreach ($data as $k => $v) {
            $cameras = Db::name('statistics_cameras')->where('encodeDevIndexCode', $v['data']['resInfo'][0]['indexCode'])->find();
            $dir_id = empty($cameras) ? 0 : $cameras['dir_id'];
            $id = Db::name('statistics_face_stranger')->insertGetId([
                'ageGroup' => $v['data']['faceRecognitionResult']['snap']['ageGroup'],
                'gender' => $v['data']['faceRecognitionResult']['snap']['gender'],
                'glass' => $v['data']['faceRecognitionResult']['snap']['glass'],
                'bkgUrl' => $this->config['base_img'] . base64_encode($v['data']['faceRecognitionResult']['snap']['bkgUrl']),
                'faceUrl' => $this->config['base_img'] . base64_encode($v['data']['faceRecognitionResult']['snap']['faceUrl']),
                'faceTime' => $v['data']['faceRecognitionResult']['snap']['faceTime'],
                'resourceType' => $v['data']['resInfo'][0]['resourceType'],
                'indexCode' => $v['data']['resInfo'][0]['indexCode'],
                'cn' => $v['data']['resInfo'][0]['cn'],
                'srcIndex' => $v['srcIndex'],
                'originalData' => serialize($v['data']),
                'dir_id' => $dir_id
            ]);
            $find = Db::name('statistics_face_stranger')->where('id', $id)->find();
            $find['dirName'] = Db::name('statistics_dir')->where('id', $dir_id)->value('dirName');
            //添加到统计表
            $stat = Db::name('statistics_dir_stat')->where('dir_id', $dir_id)->where('month_section', date('Y-m', time()))->find();
            if (empty($stat)) {
                Db::name('statistics_dir_stat')->insert([
                    'dir_id' => $dir_id,
                    'cameraIndexCode' => $cameras['regionIndexCode'],
                    'month_section' => date('Y-m', time()),
                    'stranger_face_num' => 1
                ]);
            } else {
                Db::name('statistics_dir_stat')
                    ->where('dir_id', $dir_id)
                    ->where('month_section', date('Y-m', time()))
                    ->update(['stranger_face_num' => $stat['stranger_face_num'] + 1]);
            }
            $module_info = getModuleConfig('statistics','config','config.json');
            $module_info = json_decode($module_info,true);
            $find['ageGroup']=$module_info['age'][$find['ageGroup']];
            $find['glass']=$module_info['glasses'][$find['glass']];
            $find['gender']=$module_info['sex'][$find['gender']];
            //提醒警报
            $msg = json_encode([
                'type' => 'stranger',
                'describe' => '发现陌生人',
                'content' => $find
            ]);
            $user = Db::name('user')->where('user_type', 2)->where('find_in_set(' . $dir_id . ',school)')->column('id');
            Gateway::sendToGroup([1], $msg);
            Gateway::sendToUid($user, $msg);
        }
    }
    /**
     * 测试用-接收到重点人员人脸
     */
    public function cs_17()
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
                                        'faceGroupCode' => '5fd42a66-8e55-46fc-9f36-1ffb4f80558f',
                                        'faceGroupName' => '重点人员',
                                        'faceInfoCode' => '5fd42a66-8e55-46fc-9f36-1ffb4f80558f',
                                        'faceInfoName' => '秦华',
                                        'faceInfoSex' => 'male',
                                        'facePicUrl' => 'https://111.3.64.34:446/ngx/proxy?i=aHR0cDovLzEwLjIyLjExMy44NTo2MDQwL3BpYz82ZGQ4Nj1zZmItejA2ZTY0ODY4ZjlmMWEtLTVmYzYxMGYyZmNhZDhpM2IxKj0qZDlkMmkqczFkPWkxcDFwPSptOGkxdD02ZTEtNTM0YjA1MTZpMzRiKmU1aWIzPQ==',
                                        'similarity' => 0.03,
                                    ],
                                ],
                                'snap' => [
                                    'ageGroup' => 'young',
                                    'bkgUrl' => 'https://111.3.64.34:446/ngx/proxy?i=aHR0cDovLzEwLjIyLjExMy44NTo2MDQwL3BpYz82ZGQ4Nj1zZmItejA2ZTY0ODY4ZjlmMWEtLTVmYzYxMGYyZmNhZDhpM2IxKj0qZDlkMmkqczFkPWkxcDFwPSptOGkxdD02ZTEtNTM0YjA1MTZpMzRiKmU1aWIzPQ==',
                                    'faceTime' => '2019-05-27 14:17:56',
                                    'faceUrl' => 'https://111.3.64.34:446/ngx/proxy?i=aHR0cDovLzEwLjIyLjExMy44NTo2MDQwL3BpYz82ZGQ4Nj1zZmItejA2ZTY0ODY4ZjlmMWEtLTVmYzYxMGYyZmNhZDhpM2IxKj0qZDlkMmkqczFkPWkxcDFwPSptOGkxdD02ZTEtNTM0YjA1MTZpMzRiKmU1aWIzPQ==',
                                    'gender' => 'female',
                                    'glass' => 'yes',
                                ],
                                'srcEventId' => '2f81128d2dbe4155ac7253c23510035f',
                            ],
                            'resInfo' => [
                                [
                                    'cn' => '184.156_6024_抓拍',
                                    'indexCode' => '2f81128d2dbe4155ac7253c23510035f',
                                    'resourceType' => 'camera',
                                ],
                            ],
                            'srcEventId' => '2f81128d2dbe4155ac7253c23510035f',
                        ],
                        'eventId' => 'e91d79f5-543c-4e9f-8c4a-82a113e8d384',
                        'eventType' => 1644175361,
                        'happenTime' => '2020-04-06T10:17:56.000+08:00',
                        'srcIndex' => '6a4f52d1024f4b5ea391452e4c07d05c',
                        'srcName' => '重点人员',
                        'srcType' => 'facegroup',
                        'status' => 0,
                        'timeout' => 0,
                    ],
                ],
                'sendTime' => '2019-05-27T14:17:50.747+08:00',
            ],
        ];
        $data = $param['params']['events'];
        foreach ($data as $k=>$v){
            $cameras=Db::name('statistics_cameras')->where('encodeDevIndexCode',$v['data']['resInfo'][0]['indexCode'])->find();
            $dir_id=empty($cameras)?0:$cameras['dir_id'];
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
                                    'indexCode' => 'f20cfe9c0fed4d65a7289cee435356aa',
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
        $param=input('');
        file_put_contents('C:\WWW\js2\lhyd\public\plugins\statistics\log\stranger.txt', serialize($param), FILE_APPEND);
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
            //提醒警报
            $msg=json_encode([
                'type'=>'stranger',
                'describe'=>'发现陌生人',
                'content'=> $find
            ]);
            $user=Db::name('user')->where('user_type',2)->where('find_in_set('.$dir_id.',school)')->column('id');
            Gateway::sendToGroup([1],$msg);
            Gateway::sendToUid($user, $msg);
//            Gateway::sendToAll($msg);
        }
    }

    /**
     * 查询监控点在线状态
     */
    public function cs_18()
    {
        $postData = [
            "rregionId"=> "1bc31038-779f-44fd-bc0d-995a9dab0636",
            "includeSubNode"=>"1",
            "indexCodes"=> [],
            "status"=>"1",
            "pageNo"=>1,
            "pageSize"=>200
        ];
        $hk=new Haikang();
        $result = $hk->doCurl($postData, $hk->camera_get);
        $arr=json_decode($result,true);
        halt($arr);
    }

    public function cs_19()
    {
        $page=input('page');
        if(empty($page)){
            $page=1;
        }
        $postData = [
            "pageNo"=> $page,
            "pageSize"=> 500,
            "treeCode"=> "0"
        ];
        $hk=new Haikang();
        $result = $hk->doCurl($postData, $hk->resource_cameras);
        $arr=json_decode($result,true);
        halt($arr);
    }
    public function cs_20()
    {
        $arr=[];
//        $arr[0]['title']="辖区名称：";
//        $arr[0]['value']="临海市";
//        $arr[1]['title']="街道、乡镇：";
//        $arr[1]['value']=Db::name('statistics_regions')->where('abbr','not null')->where('parentIndexCode','root000000')->count();
//        $arr[2]['title']="学校总数：";
//        $arr[2]['value']=Db::name('statistics_dir')->where('cameraIndexCode','not null')->count();
//        $arr[3]['title']="超市总数：";
//        $arr[3]['value']=Db::name('statistics_dir')->where('cameraIndexCode','not null')->count();
//        $arr[4]['title']="在职后厨：";
//        $arr[4]['value']=19;
//        $arr[5]['title']="食品安全管理员：";
//        $arr[5]['value']=19;
//        $arr[6]['title']="就餐人数：";
//        $arr[6]['value']=19;
    }

    /**
     * 更新图片地址
     */
    public function cs_22()
    {
        $str="http://111.3.64.34:81/ngx/proxy?i=";
        $new_str="http://10.22.113.85:81/ngx/proxy?i=";
        $list=Db::name("statistics_face_emphasis")->select();
        foreach ($list as $k=>$v){
            $v['faceUrl']=str_replace($str, $new_str, $v['faceUrl']);
            $v['bkgUrl']=str_replace($str, $new_str, $v['bkgUrl']);
            Db::name("statistics_face_emphasis")->where("id",$v['id'])->update(["faceUrl"=>$v['faceUrl'],'bkgUrl'=>$v['bkgUrl']]);
        }
        $lista=Db::name("statistics_face_stranger")->select();
        foreach ($lista as $k=>$v){
            $v['faceUrl']=str_replace($str, $new_str, $v['faceUrl']);
            $v['bkgUrl']=str_replace($str, $new_str, $v['bkgUrl']);
            Db::name("statistics_face_stranger")->where("id",$v['id'])->update(["faceUrl"=>$v['faceUrl'],'bkgUrl'=>$v['bkgUrl']]);
        }
        echo "replace succeed";
    }
//    /**
//     * 所有在线率
//     */
//    public function cs_22()
//    {
//        $page=input('page');
//        if(empty($page)){
//            $page=1;
//        }
//        $postData = [
//            "regionId"=>"root000000",
//            "includeSubNode"=>1,
//            "pageNo"=> $page,
//            "pageSize"=> 2000,
//            "treeCode"=> "0",
//            "resourceType"=> "camera"
//        ];
//        $hk=new Haikang();
//        $result = $hk->doCurl($postData, $hk->online_get);
//        $arr=json_decode($result,true);
//        $on=0;
//        $off=0;
//        if(isset($arr['data']['list'])){
//            foreach ($arr['data']['list'] as $k=>$v){
//                if($v['online']==1){
//                    $on+=1;
//                }else{
//                    $off+=1;
//                }
//            }
//        }
//        dump("在线：".$on);
//        dump("离线：".$off);
//        halt($arr);
//    }

    /**
     * 某个区域是否在线
     */
    public function cs_23()
    {
        $page=input('page');
        if(empty($page)){
            $page=1;
        }
        $postData = [
            "indexCodes"=>["41f25dc7e03f42a3a182d2afa7bfac99"],
            "pageNo"=> $page,
            "pageSize"=> 2000,
            "treeCode"=> "0",
            "resourceType"=> "camera"
        ];
        $hk=new Haikang();
        $result = $hk->doCurl($postData, $hk->online_get);
        $arr=json_decode($result,true);
        halt($arr);
    }
    /**
     * 更新学校的区域标识
     */
    public function cs_24()
    {
        $list=Db::name("statistics_dir")->select();
        foreach ($list as $k=>$v){
            if(!empty($v['cameraIndexCode'])){
                $find=Db::name("statistics_regions")->where("parentIndexCode",$v['cameraIndexCode'])->where("name","学校")->find();
                Db::name("statistics_dir")->where("id",$v['id'])->update(["cameraIndexCode"=>$find['indexCode']]);
            }
        }
        echo "ok";
    }
    public function cs_25()
    {
        $list=Db::name("statistics_regions")->select();
        foreach ($list as $k=>$v){
            Db::name("statistics_regions")->where('id',$v['id'])->update(["is_show"=>1]);
        }
    }
    //获取学校目录
    public function cs_26()
    {
        $list=Db::name("statistics_regions")->where("parentIndexCode","<>","root000000")->select();
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
            dump($arr);
//            foreach ($arr['data']['list'] as $k=>$v){
//                Db::name("statistics_dir")->insert([
//                    "indexCode"=>$v['indexCode'],
//                    "dirName"=>$v['name'],
//                    "regionIndexCode"=>$v['regionIndexCode'],
//                    "cameraIndexCode"=>$v['regionIndexCode']
//                ]);
//            }
        }
    echo "ok";
//        Db::name('statistics_regions')->insertAll($arr['data']['list']);
    }
    /**
     * 获取内容
     */
    public function getHik($postData,$url)
    {
        $hk=new Haikang();
        $result = $hk->doCurl($postData, $url);
        return json_decode($result,true);
    }
}