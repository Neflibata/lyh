<?php

namespace plugins\statistics\controller;

use cmf\controller\PluginRestBaseController;
//引用插件基类
use think\Db;
use think\Request;
use plugins\statistics\model\BaseModel as base;
/**
 * api控制器
 */
class ExcelController extends PluginRestBaseController
{
    protected $config = [
        'stranger_status' => ['未处理','已反馈','忽略'],
        'stranger_status2' => ['未处理','已处理','忽略']
    ];
    function __construct()
    {
        header("content-type:text/html;charset=utf-8");
        //        Config('app_trace',false);
        parent::__construct();
    }
    //陌生人导出
    public function out_manageStranger()
    {
        $param = input('');
        if (empty($param['uid'])) {
            return zy_json_echo(false, '请检查uid', '', -1);
        }
        $param['uid'] = $this->verify_power($param['uid'],true);
        $where = '1 = 1 ';
        if (!empty($param['dir_id'])) {
            $where .= " and b.dir_id = '" . $param['dir_id'] . "'";
        }
        if (!empty($param['indexCode'])) {
            $where .= " and c.indexCode = '" . $param['indexCode'] . "'";
        }
        if (!empty($param['start_time'])) {
            $where .= " and a.faceTime >= '" . strtotime($param['start_time']) . "'";
        }
        if (!empty($param['end_time'])) {
            $where .= " and a.faceTime <= '" . strtotime($param['end_time']) . "'";
        }
        $list = Db::name('statistics_face_stranger')->alias('a')->join('cmf_statistics_cameras b', 'a.srcIndex=b.cameraIndexCode', 'left')->join('cmf_statistics_regions c', 'b.regionIndexCode=c.indexCode', 'left')->where($where)->field('a.id,a.faceTime,a.stranger_status,a.ageGroup,a.glass,a.gender,b.cameraName,c.name')->order('a.id desc')->select()->each(function ($item, $key) {
            $module_info = getModuleConfig('statistics', 'config', 'config.json');
            $module_info = json_decode($module_info, true);
            $item['ageGroup'] = $module_info['age'][$item['ageGroup']];
            $item['glass'] = $module_info['glasses'][$item['glass']];
            $item['gender'] = $module_info['sex'][$item['gender']];
            return $item;
        });
        $xlsName = "陌生人列表";
        //表格标题
        $encode = mb_detect_encoding($xlsName, array("ASCII", "UTF-8", "GB2312", "GBK", "BIG5"));
        //注意  数组第一个字段必须是小写  数组第二个（列标题）根据你的情况填写
        $xlsCell = [["id","ID"], ["name","区域"], ["cameraName" , "抓拍点"], ["gender" , "性别"], ["ageGroup" , "年龄段"], ["stranger_status" ,"状态"], ["faceTime","抓拍时间"]];
        if (empty($isImg) || !isset($isImg)) {
            $isImg = 999;
        }
        $xlsData = [];
        foreach ($list as $k => $v) {
            $xlsData[$k]['id'] = $v['id'];
            $xlsData[$k]['name'] = $v['name'];
            $xlsData[$k]['cameraName'] = $v['cameraName'];
            $xlsData[$k]['gender'] = $v['gender'];
            $xlsData[$k]['ageGroup'] = $v['ageGroup'];
            $xlsData[$k]['stranger_status'] = $this->config['stranger_status'][$v['stranger_status']];
            $xlsData[$k]['faceTime'] = $v['faceTime'];
        }
        $this->exportExcel($xlsName, $xlsCell, $xlsData, $isImg);
    }
    //健康证导出
    public function out_schoolList()
    {
        $param=input('');
        if(empty($param['uid'])){
            return zy_json_echo(false,'参数错误',[],-1);
        }
        $param['uid'] = $this->verify_power($param['uid'],true);
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
            ->field('a.MIID,a.nickname,a.mobile,a.member_type,a.health_endtime,b.dirName,c.name')
            ->order('a.MIID desc')
            ->select();
        $xlsName = "健康证清单";
        //表格标题
        $encode = mb_detect_encoding($xlsName, array("ASCII", "UTF-8", "GB2312", "GBK", "BIG5"));
        //注意  数组第一个字段必须是小写  数组第二个（列标题）根据你的情况填写
        $xlsCell = [
            ["MIID","ID"],
            ["nickname","姓名"],
            ["name" , "所属地区"],
            ["dirName" , "所属学校"],
            ["mobile" , "联系方式"],
            ["member_type" ,"人员类别"],
            ["health_endtime","健康证到期时间"]
        ];
        if (empty($isImg) || !isset($isImg)) {
            $isImg = 999;
        }
        $xlsData=[];
        foreach ($list as $k=>$v){
            $xlsData[$k]['MIID']=$v['MIID'];
            $xlsData[$k]['nickname']=$v['nickname'];
            $xlsData[$k]['name']=$v['name'];
            $xlsData[$k]['dirName']=$v['dirName'];
            $xlsData[$k]['mobile']=$v['mobile'];
            $xlsData[$k]['member_type']=$v['member_type'];
            $xlsData[$k]['health_endtime']=$v['health_endtime'];
        }
        $this->exportExcel($xlsName, $xlsCell, $xlsData, $isImg);
    }

    /**
     * 警报检测-陌生人导出
     */
    public function  out_strangerAlarm()
    {
        $param=input('');
        if(empty($param['uid'])){
            return zy_json_echo(false,'参数错误',[],-1);
        }
        $param['uid'] = $this->verify_power($param['uid']);
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
            ->join('cmf_user c','a.examine_uid=c.id','left')
            ->where($where)
            ->field('a.*,b.cameraName,c.user_login')
            ->where('a.dir_id','in',$user['school'])
            ->order('id desc')
            ->select()->toArray();
        $arr=[];

        $xlsName = "陌生人脸抓拍清单";
        //表格标题
        $encode = mb_detect_encoding($xlsName, array("ASCII", "UTF-8", "GB2312", "GBK", "BIG5"));
        //注意  数组第一个字段必须是小写  数组第二个（列标题）根据你的情况填写
        $xlsCell = [
            ["id","ID"],
            ["faceTime","抓拍时间"],
            ["cameraName","抓拍点"],
            ["ageGroup","年龄段"],
            ["gender" , "性别"],
            ["glass" , "是否戴眼镜"],
            ["stranger_status" , "状态"],
            ["user_login","上报人"],
            ["examine_title" ,"上报标题"],
//            ["examine_content","上报内容"],
            ["examine_time","上报时间"]
        ];
        if (empty($isImg) || !isset($isImg)) {
            $isImg = 999;
        }
        $xlsData=[];
        foreach ($list as $k=>$v){
            $xlsData[$k]['id']=$v['id'];
            $xlsData[$k]['faceTime']=$v['faceTime'];
            $xlsData[$k]['cameraName']=$v['cameraName'];
            $xlsData[$k]['ageGroup']=$module_info['age'][$v['ageGroup']];
            $xlsData[$k]['gender']=$module_info['glasses'][$v['glass']];
            $xlsData[$k]['glass']=$module_info['sex'][$v['gender']];
            $xlsData[$k]['stranger_status']=$this->config['stranger_status2'][$v['stranger_status']];
            $xlsData[$k]['user_login']=$v['user_login'];
            $xlsData[$k]['examine_title']=$v['examine_title'];
//            $xlsData[$k]['examine_content']=$v['examine_content'];
            $xlsData[$k]['examine_time']=date('Y-m-d h:i:s',$v['examine_time']);
        }
        $this->exportExcel($xlsName, $xlsCell, $xlsData, $isImg);

    }
    /**
     * 学校后台-人员信息维护导出
     */
    public function health_list()
    {
        $param = $this->request->param();
        $param = zy_decodeData($param,false);
        $paginate=empty($param['paginate'])?10:$param['paginate'];
        if(empty($param['uid'])){
            return zy_array(false,'请传入用户id(user_id)','',300,false);
        }
        $user_id = $param['uid'];
        $userInfo = Db::name('user')->where('id='.$user_id)->find();
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
        if(!empty($param['school_id'])){
            $where .= " and school_id in (".$param['school_id'].")";
        }else{
            $where .= " and school_id in (".$userInfo['school'].")";
        }
        $re = Db::name('member_info')->where($where)->order('MIID','asc')->paginate($paginate);
        $xlsName = "人员信息维护";
        //表格标题
        $encode = mb_detect_encoding($xlsName, array("ASCII", "UTF-8", "GB2312", "GBK", "BIG5"));
        //注意  数组第一个字段必须是小写  数组第二个（列标题）根据你的情况填写
        $xlsCell = [
            ["id","ID"],
            ["nickname","姓名"],
            ["company","单位名称"],
            ["mobile","手机号"],
            ["id_card" , "身份证号"],
            ["health_id_card" , "健康证号"],
            ["health_endtime" , "健康证到期时间"],
            ["member_type","人员类别"],
            ["timeStr" ,"添加时间"]
        ];
        if (empty($isImg) || !isset($isImg)) {
            $isImg = 999;
        }
        $xlsData=[];
        foreach ($re as $k=>$v){
            $xlsData[$k]['id']=$v['MIID'];
            $xlsData[$k]['nickname']=$v['nickname'];
            $xlsData[$k]['company']=$v['company'];
            $xlsData[$k]['mobile']=$v['mobile'];
            $xlsData[$k]['id_card']=$v['id_card'];
            $xlsData[$k]['health_id_card']=$v['health_id_card'];
            $xlsData[$k]['health_endtime']=$v['health_endtime'];
            $xlsData[$k]['member_type']=$v['member_type'];
            $xlsData[$k]['timeStr']=$v['timeStr'];
        }
        $this->exportExcel($xlsName, $xlsCell, $xlsData, $isImg);
    }

    /**
     * 数据对比导出
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
            //当月健康证
            $month_health=base::month_health($v['id']);
            $result['dir'][$k]['nor_ratio']=$month_health['nor_ratio'];

            //上月健康证未过期比例
            $last_month_health=base::last_month_health($v['id']);
            $result['dir'][$k]['last_ratio']=$last_month_health['last_ratio'];
            //当月人脸抓拍
            $month_face=base::month_face($v['id']);
            $result['dir'][$k]['face_num']= $month_face['face_num'];
            $result['dir'][$k]['dis_pro']= $month_face['dis_pro'];
            //上月人脸抓拍
            $last_month_face=base::last_month_face($v['id']);
            $result['dir'][$k]['last_face_num']= $last_month_face['last_face_num'];
            $result['dir'][$k]['last_dis_pro']= $last_month_face['last_dis_pro'];
        }
        $xlsName = "各学校数据对比信息";
        //表格标题
        $encode = mb_detect_encoding($xlsName, array("ASCII", "UTF-8", "GB2312", "GBK", "BIG5"));
        //注意  数组第一个字段必须是小写  数组第二个（列标题）根据你的情况填写
        $xlsCell = [
            ["school_name","学校"],
            ["nor_ratio","当月健康证未过期比例(%)"],
            ["last_ratio","上月健康证未过期比例(%)"],
            ["face_num","当月人脸抓拍警告次数"],
            ["last_face_num" , "上月人脸抓拍警告次数"],
            ["dis_pro" , "当月事件处理及时率(%)"],
            ["last_dis_pro" , "上月事件处理及时率(%)"]
        ];
        if (empty($isImg) || !isset($isImg)) {
            $isImg = 999;
        }
        $xlsData=[];
        foreach ($result['dir'] as $k=>$v){
            $xlsData[$k]['school_name']=$v['school_name'];
            $xlsData[$k]['nor_ratio']=$v['nor_ratio'];
            $xlsData[$k]['last_ratio']=$v['last_ratio'];
            $xlsData[$k]['face_num']=$v['face_num'];
            $xlsData[$k]['last_face_num']=$v['last_face_num'];
            $xlsData[$k]['dis_pro']=$v['dis_pro'];
            $xlsData[$k]['last_dis_pro']=$v['last_dis_pro'];
        }
        $this->exportExcel($xlsName, $xlsCell, $xlsData, $isImg);
    }
    private function exportExcel($expTitle, $expCellName, $expTableData, $isImg)
    {
        vendor("phpoffice.phpexcel.Classes.PHPExcel");
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);
        //文件名称
        $fileName = $xlsTitle;
        //or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        $objPHPExcel = new \PHPExcel();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');
        //合并单元格
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle . ' 导出时间:' . date('Y-m-d H:i:s'));
        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($cellName[$i])->setWidth(30);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
        }
        // Miscellaneous glyphs, UTF-8
        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {
                $imgstr = explode(',', $isImg);
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), $expTableData[$i][$expCellName[$j][0]]);
                foreach ($imgstr as $item) {
                    if ($j == $item) {
                        $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(80);
                        $dir = 'uploadFile/';
                        $fileNameStr = $expTableData[$i][$expCellName[$j][0]];
                        // 图片生成
                        $names = iconv("utf-8", "gb2312", $fileNameStr);
                        if (file_exists($dir . $names)) {
                            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), "");
                            $objDrawing[$j] = new \PHPExcel_Worksheet_Drawing();
                            $objDrawing[$j]->setPath($dir . $names);
                            // 设置宽度高度
                            $objDrawing[$j]->setHeight(80);
                            //照片高度
                            $objDrawing[$j]->setWidth(80);
                            //照片宽度
                            /*设置图片要插入的单元格*/
                            $objDrawing[$j]->setCoordinates($cellName[$j] . ($i + 3));
                            // 图片偏移距离
                            $objDrawing[$j]->setOffsetX(12);
                            $objDrawing[$j]->setOffsetY(12);
                            $objDrawing[$j]->setWorksheet($objPHPExcel->getActiveSheet());
                        } else {
                            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), "");
                        }
                    }
                }
            }
        }
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename={$fileName}.xls");
        //attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $time = date("Ymd", time());
        if (!file_exists('uploadFile/' . $time . '/')) {
            mkdir('uploadFile/' . $time . '/');
        }
        $download_path = "uploadFile/" . $time . "/" . time() . ".xlsx";
        $objWriter->save($download_path);
        return zy_array(true, '导出成功', $this->request->domain() . $this->request->root() . $download_path, 200, false);
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
}