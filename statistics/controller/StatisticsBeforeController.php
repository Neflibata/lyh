<?php
namespace plugins\statistics\controller; //Demo插件英文名，改成你的插件英文就行了
use cmf\controller\PluginBaseController;
use think\Db;
use plugins\statistics\controller\Haikang as haikang;


class StatisticsBeforeController extends PluginBaseController
{
    public $video_url = "/artemis//api/video/v1/cameras/previewURLs";//搜索分组

    public $all_tree = "/artemis/api/resource/v1/regions";//所有区域树
    public $sub_regions ="/artemis//api/resource/v1/regions/subRegions";//区域树下级
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
        $result = $hk->doCurl($postData, $hk->api_search_url);
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
     * 获取内容
     */
    public function getHik($postData,$url)
    {
        $hk=new Haikang();
        $result = $hk->doCurl($postData, $url);
        return json_decode($result,true);
    }
}