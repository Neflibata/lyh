<?php
namespace plugins\statistics\controller;
use cmf\controller\PluginRestBaseController;//引用插件基类
use think\Db;
use think\Request;
use plugins\statistics\model\BaseModel as base;

/**
 * api控制器
 */
class ApiIndexController extends PluginRestBaseController
{
    public $hrefPath;
    public $uploadPath;
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

}