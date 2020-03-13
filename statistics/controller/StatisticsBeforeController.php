<?php
namespace plugins\statistics\controller; //Demo插件英文名，改成你的插件英文就行了
use cmf\controller\PluginBaseController;
use think\Db;
use plugins\statistics\controller\Haikang as haikang;


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
        $schoolInfo = getModuleConfig("statistics", "config", "schoolInfo.json");
        $this->uploadPath = ZY_APP_PATH."uploadFile/";
        $this->assign("uploadPath",  $this->uploadPath);
        $this->assign("schoolInfo",  $schoolInfo);
        return $this->fetch("foreHtml/from");
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
        $postData = [
            "pageNo"=> 1,
            "pageSize"=> 200,
            "treeCode"=> "0"
        ];

        $hk=new Haikang();
        $result = $hk->doCurl($postData, $hk->resource_cameras);
        $arr=json_decode($result,true);
        halt($arr);
        return $arr;
//        Db::name('statistics_cameras')->insertAll($arr['data']['list']);
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
            "parentIndexCode" => "1bc31038-779f-44fd-bc0d-995a9dab0636",
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
            "pageSize"=> 20,
            "treeCode"=> "0"
        ];
        $hk=new Haikang();
        $arr = $hk->doCurl($postData, $hk->regions_root);
        halt($arr);
    }
    /**
     * 订阅事件
     */
    public function cs_8()
    {
        $postData = [
            "eventTypes"=> [851969],
	        "eventDest"=> "http://js2.300c.cn/lhyd/public/plugin/statistics/api_index/addGps"
        ];
        $hk=new Haikang();
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
    /**
     * 读取写入txt
     */
    public function cshj()
    {
        $file_path = "D:\phpstudy\PHPTutorial\WWW\lhyd\public\plugins\statistics\controller\abc.txt";
        $fp = fopen($file_path, 'r');
        $arr = array();//初始化数组
        for ($i = 0; $i < 142; $i++) {
            $arr[] = trim(fgets($fp));
        };
        fclose($fp);
        $file_patha = "D:\phpstudy\PHPTutorial\WWW\lhyd\public\plugins\statistics\controller\b.txt";
        $fpa = fopen($file_patha, 'r');
        $arra = array();//初始化数组
        for ($i = 0; $i < 142; $i++) {
            $a = trim(fgets($fpa));
            $data = explode(",",$a);
            $arra[] = $data;
        };
        fclose($fpa);
        foreach ($arr as $k=>$v){
            Db::name('statistics_dir')->insert([
                'dirName'=>$arr[$k],
                'longitude'=>$arra[$k][0],
                'latitude'=>$arra[$k][1]
            ]);
        }
        dump($arr);
        halt($arra);
    }
    /**
     * 操作表格
     */
    public function upTable()
    {

        $dir=Db::name('statistics_dir')->select();
        foreach ($dir as $k=>$v){
            $where['cameraName']=['like','%'.$v['dirName'].'%'];
            $cameras=Db::name('statistics_cameras')->where($where)->select();
            foreach ($cameras as $ka=>$va){
                Db::name('statistics_dir')->where('id',$v['id'])->update(['cameraIndexCode'=>$va['regionIndexCode']]);
                Db::name('statistics_cameras')->where('id',$va['id'])->update(['dir_id'=>$v['id']]);
            }
        }
        echo 'ok';
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