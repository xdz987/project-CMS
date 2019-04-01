<?php
namespace app\admin\controller;
use app\admin\controller\Common;

class AuthGroup extends Common {

	public function lst() {

		$authGroupRes = db('authGroup')->paginate(2);
		$page = $authGroupRes->render();
		$this->assign([
			'authGroupRes' => $authGroupRes,
			'page' => $page,
		]);
		return view('list');
	}

	public function add() {
		if (request()->isPost()) {
			$data = input('post.');
			$validate = validate('AuthGroup');
			if (!$validate->scene('add')->check($data)) {
				$this->error($validate->getError());
			}
			$add = db('authGroup')->insert($data);
			if ($add) {
				$this->success('添加用户组成功！', 'lst');
			} else {
				$this->error('添加用户组失败！');
			}
		}
		return view();
	}

	public function edit() {

		if (request()->isPost()) {
			$data = input('post.');
			$validate = validate('AuthGroup');
			if (!$validate->scene('edit')->check($data)) {
				$this->error($validate->getError());
			}
			$save = db('authGroup')->update($data);
			if ($save !== false) {
				$this->success('修改用户组成功！', 'lst');
			} else {
				$this->error('修改用户组失败！');
			}
		}
		$id = input('id');
		$authGroups = db('authGroup')->find($id);
		$this->assign([
			'authGroups' => $authGroups,
		]);
		return view();
	}

	public function del($id) {

		//删除用户组前先删除该用户组下所有管理员
		$delAdmin = db('admin')->where(['groupid' => $id])->delete();
		$del = db('authGroup')->delete($id);
		if ($del && $delAdmin) {
			$this->success('删除用户组成功！', 'lst');
		} else {
			$this->error('删除用户组失败！');
		}
	}

	//分配权限
	public function power() {

		if (request()->isPost()) {
			$data = input('post.');
			$rules = implode(',', $data['rules']);
			$save = db('authGroup')->where(array('id' => $data['id']))->update(['rules' => $rules]);
			if ($save !== false) {
				$this->success('分配权限成功！');
			} else {
				$this->error('分配权限失败！');
			}
			return;
		}
		//查询所有一级权限
		$data = db('authRule')->where(['pid' => 0])->select();
		//查询一级规则下的二级规则
		foreach ($data as $k => $v) {
			$data[$k]['children'] = db('authRule')->where(['pid' => $v['id']])->select();
			//查询二级规则下的三级规则，最多三级（用于测试）
			foreach ($data[$k]['children'] as $k1 => $v1) {
				$data[$k]['children'][$k1]['children'] = db('authRule')->where(['pid' => $v1['id']])->select();
			}
		}
		$id = input('id');
		$authGroups = db('authGroup')->find($id);
		$rules = explode(',', $authGroups['rules']);
		$this->assign([
			'authGroups' => $authGroups,
			'data' => $data,
			'rules' => $rules,
		]);
		return view();
	}

	//ajax异步修改模型状态
	public function changestatus() {
		if (request()->isAjax()) {
			$authGroupid = input('authGroupid');
			$status = db('authGroup')->field('status')->where('id', $authGroupid)->find();
			$status = $status['status'];
			if ($status == 1) {
				db('authGroup')->where('id', $authGroupid)->update(['status' => 0]);
				echo 1; //由显示改为隐藏
			} else {
				db('authGroup')->where('id', $authGroupid)->update(['status' => 1]);
				echo 2; //由隐藏改为显示
			}
		} else {
			$this->error("非法操作!");
		}
	}
}
