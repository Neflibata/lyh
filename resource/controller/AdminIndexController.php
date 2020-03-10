<?php
namespace plugins\Resource\controller;

use cmf\controller\PluginAdminBaseController;//引入此类
use think\Db;

//AdminIndexController类和类的index()方法是必须存在的 index() 指向admin_index.html模板也就是模块后台首页
// 并且继承PluginAdminBaseController
class AdminIndexController extends PluginAdminBaseController
{
    protected function _initialize()
    {
        parent::_initialize();
        $adminId = cmf_get_current_admin_id();//获取后台管理员id，可判断是否登录
        if (!empty($adminId)) {
            $this->assign("admin_id", $adminId);
        }
    }

    /**
     * 资源目录
     * @adminMenu(
     *     'name'   => '资源目录',
     *     'parent' => 'admin/Plugin/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '监控资源目录',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $index=new IndexController();
        return $index->index();
    }
    public function school()
    {
        $school=new SchoolController();
        return $school->index();
    }

}
