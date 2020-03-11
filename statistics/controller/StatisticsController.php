<?php


namespace plugins\statistics\controller;
use cmf\controller\PluginAdminBaseController;
use think\Db;
use plugins\statistics\model\BaseModel as base;
use plugins\statistics\model\ModelC as MC;
use plugins\statistics\model\chartModel;
use think\Exception;

//include "/../functions/fuc.php";

class StatisticsController extends PluginAdminBaseController
{
    public $pagesize = 10;
    public $uploadPath;


    /**
     *  首页
     */
    public function index()
    {
        $this->uploadPath = ZY_APP_PATH.'uploadFile/';
        $this->assign("uploadPath",  $this->uploadPath);
        return $this->fetch('/showData/index');
//        return $this->fetch('/demo/index');
    }

    /**
     * 配置页
     */
    public function config()
    {
        return $this->fetch('/demo/test');
    }
//    测试用
    public function lineCeshi()
    {
        $data = json_decode(getModuleConfig("statistics", "config", "chartcard.json"), true);
        $info = [];
        $num = 0;
        if(!empty($data))
        {
            foreach ($data as $key=>$value)
            {
                foreach ($value as $k=>$v)
                {
                    $info[$num] = $v;
                    $item = new MC($key);
                    $info[$num]["num"] = $item->get_one($v["where"], $v["field"])["num"];
                    $num ++;
                }
            }
        }
        saveModuleConfigData("statistics", "config", "chartcard.json", $data);
        zy_json_echo(true, "成功", $info, 200);
    }
//    测试用
    public function barCeshi()
    {
        $data = $this->request->post();
        $neadArg = ["time"=>["true", 1]];
        $info = checkArg($neadArg, $data);
        $configData = getModuleConfig("statistics", "config", "chartbar.php");
        $timeData = getTime($info["time"]);
        $retData = [];
        $item = new MC($configData["table"]);
        foreach ($timeData as $key=>$value)
        {
            $retData[$value["date"]] = $item->get_one([$configData["where"]=>$value["where"]], $configData["field"])["num"];
        }
        zy_json_echo(true, "成功", $retData, 200);
    }
//    测试用
    public function ceshi()
    {
        $data = new MC("cms_member");
        $info = $data->select("1", "*", "2", "userid DESC");
        list($fffa, $count) = MC::moreTableSelect(["cms_member"=>["*"], "cms_1"=>["id1"]], [["userid", "id1"]], "1", getPageLimit(2,$this->pagesize));
        $fff = 1;
        list($page,$pagenums, $pageStart, $pageCount) = getPage(2, $this->pagesize, $count);
        $s = 1;
    }
    /**
     * param $type
     * param $chickTable
     * return array
     * 测试用的
     */
    public function getData($type, $chickTable){
        $data = [];
        $num = 0;
        foreach ($chickTable as $key=>$value)
        {
            $model = new MC($value["tableName"]);
            $field = [];
            switch ($value["field"]["selectType"])
            {
                case "求和": $field[] = "sum(`".$value["field"]["selectField"]."`) as value";break;
                case "数量": $field[] = "count('*') as value";break;
            }
            $data[] = $model->get_one($value["where"], $field);
            $data[$num]["name"] = $value["name"];
            $num ++;
        }
        return $data;
    }



    /**
     * 获取数据库的配置文件（不需要写前缀）
     * []数组格式表示正向取字段
     * ""字符串格式表示逆向取字段
     * 没做成配置，需要手写
     */
    public function getDatabaseConfig(){
        $table = json_decode(getModuleConfig("statistics", "config", "databaseField.json"), true);
        if(empty($table))
            zy_json_echo(false, "请先配置数据库的json文件");
        $data = MC::getTableField($table);
        zy_json_echo(true, "成功", $data, 200);
    }

    /**
     * 测试表格数据
     */
    public function getTestModelData(){
        $data = $this->request->post();
        $neadArg = ["type"=>[true, 0,"请传入图表类型"], "tableConfig"=>[true, 0, "请传入配置"]];
        $info = checkArg($neadArg, $data);
        $chartModel = new chartModel($info["type"], $info["tableConfig"]);
        $i =getTime(7);
        try{
            $modelData = $chartModel->handOut();
            zy_json_echo(true, "成功", $modelData, 200);
        }
        catch (Exception $e)
        {
            zy_json_echo(false, $e->getMessage(), "", -1);
        }
//        $modelData = $this->getData($info["type"], $info["tableConfig"]["chickTable"]);

    }

    /**
     * 获取图标
     */
    public function getIcon()
    {
        $url =CMF_ROOT."public/plugins/statistics/view/public/balabala/iconfont.css";
        $html = file_get_contents($url);
        $icon = [];
        preg_match_all('/.(icon-.*?):before/', $html, $icon);
        zy_json_echo(true, "成功", $icon[1]);
    }

    /**
     * 获取页面的model的配置文件
     */
    public function getStatisticsConfig()
    {
        $data = json_decode(getModuleConfig("statistics", "config", "statisticsconfig.json"));
        $chartConfig = json_decode(getModuleConfig("statistics", "config", "saveChartConfig.json"), true);
        if(empty($chartConfig))
            zy_json_echo(true, "成功", ["data"=>$data], 200);
        else
        {
            foreach ($chartConfig["model"] as $k=>$v)
            {
                foreach ($v["col"] as $key=>$value)
                {
                    $num = 0;
                    foreach ($value["choice"] as $x=>$y)
                    {
                        $chartConfig["model"][$k]["col"][$key]["choice"][$x]["chickModel"]["where"] = (object)array();
                        if($x == $value["beChoice"])
                        {
                            $chartConfig["model"][$k]["col"][$key]["width"] = $value["width"] + 0;
                            $chartModel = new chartModel($value["beChoice"], $y);
                            $retData =  $chartModel->handOut();
                            $chartConfig["model"][$k]["col"][$key]["choice"][$value["beChoice"]]["data"] = $retData["data"];
                            if($x == "chartline")
                                $chartConfig["model"][$k]["col"][$key]["choice"][$value["beChoice"]]["xData"] = $retData["xData"];
                        }
                        $num++;
                    }
                }
            }
            zy_json_echo(false, "成功", ["data"=>$data, "choiceData"=>$chartConfig], 201);
        }

    }

    /**
     * 存新的数据类型的
     */
    public function saveConfig()
    {
        $data = $this->request->post(false);
        $neadArg = ["chartConfig"=>[true,0]];
        $info = checkArg($neadArg, $data);
        $i = 1;
        $chartConfig = array_pop($info);

        foreach ($chartConfig["model"] as $k=>$v)
        {
            foreach ($v["col"] as $key=>$value)
            {
                $num = 0;
               foreach ($value["choice"] as $x=>$y)
               {
                   $chartConfig["model"][$k]["col"][$key]["width"] = $value["width"] + 0;

                   if($x != $value["beChoice"])
                   {
//                       array_splice($chartConfig["model"][$k]["col"][$key]["choice"], $num,1);

                   }
                   else
                   {
                       $chartConfig["model"][$k]["col"][$key]["width"] = $value["width"] + 0;
                       $chartModel = new chartModel($value["beChoice"], $y);
                       try{
                           $chartModel->handOut();
                           $chartConfig["model"][$k]["col"][$key]["choice"][$value["beChoice"]]["data"] = [];
                           if($x == "chartline")
                               $chartConfig["model"][$k]["col"][$key]["choice"][$value["beChoice"]]["xData"] = [];
                       }
                       catch (Exception $e)
                       {
                           zy_json_echo(false, $e->getMessage(), "", -1);
                       }



                   }
                   $num++;
               }
            }
        }
        saveModuleConfigData("statistics", "config", "saveChartConfig.json", $chartConfig);
        zy_json_echo(true, "成功");
    }

    /**
     * 获取新的数据的
     */
    public function showChart()
    {
        $chartConfig = json_decode(getModuleConfig("statistics", "config", "saveChartConfig.json"),true);
        if(empty($chartConfig))
        {
            zy_json_echo(false, "请先配置");
        }
        else{
            $chartConfig["debug"] = false;
            foreach ($chartConfig["model"] as $k=>$v)
            {
                foreach ($v["col"] as $key=>$value)
                {
                    $num = 0;
                    foreach ($value["choice"] as $x=>$y)
                    {
                        if($x != $value["beChoice"])
                            array_splice($chartConfig["model"][$k]["col"][$key]["choice"], $num,1);
                        else
                        {
                            $chartConfig["model"][$k]["col"][$key]["width"] = $value["width"] + 0;
                            $chartModel = new chartModel($value["beChoice"], $y);
                            $retData =  $chartModel->handOut();
                            $chartConfig["model"][$k]["col"][$key]["choice"][$value["beChoice"]]["data"] = $retData["data"];
                            if($x == "chartline")
                                $chartConfig["model"][$k]["col"][$key]["choice"][$value["beChoice"]]["xData"] = $retData["xData"];
                        }
                        $num++;
                    }
                }
            }
            zy_json_echo(true, "成功", $chartConfig);
        }
    }

    public function getMemberInfoData(){
        $data = $this->request->post();
        $neadArg = ["page"=>[false, 1, "default"=>1], "limit"=>[false, 1, "default"=>10]];
        $pageInfo = checkArg($neadArg, $data);
        $neadWhereArg = ["nickname"=>[false, 0, "", "like"], "mobile"=>[false, 0], "company"=>[false, 0, "", "like"], "MIID"=>[false, 1]];
        $where = checkWhere($neadWhereArg, $data);
        if(empty($where))
            $where = 1;
        list($retData, $count) = base::moreTableSelect(["member_info"=>["*"]], [], $where, $pageInfo["page"], $pageInfo["limit"], "addtime DESC");
        list($page,$pageNums, $pageStart, $pageCount) = getPage($pageInfo["page"], $pageInfo["limit"], $count);
        return zy_json_echo(true,'连入成功',["data"=>$retData, "pageNums"=>$pageNums,"pageStart"=>$pageStart,"pageCount"=>$pageCount]);
    }
    public function map()
    {
        return $this->fetch('/demo/admin_index');
    }
}