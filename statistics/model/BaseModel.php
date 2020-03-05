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
}