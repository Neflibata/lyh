<?php


namespace plugins\school\controller;
use cmf\controller\PluginAdminBaseController;
use think\Db;
use plugins\school\model\BaseModel as base;
use plugins\school\model\ModelC as MC;
use plugins\school\model\chartModel;
use think\Exception;

class SchoolController extends PluginAdminBaseController
{

    /**
     *  首页
     */
    public function index()
    {
        return $this->fetch('/index');
    }

    /**
     * 添加/修改 学校配置
     */
    public function updateSchoolConfig(){
        $param = $this->request->param();
        $title = $param['title'];
        $content = $param['content'];
        $describe = $param['describe'];
        $configInfo = Db::name('school_config')->where("title = '".$title."' ")->find();
        $info['title'] = $title;
        $info['content'] = $content;
        $info['describe'] = $describe;

        if(empty($configInfo)){
            $re = Db::name('school_config')->insert($info);
        }else{
            $configInfo['title'] = $title;
            $configInfo['content'] = $content;
            $configInfo['describe'] = $describe;
            $re = Db::name('school_config')->update($configInfo);
        }
        if(empty($re)){
            return zy_array (false,'修改失败!','',300,false);
        }
        return zy_array(true,'修改成功','',200,false);
    }

    /**
     * 列表 学校配置
     */
    public function schoolConfigInfo(){
        $param = $this->request->param();
        $title = $param['title'];
        $configInfo = Db::name('school_config')->where("title = '".$title."' ")->find();
        return zy_array(true,'查询成功',$configInfo,200,false);
    }


}