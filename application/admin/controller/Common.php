<?php
namespace app\admin\controller;
use auth\Auth;
use think\Controller;
use think\Request;

class Common extends Controller {

	public $config;
	//分配conf，用于left页面功能定位
	public function _initialize() {
		if (!session('uname')) {
			$this->error('请先登录系统', 'Login/index');
		}
		$request = Request::instance();
		$module = $request->module(); //获取当前模块
		$con = $request->controller(); //获取当前控制器
		$action = $request->action(); //获取当前控制器下当前的方法
		$this->assign('con', $con);
		$this->getConf();
		$auth = new Auth();
		$name = $module . '/' . $con . '/' . $action;
		//$name: admin/Cate/lst  模块/控制器/方法
		//dump($name);
		//dump(session('id'));
		//dump($auth->check($name, session('id')));
		/*if (!$auth->check($name, session('id'))) {
			$this->error('你没有该操作权限！');
		}*/
		//dump($this->config);die;
	}

	public function getConf() {
		$confRes = array();
		$_confRes = db('conf')->field('ename,value')->select();
		foreach ($_confRes as $v) {
			$confRes[$v['ename']] = $v['value'];
		}
		$this->config = $confRes;
	}
}