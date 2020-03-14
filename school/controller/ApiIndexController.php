<?php
namespace plugins\school\controller;
use cmf\controller\PluginRestBaseController;//引用插件基类
use plugins\statistics\controller\Haikang;
use plugins\statistics\model\BaseModel as base;
use think\Db;
use think\Request;

/**
 * api控制器
 */
class ApiIndexController extends PluginRestBaseController
{
    private $APP_PATH="";
    public $hrefPath;
    public $uploadPath;
    protected function _initialize()
    {
        $this->APP_PATH=strstr($this->request->Url(true),$this->request->path(),true);
        $this->uploadPath = ROOT_PATH.'public/uploadFile';
        $this->hrefPath = ZY_APP_PATH."uploadFile/";
    }
    /**
     * 执行构造
     */
    function __construct()
    {
        header("content-type:text/html;charset=utf-8");
        parent::__construct();
    }

    /**
     * 登录
     */
    public function user_login()
    {
        $param = $this->request->param();
        $user_login = $param['user_login'];//账号
        $user_pass = $param['user_pass'];//密码

        $where['user_login'] = $user_login;
        $result = Db::name('user')->where($where)->find();
        if(empty($result)){
            return zy_array(false,'未找到用户名','',300,false);
        }
        if (!cmf_compare_password($user_pass, $result['user_pass'])) {
            return zy_array(false,'密码错误','',300,false);
        }
        $result['last_login_ip']   = get_client_ip(0, true);
        $result['last_login_time'] = time();
        Db::name('user')->update($result);
        $result = Db::name('user')->field('id,user_type,user_login')->where($where)->find();
        return zy_array(true,'查询成功',$result,200,false);
    }


    /**
     * 网格员/学校首页数据
     */
    public function wgy_index(){
        $param = $this->request->param();
        if(empty($param['uid'])){
            return zy_array(false,'请传入用户id(uid)','',300,false);
        }
        $uid = $param['uid'];
        $userInfo = Db::name('user')->where('id='.$uid)->find();
        //根据学校获取健康证列表
        $where = " school_id in (".$userInfo['school'].") ";
        $re = Db::name('member_info')->where($where)->order('MIID','asc')->select();
        //根据健康证查看
        $overCount = 0;//到期数
        $comeOverCount = 0;//快到期数
        $normalCount = 0;//正常数
        foreach ($re as $item) {
            $zero1=strtotime (date("y-m-d")); //当前时间
            $zero2=strtotime ($item['health_endtime']);  //健康证时间
            $cut_time = $zero2-$zero1;
            $day = floor($cut_time/(3600*24));
            if($zero1>$zero2){
                //过期时间
                $overCount += 1;
            }elseif($day<7 || $day==7){
                //快到期时间
                $comeOverCount += 1;
            }else{
                //正常时间
                $normalCount += 1;
            }
        }
        $amount = $comeOverCount+$normalCount;
        //表格
        $table_json = '[{"num": "'.$amount.'","title": "健康证未到期数"},{"num": "'.$comeOverCount.'","title": "7天内到期数"},{"num": "'.$overCount.'","title": "健康证过期数"}]';
        $allCount = json_decode($table_json);
        //饼图
        $chat_json = '[{"value": "'.$amount.'","name": "健康证未到期数"},{"value": "'.$overCount.'","name": "健康证过期数"}]';
        $chart_Count = json_decode($chat_json);

        $res = array(
            "allCount"=>$allCount,
            "chart_Count"=>$chart_Count,
        );

        return zy_array(true,'查询成功',$res,200,false);
    }


    /**
     * 学校列表
     */
    public function index()
    {
        $param = $this->request->param();
        $paginate=empty($param['paginate'])?10:$param['paginate'];
        if(empty($param['user_id'])){
            return zy_array(false,'请传入用户id(user_id)','',300,false);
        }
        $user_id = $param['user_id'];
        $userInfo = Db::name('user')->where('id='.$user_id)->find();
        if($userInfo['user_type']!=3){
            return zy_array(false,'该用户类型不能查看学校列表','',300,false);
        }
        $where = '1 = 1 ';
        if(!empty($param['dirName'])){
            $where .=" and dirName like '%".$param['dirName']."%'";
        }
        if(!empty($param['cameraIndexCode'])){
            $where .= " and cameraIndexCode = '".$param['cameraIndexCode']."'";
        }
        //根据用户id获取学校id
        $where .= " and id in (".$userInfo['school'].") ";
        $re = Db::name('statistics_dir')->where($where)->order('id','asc')->paginate($paginate);
        return zy_array(true,'查询成功',$re,200,false);
    }

    /**
     * 学校详情
     */
    public function school_detail()
    {
        $param = $this->request->param();

        $uid = $param['uid'];
        if(empty($uid)){
            return zy_array(false,'请传入uid','',300,false);
        }
        $userInfo = Db::name('user')->where('id='.$uid)->find();
        if(empty($userInfo)){
            return zy_array(false,'无人员信息','',300,false);
        }
        $id = $userInfo['school'];
        if(empty($id)){
            return zy_array(false,'无学校信息','',300,false);
        }
        $schoolInfo = Db::name('statistics_dir')->where('id='.$id)->find();
        if(empty($schoolInfo)){
            return zy_array(false,'无学校信息','',300,false);
        }
        return zy_array(true,'查询成功',$schoolInfo,200,false);
    }

    /**
     * 学校编辑
     */
    public function school_edit()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $company = $param['company'];
        $personCharge = $param['personCharge'];
        $personChargePhone = $param['personChargePhone'];
        $street = $param['street'];
        $meals = $param['meals'];
        if(empty($param['user_id'])){
            return zy_array(false,'请传入用户id(user_id)','',300,false);
        }
        $user_id = $param['user_id'];
        $userInfo = Db::name('user')->where('id='.$user_id)->find();
        $schoolArr = explode(",",$userInfo['school']);
        if(!in_array($id,$schoolArr)){
            return zy_array(false,'该用户无权限修改学校','',300,false);
        }
        if(empty($id)){
            return zy_array(false,'id不能为空','',300,false);
        }
        if(!empty($company)){
            $add['company'] = $company;
        }
        if(!empty($personCharge)){
            $add['personCharge'] = $personCharge;
        }
        if(!empty($meals)){
            $add['meals'] = $meals;
        }
        if(!empty($personChargePhone)){
            $add['personChargePhone'] = $personChargePhone;
        }
        if(!empty($street)){
            $add['street'] = $street;
        }
        if(empty($company) && empty($personCharge) && empty($personChargePhone) && empty($street)){
            return zy_array(false,'无修改参数','',300,false);
        }

        $add['updateUserId'] = $user_id;
        $add['updateTime'] = time();
        $re = Db::name('statistics_dir')->where('id',$id)->update($add);
        return zy_array(true,'修改成功',$re,200,false);
    }

    /**
     * 区域列表
     */
    public function region_list()
    {
        $param = $this->request->param();
        $re = Db::name('statistics_regions')->select()->toArray();
        $rest = array();
        foreach ($re as $key=>$item){
            if($item['parentIndexCode'] == null){
                $children = array();
                foreach ($re as $keys=>$items){
                    $children_s = array();
                    foreach ($re as $keys_s=>$items_s){
                        if($items_s['parentIndexCode'] == $items['indexCode']){
                            $cr_s=array("value"=>$items_s['indexCode'],"label"=>$items_s['name']);
                            array_push($children_s,$cr_s);
                        }
                    }
                    if($items['parentIndexCode'] == $item['indexCode']){
                        if(sizeof($children_s)>0){
                            $cr=array("value"=>$items['indexCode'],"label"=>$items['name'],"children"=>$children_s);
                        }else{
                            $cr=array("value"=>$items['indexCode'],"label"=>$items['name']);
                        }
                        array_push($children,$cr);
                    }
                }
                $r=array("value"=>$item['indexCode'],"label"=>$item['name'],"children"=>$children);
                array_push($rest,$r);
            }
        }
        return zy_array(true,'查询成功',$rest,200,false);
    }

    /**
     * 上报违规
     */
    public function report_school(){
        $param = $this->request->param();

        $id = $param['id'];//学校id
        $name = $param['dirName'];//学校名称
        $company = $param['company'];//单位名称
        $violation = $param['violation'];//违规选项
        $title = $param['title'];//上报标题
        $content = $param['content'];//上报内容
        $personCharge = $param['personCharge'];//负责人
        $personChargePhone = $param['personChargePhone'];//负责人联系方式
        $street = $param['street'];//学校地址
        $user_id = $param['user_id'];//用户id

        if(empty($param['user_id'])){
            return zy_array(false,'请传入用户id(user_id)','',300,false);
        }
        if(empty($id) || empty($name) || empty($company) || empty($violation) || empty($title) || empty($content) || empty($personCharge)
            || empty($personChargePhone) || empty($street)){
            return zy_array(false,'参数缺失','',300,false);
        }
        if(!empty($param['file'])){
            $file = $param['file'];//附件
            $info['enclosure'] = $file;
        }

        $userInfo = Db::name('user')->where('id='.$user_id)->find();

        $info['school_id'] = $id;
        $info['name'] = $name;
        $info['company'] = $company;
        $info['violation'] = $violation;
        $info['title'] = $title;
        $info['content'] = htmlspecialchars_decode($content);;
        $info['personCharge'] = $personCharge;
        $info['personChargePhone'] = $personChargePhone;
        $info['street'] = $street;
        $info['userId'] = $user_id;
        $info['user_login'] = $userInfo['user_login'];
        $info['time'] = time();
        $info['timeStr'] = date("Y-m-d H:i",time());
        $re = Db::name('report_school')->insert($info);
        if(empty($re)){
            return zy_array (false,'上传失败!','',300,false);
        }
        return zy_array(true,'上传成功','',200,false);
    }

    /**
     * 违规列表
     */
    public function report_list()
    {
        $param = $this->request->param();
        $paginate=empty($param['paginate'])?10:$param['paginate'];
        $id = $param['id'];
        if(empty($id)){
            return zy_array(false,'请传入学校id','',300,false);
        }
        $where = ' school_id = '.$id;
        if(!empty($param['violation'])){
            $where .=" and violation like '%".$param['violation']."%'";
        }
        if(!empty($param['timeStart'])){
            $st = strtotime($param['timeStart']);
            $where .=" and time >= ".$st;
        }
        if(!empty($param['timeEnd'])){
            $se = strtotime($param['timeEnd']);
            $where .=" and time <= ".$se;
        }
        if(!empty($param['status'])){
            $where .=" and status = '".$param['status']."'";
        }
        $re = Db::name('report_school')->where($where)->order('id','asc')->paginate($paginate);
        $list = $re->toArray();
        foreach ($re as $key => $val){
            $imgArr = explode(',',$val['enclosure']);
            $imgArrPath = array();
            foreach($imgArr as $item){
                if(!empty($item)){
                    array_push($imgArrPath, $this->APP_PATH.'uploadFile/'.$item);
                }
            }
            $list['data'][$key]['path'] = $imgArrPath;
        }
        return zy_array(true,'查询成功',$list,200,false);
    }

    /***************************************************************************************************************************************************************************
     ***************************************************************************************************************************************************************************
     *****************************************************                    学校后台接口                   *********************************************************************
     ***************************************************************************************************************************************************************************
     ***************************************************************************************************************************************************************************/

    /**
     * 添加人员
     */
    public function add_health_person(){
        $data = $this->request->post();
        $neadArg = ["nickname"=>[true, 0, "请填写姓名"], "mobile"=>[true, 1, "请填写手机号"], "face_thumb"=>[true, 0, "请上传人脸照片"],
            "health_card"=>[true, 0, "请上传健康证照片"] , "health_endtime"=>[true, 0, "请填写健康证到期时间"], "member_type"=>[true, 0, "请填写人员类别"],"health_id_card"=>[true, 1, "请输入健康证号"],
            "id_card"=>[true, 1,"请输入身份证号"]];

        $uid = $data['uid'];//用户id
        $userInfo = Db::name('user')->where('id='.$uid)->find();
        if($userInfo['user_type'] != 2){
            return zy_array(false,'用户类型错误','',300,false);
        }
        $school_id = $userInfo['school'];
        if(empty($school_id)){
            return zy_array(false,'未找到学校','',300,false);
        }
        $schoolInfo = Db::name('statistics_dir')->where('id',$school_id)->find();
        $company = $schoolInfo['dirName'];
        $dataInfo = checkArg($neadArg, $data);
        $id_card = array_pop($dataInfo);
        $model = new base("member_info");
        $res = $model->get_one(["id_card"=>["=", $id_card]]);
        $upOrIn = !empty($res)?true:false; //true是数据库没有该数据，其他是有
        $dataInfo["addtime"] = time();
        $dataInfo["timeStr"] = date("Y-m-d H:i:s",time());
        $dataInfo["school_id"] = $school_id;
        $dataInfo["company"] = $company;

        $thumb_name = $company ."_". $dataInfo["nickname"];

//        $retData = $this->uploadDistant($company, $thumb_name, $this->hrefPath.$dataInfo["face_thumb"], $id_card, $upOrIn);
////        if($retData[0])
////        {
            $face_before = preg_replace("/[0-9|A-Z|a-z]+\./", "人脸_".$thumb_name.".", $dataInfo["face_thumb"]);
            $health_before = preg_replace("/[0-9|A-Z|a-z]+\./", "健康证_".$thumb_name.".", $dataInfo["health_card"]);
            if(empty($face_before) || empty($health_before))
            return zy_array(false,'图片地址错误',$dataInfo,300,false);
            rename($this->uploadPath."/".$dataInfo["face_thumb"], $this->uploadPath."/".$face_before);
            rename($this->uploadPath."/".$dataInfo["health_card"], $this->uploadPath."/".$health_before);
            $dataInfo["face_thumb"] = $face_before;
            $dataInfo["health_card"] = $health_before;
            if($upOrIn == 0)
            {
                $dataInfo["id_card"] = $id_card;
                $model->insert($dataInfo);
            }
            else
                $model->update($dataInfo, ["id_card"=>["=", $id_card]]);
            return zy_array(true,'添加成功',$dataInfo,200,false);
//        }
//        else
//        {
//            return zy_array(false,'添加失败',$dataInfo,300,false);
//        }
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

    /**
     * 人员列表
     */
    public function health_list()
    {
        $param = $this->request->param();
        $paginate=empty($param['paginate'])?10:$param['paginate'];
        if(empty($param['uid'])){
            return zy_array(false,'请传入用户id(user_id)','',300,false);
        }
        $user_id = $param['uid'];
        $userInfo = Db::name('user')->where('id='.$user_id)->find();
        if($userInfo['user_type']!=2){
            return zy_array(false,'该用户类型不能查看学校列表','',300,false);
        }
        $where = '1 = 1 ';
        if(!empty($param['nickname'])){
            $where .=" and nickname like '%".$param['nickname']."%'";
        }
        if(!empty($param['id_card'])){
            $where .=" and id_card like '%".$param['id_card']."%'";
        }
        if(!empty($param['health_id_card'])){
            $where .=" and health_id_card like '%".$param['health_id_card']."%'";
        }
        if(!empty($param['time_start'])){
            $where .= " and health_endtime >= '".$param['time_start']."'";
        }
        if(!empty($param['time_end'])){
            $where .= " and health_endtime <= '".$param['time_end']."'";
        }
        //获取健康证到期时间天数
        $configInfo = Db::name('school_config')->where("title = 'healthTime' ")->find();

        //根据用户id获取学校id
        $where .= " and school_id = ".$userInfo['school'];
        $re = Db::name('member_info')->where($where)->order('MIID','asc')->paginate($paginate);
        $list = $re->toArray();
        foreach ($re as $key => $val){
            $is_over = 0;
            $list['data'][$key]['faceThumbPath'] = array($this->APP_PATH.'uploadFile/'.$val['face_thumb']);
            $list['data'][$key]['healthCardPath'] = array($this->APP_PATH.'uploadFile/'.$val['health_card']);
            if(!empty($configInfo)){
                $zero1=strtotime (date("y-m-d")); //当前时间
                $zero2=strtotime ($val['health_endtime']);  //健康证时间
                $cut_time = $zero2-$zero1;
                $day = floor($cut_time/(3600*24));
                if($zero1>$zero2){
                    //过期时间
                    $is_over = -1;
                }elseif($day<$configInfo['content']){
                    //快到期时间
                    $is_over = 1;
                }else{
                    //正常时间
                    $is_over = 2;
                }
            }
            $list['data'][$key]['is_over'] = $is_over;
        }
        return zy_array(true,'查询成功',$list,200,false);
    }

    /**
     * 删除人员
     */
    public function del_health(){
        $param = $this->request->param();
        $id = $param['id'];
        if(empty($id)){
            return zy_array(false,'未传参数id',null,300,false);
        }
        $data = Db::name('member_info')->where('MIID',$id)->find();
        if(empty($data)){
            return zy_array(false,'查无该id数据',null,300,false);
        }
        $re = Db::name('member_info')->where('MIID',$id)->delete();
        if(empty($re)){
            return zy_array(false,'删除失败',null,300,false);
        }
        return zy_array(true,'删除成功','',200,false);
    }

    /**
     * 网格员上报列表
     */
    public function report_school_list()
    {
        $param = $this->request->param();
        $paginate=empty($param['paginate'])?10:$param['paginate'];
        $uid = $param['uid'];
        if(empty($uid)){
            return zy_array(false,'请传入uid','',300,false);
        }
        $userInfo = Db::name('user')->where('id='.$uid)->find();
        $where = ' school_id = '.$userInfo['school'];
        if(!empty($param['personCharge'])){
            $where .=" and personCharge like '%".$param['personCharge']."%'";
        }
        if(!empty($param['user_login'])){
            $where .=" and user_login like '%".$param['user_login']."%'";
        }
        if(!empty($param['timeStart'])){
            $st = strtotime($param['timeStart']);
            $where .=" and time >= ".$st;
        }
        if(!empty($param['timeEnd'])){
            $se = strtotime($param['timeEnd']);
            $where .=" and time <= ".$se;
        }
        $re = Db::name('report_school')->where($where)->order('id','asc')->paginate($paginate);
        $list = $re->toArray();
        foreach ($re as $key => $val){
            $imgArr = explode(',',$val['enclosure']);
            $imgArrPath = array();
            foreach($imgArr as $item){
                if(!empty($item)){
                    array_push($imgArrPath, $this->APP_PATH.'uploadFile/'.$item);
                }
            }
            $list['data'][$key]['path'] = $imgArrPath;
        }
        return zy_array(true,'查询成功',$list,200,false);
    }

    /**
     * 处理上报信息
     */
    public function edit_report_message(){
        $param = $this->request->param();
        $id = $param['id'];
        $status = $param['status'];
        $describe = $param['describe'];
        if(empty($id) || empty($status) || empty($describe)){
            return zy_array(false,'参数不能为空','',300,false);
        }
        $re = Db::name('report_school')->where('id='.$id)->find();
        if(empty($re)){
            return zy_array(false,'未找到数据','',300,false);
        }
        $re['status'] = $status;
        $re['describe'] = htmlspecialchars_decode($describe);
        $res = Db::name('report_school')->where('id',$id)->update($re);
        if(empty($res)){
            return zy_array(false,'更新失败','',300,false);
        }
        return zy_array(true,'更新成功','',200,false);
    }

    /**
     * 导出excel
     */
    public function out_excel(){
        /**
         * 参数
         */
        $param = $this->request->param();
        $xlsName = $param['xlsName'];//表格标题
        $isImg=$param['isImg'];//第几列是图片
        //注意  数组第一个字段必须是小写  数组第二个（列标题）根据你的情况填写
        $xlsCell = $param['xlsCell'];
        if(empty($isImg) || !isset($isImg)){
            $isImg = 999;
        }
        $xlsData = $param['xlsData'];;
        $this->exportExcel($xlsName,$xlsCell,$xlsData,$isImg);
    }

    public function exportExcel($expTitle,$expCellName,$expTableData,$isImg){
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
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]);
        }
        // Miscellaneous glyphs, UTF-8
        for($i=0;$i<$dataNum;$i++){
            for($j=0;$j<$cellNum;$j++){
                $imgstr = explode(',', $isImg);
                foreach ($imgstr as $item) {
                    if($j == $item){
                        $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(80);
                        $dir = 'uploadFile/';
                        $fileNameStr = $expTableData[$i][$expCellName[$j][0]];
                        // 图片生成
                        $objDrawing[$j] = new \PHPExcel_Worksheet_Drawing();
                        $objDrawing[$j]->setPath($dir.$fileNameStr);
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
                        $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3), $expTableData[$i][$expCellName[$j][0]]);
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
        return zy_array(true,'导出成功','public/'.$download_path,200,false);
    }



}