<?php
namespace app\admin\controller;
use app\admin\controller\Common;

//可省略

class Admin extends Common {

	public function lst() {
		$adminRes = db('admin')->alias('a')->field('a.*,g.title')->join('authGroup g', 'a.groupid=g.id')->paginate(3);
		$page = $adminRes->render();
		$this->assign([
			'adminRes' => $adminRes,
			'page' => $page,
		]);
		return view('list');
	}

	public function add() {
		if (request()->isPost()) {
			$data = input('post.');
			//前端验证
			$validate = validate('admin');
			if (!$validate->scene('add')->check($data)) {
				$this->error($validate->getError());
			}
			//验证
			$data['password'] = MD5($data['password']);
			$data['create_time'] = time();
			$data['last_time'] = time();
			$add = db('admin')->insertGetId($data);
			$_data = array(); //对应管理员和用户组
			$_data['uid'] = $add;
			$_data['group_id'] = $data['groupid'];
			$addGroupAccess = db('authGroupAccess')->insert($_data);
			if ($add && $addGroupAccess) {
				$this->success('添加管理员成功！', 'lst');
			} else {
				$this->error('添加管理员失败！');
			}
			return;
		}
		//所属用户组
		$groupRes = db('authGroup')->select();
		$this->assign([
			'groupRes' => $groupRes,
		]);
		return view();
	}

	public function edit() {
		if (request()->isPost()) {
			$data = input('post.');
			//前端验证
			$validate = validate('admin');
			if (!$validate->scene('edit')->check($data)) {
				$this->error($validate->getError());
			}
			//验证
			if ($data['password']) {
				$data['password'] = MD5($data['password']);
			} else {
				unset($data['password']);
			}
			$save = db('admin')->update($data);
			//更新用户组对应接口表
			db('authGroupAccess')->where(array('uid' => $data['id']))->update(['group_id' => $data['groupid']]);
			if ($save !== false) {
				$this->success('修改管理员成功！', 'lst');
			} else {
				$this->error('修改管理员失败！');
			}
			return;
		}
		$admins = db('admin')->field('id,uname,groupid')->find(input('id'));
		//所属用户组
		$groupRes = db('authGroup')->select();
		$this->assign([
			'admins' => $admins,
			'groupRes' => $groupRes,
		]);
		return view();
	}

	public function del($id) {
		if ($id == 1) {
			$this->error('超级管理员不允许删除！');
		} else {
			$del = db('admin')->delete($id);
			if ($del) {
				$this->success('删除管理员成功！', 'lst');
			} else {
				$this->error('删除管理员失败！');
			}
		}
		return view();
	}

	//ajax异步修改模型状态
	public function changestatus() {
		if (request()->isAjax()) {
			$adminid = input('adminid');
			$status = db('admin')->field('status')->where('id', $adminid)->find();
			$status = $status['status'];
			if ($status == 1) {
				db('admin')->where('id', $adminid)->update(['status' => 0]);
				echo 1; //由显示改为隐藏
			} else {
				db('admin')->where('id', $adminid)->update(['status' => 1]);
				echo 2; //由隐藏改为显示
			}
		} else {
			$this->error("非法操作!");
		}
	}

	//退出登录
	public function logout() {
		session(null);
		$this->success('退出成功！', 'Login/index');
	}
}