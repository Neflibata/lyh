<?php
namespace plugins\statistics\model;
//use think\Model;
use think\Db;

/**
 * Class BaseModel
 * @package plugins\statistics\model
 *
 *
 * baseModel 中的函数统一不传前缀
 */
class BaseModel
{
    protected $tableName;
    public $DB;
    public function __construct($tableName)
    {
        $this->tableName = $tableName;
        $this->DB =  DB::name($tableName);
    }

    /**
     * @param $data
     * @return int|string 插入一条数据并返回插入数据的id
     */
    public function insert($data)
    {
        return $this->DB->insertGetId($data);
    }

    /**
     * @param $where
     * @param string $field  $field 写法["userid", "id"]和"userid, id",规定数组格式为正取，字符串为反取
     * @param string $limit
     * @param string $order
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function select($where="1", $field="*", $limit = '', $order='')
    {
        $this->checkWhere($where);
        if(is_array($field) && !empty($field))
            $this->DB->field($field);
        else
            $this->DB->field($field, true);
        $data = $this->G("select", $limit, $order);
        return $data;
    }

    /**
     * @param $where
     * @param string $field 写法["userid", "id"]和"userid, id"
     * @param string $limit
     * @param string $order
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function get_one($where, $field="*", $limit = '', $order='')
    {
        $this->checkWhere($where);
        if(is_array($field) && !empty($field))
            $this->DB->field($field); //文档中说后面加个true则表示除了这个字段，其他字段的数据都要，所以配置传入field为字符串时为正向取数据，数组为反向取
        else if(!empty($field))
            $this->DB->field($field, true);
        $data = $this->G("find", $limit, $order);
        return $data;
    }
    public function update($data, $where)
    {
        $this->checkWhere($where);
        $upData = [];
        foreach ($data as $key=>$value)
        {
            if(is_array($value)) //这个是checkData的时候处理的（如果类型为+=和-=的时候,自动把$value变为数组格式）如果有修改，两边都要变动
            {
                switch ($value[0])
                {
                    case "+=":$this->DB->inc($key, $value[1]);break;
                    case "-=":$this->DB->dec($key, $value[1]);break;
                }
            }
            else
            {
                $upData[$key] = $data[$key];
            }
        }
        return $this->DB->update($upData);
    }
    public function del($where)
    {
        $this->checkWhere($where);
        $this->DB->delete();
    }

    /**
     * @param $data
     * where[0][1, 2, 3, 4] 字典 0 字段名 1 形式（=，>，<之类）2数据 3（or或and） 4 连表时用，（B开头）checkwhere函数中不使用
     */
    private function checkWhere($data)
    {
        $num = 0;
        if(is_array($data))
        {
            foreach ($data as $key=>$value)
            {
                if(is_array($value))
                {
                    $vc = $value[1];
                    if(in_array($value[0], ["&lt;", "&gt;", "&le;", "&qe;"]))
                        $value[0] = htmlspecialchars_decode($value[0]);
                    if($value[0] == "like")
                        $vc = "%".$vc."%"; //百分号，这里以后要修改
                    if($num != 0 )
                    {
                        if(isset($value[2]) && $value[2] == "or")
                            $this->DB->whereOr($key, $value[0], $vc); //or格式（如果是同一个字段则用in ()）
                        else
                            $this->DB->where($key, $value[0], $vc);
                    }
                    else
                        $this->DB->where($key, $value[0], $vc);

                }
                else
                    $this->DB->where($key, $value);
                $num ++;
            }
        }
        else if($data != "1")
            $this->DB->where($data); //直接sql语句操作
    }
    private function G($type = "select", $limit='', $order='')
    {
        if(!empty($limit))
            $this->DB->limit($limit);
        if(!empty($order))
            $this->DB->order($order);
        $retData = [];
        switch($type)
        {
            case "select":
                $retData = $this->DB->select()->all();break;//获取多条数据
            case "find":
                $retData = $this->DB->find();break; //获取一条数据
        }
        return $retData;
    }

    /**
     * @param $tableData //格式[表名=>["字段名","字段名"], 表名=>["字段名","字段名"],.....]
     * @param $joinData //连接字段格式 ["userid",["userid","uid"],[["B2", "userid"], "uid"]]三种格式，不同的连接方式
     * @param string $where where同checkwhere，基本一致
     * @param string $limit 和TP的传入方式一样
     * @param string $order 和TP的传入方式一样
     * @return array
     * @throws \think\db\exception\BindParamException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */

    //注意的是 字段排除功能不支持跨表和join操作。(所以附表的数据一概用数组形式，并且是正向取值)
    public static function moreTableSelect($tableData, $joinData, $where="1", $page="1", $pagesize="5", $order="")
    {
        $temporaryData = [];
        $num = 0;
        $whereNum = 0;
        if(!is_array($tableData))
            Error("数据错误");
        $moreTableDB = null;
        foreach ($tableData as $key=>$value)
        {
            $str = '';
            if($num == 0)
            {
                $moreTableDB = Db::name($key)->alias(" B".$num);
            }
            else
            {
                if(!empty($joinData[$num-1]))
                {
                    $join = $joinData[$num-1];
                    if(is_array($join))
                    {
                        if(is_array($join[0]))
                        {
                            if(array_key_exists($join[0][0], $temporaryData))
                                $moreTableDB->join($key." B".$num, $temporaryData[$join[0][0]].".`".$join[0][1]."`=B".$num.".`".$join[1]."`", "LEFT");
                            else
                                $moreTableDB->join($key." B".$num, $join[0][0].".`".$join[0][1]."`=B".$num.".`".$join[1]."`", "LEFT");
                        }
                        else
                            $moreTableDB->join($key." B".$num, "B0.`".$join[0]."`=B".$num.".`".$join[1]."`", "LEFT");
                    }
                    else
                        $moreTableDB->join($key." B".$num, "B0.`".$join."`=B".$num.".`".$join."`", "LEFT");

                }

            }
            if ($num == 0)
                $str .= "sql_calc_found_rows "; //获取一共多少条数据
            if(is_array($value))
            {
                foreach ($value as $k=>$v)
                {
                    if($v != "*")
                        $str .= "B".$num.".`".$v."` ,";
                    else
                        $str .= "B".$num.".".$v.",";
                }
            }
            else
                Error("副表数据字段必须用数组格式传入");
            $str = substr($str,0,strlen($str)-1);
            $moreTableDB->field($str);
            $temporaryData[$key]="B".$num;
            $num++;
        }
        if(is_array($where))
        {
            foreach ($where as $key=>$value)
            {
                if(is_array($value))
                {
                    $whereKey = $key;
                    $vc = $value[1];
                    if(isset($value["prefix"]))
                        $whereKey = isset($temporaryData[$value["prefix"]])? $temporaryData[$value["prefix"]].".`".$whereKey."`":$value["prefix"].".`".$whereKey."`";
                    if($value[0] == "like")
                        $vc = "%".$vc."%";
                    if($whereNum != 0 && $value[2] == "or")
                        $moreTableDB->whereOr($whereKey, $value[0], $vc);
                    else
                        $moreTableDB->where($whereKey, $value[0], $vc);

                }
                else
                    $moreTableDB->where($key, $value);
                $whereNum ++; //第一个条件好像只能用where
            }
        }
        else if($where != "1")
            $moreTableDB->where($where); //直接sql语句操作
        if(!empty($page))
        {
            $page_num = ((string)($page - 1) * $pagesize) . "," . $pagesize;
            $moreTableDB->limit($page_num);
        }
        if(!empty($order))
            $moreTableDB->order($order);
        $retData = $moreTableDB->select()->all();
        $sql = "select found_rows()";
        $count = $moreTableDB->query($sql); //获取上一条sql语句查询了多少条数据
        return [$retData, array_shift($count)["found_rows()"]];
    }

    /**
     * 当月健康证
     * int all_num所有健康证数量
     * int nor_num未过期的数量
     * int nor_ratio未过期比例 0-100
     */
    public static function month_health($dir_id=null)
    {
        if(empty($dir_id)){
            return zy_json_echo(false,'当月健康证:dir_id不能为空','',-1);
        }
        $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
        $result['all_num']=Db::name('member_info')->where('addtime','egt',$beginThismonth)->where('school_id',$dir_id)->count();
        $result['nor_num']=Db::name('member_info')->where('addtime','egt',$beginThismonth)->where('school_id',$dir_id)->where('health_endtime','>= time',date("Y-m-d",time()))->count();
        if($result['nor_num']==0){
            $result['nor_ratio']=0;
        }else{
            $result['nor_ratio']=round($result['nor_num']/$result['all_num']*100);
        }
        return $result;
    }
    /**
     * 上月健康证
     * int last_all_num所有健康证数量
     * int last_nor_num未过期的数量
     * int last_nor_ratio未过期比例 0-100
     */
    public static function last_month_health($dir_id=null)
    {
        if(empty($dir_id)){
            return zy_json_echo(false,'上月健康证:dir_id不能为空','',-1);
        }
//        $first=strtotime('-1 month');
//        $last=strtotime('-1 month');
        $first=date('Y-m-01 h:i:s', strtotime('-1 month'));
        $last=date('Y-m-t h:i:s', strtotime('-1 month'));
//        $result['last_all_num']=Db::name('member_info')->where('school_id',$dir_id)->count();
//        $result['last_nor_num']=Db::name('member_info')->where('school_id',$dir_id)->where('health_endtime','>= time',date("Y-m-d",time()))->count();
        $result['last_all_num']=Db::name('member_info')
            ->where('school_id',$dir_id)
            ->where('addtime','between time',[$first,$last])
//            ->whereTime('addtime', '<=', date('Y-m-t', strtotime('-1 month')))
            ->count();
        $result['last_nor_num']=Db::name('member_info')
            ->where('school_id',$dir_id)
            ->where('health_endtime','>= time',date('Y-m-t', strtotime('-1 month')))
//            ->whereTime('addtime', '<=', date('Y-m-t', strtotime('-1 month')))
            ->where('addtime','between time',[$first,$last])
            ->count();
        if($result['last_nor_num']==0){
            $result['last_ratio']=0;
        }else{
            $result['last_ratio']=round($result['last_nor_num']/$result['last_all_num']*100);
        }
        return $result;
    }
    /**
     * 当月人脸抓拍
     * int face_num上月抓拍人脸数量
     * int dis_num上月处理人脸数量
     * int dis_pro及时处理率 0-100
     */
    public static function month_face($dir_id=null)
    {
        if(empty($dir_id)){
            return zy_json_echo(false,'当月人脸抓拍:dir_id不能为空','',-1);
        }
        $stat=Db::name('statistics_dir_stat')->where('month_section',date('Y-m', time()))->where('dir_id',$dir_id)->find();
        $result['face_num']=$stat['stranger_face_num']+$stat['emphasis_face_num'];
        $result['dis_num']=$stat['dis_stranger']+$stat['dis_emphasis'];
        $result['dis_pro']= $result['dis_num']<1?0:round($result['dis_num']/$result['face_num']*100);
        return $result;
    }
    /**
     * 上月人脸抓拍
     * int face_num上月抓拍人脸数量
     * int dis_num上月处理人脸数量
     * int dis_pro及时处理率 0-100
     */
    public static function last_month_face($dir_id=null)
    {
        if(empty($dir_id)){
            return zy_json_echo(false,'上月人脸抓拍:dir_id不能为空','',-1);
        }
        $stat=Db::name('statistics_dir_stat')->where('month_section',date('Y-m', strtotime('-1 month')))->where('dir_id',$dir_id)->find();
        $result['last_face_num']=$stat['stranger_face_num']+$stat['emphasis_face_num'];
        $result['last_dis_num']=$stat['dis_stranger']+$stat['dis_emphasis'];
        $result['last_dis_pro']= $result['last_dis_num']<1?0:round($result['last_dis_num']/$result['last_face_num']*100);
        return $result;
    }
    /**
     * 学校12个月健康证过期比例
     */
    public static function year_health($dir_id=null)
    {
        $arr=[];
        for ($i=0;$i<13;$i++){
            $arr[$i]['month']=date('Y-m', strtotime(-$i.' month'));
            $addmonth=date("Y-m",strtotime("+1 month",strtotime($arr[$i]['month'])));
            $arr[$i]['end_time']=Db::name('member_info')->where('health_endtime','like %'.$arr[$i]['month'].'%')->count();
        }
        $stat=Db::name('statistics_dir_stat')->where('month_section',date('Y-m', strtotime('-1 month')))->where('dir_id',$dir_id)->find();
    }
    /**
     * 学校12个月人脸抓拍
     */
    public static function year_face($dir_id=null)
    {
        $arr=[];
        for ($i=0;$i<12;$i++){
            $month=date('Y-m', strtotime(-$i.' month'));
            $arr[$i]=Db::name('statistics_dir_stat')->where('dir_id',$dir_id)->where('month_section',$month)->find();
            if(empty($arr[$i])){
                $arr[$i]['id']=$dir_id;
                $arr[$i]['month_section']=$month;
                $arr[$i]['stranger_face_num']=0;
                $arr[$i]['emphasis_face_num']=0;
                $arr[$i]['dis_stranger']=0;
                $arr[$i]['dis_emphasis']=0;
            }
            $num=$arr[$i]['stranger_face_num']+$arr[$i]['emphasis_face_num'];
            $dis=$arr[$i]['dis_stranger']+$arr[$i]['dis_emphasis'];
            $arr[$i]['dis_pro']=$dis<1?0:round($dis/$num*100);
        }
        return $arr;
    }
    /**
     * 学校12个月事件处理率
     */
    public static function year_dis($dir_id=null)
    {

    }
}