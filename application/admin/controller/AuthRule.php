<?php
namespace app\admin\controller;
use app\admin\controller\Common;

class AuthRule extends Common {

	public function lst() {

		//调用模型下的无限极分类方法
		$ruleRes = db('authRule')->select();
		$ruleTree = model('AuthRule')->ruletree($ruleRes);
		$this->assign([
			'ruleTree' => $ruleTree,
		]);
		return view('list');
	}

	public function edit() {

		if (request()->isPost()) {
			$data = input('post.');
			$validate = validate('AuthRule');
			if (!$validate->scene('edit')->check($data)) {
				$this->error($validate->getError());
			}
			$save = db('authRule')->update($data);
			if ($save !== false) {
				$this->success('修改规则成功！', 'lst');
			} else {
				$this->error('修改规则失败！');
			}
			return;
		}
		$id = input('id');
		$rules = db('authRule')->find($id);
		$ruleRes = db('authRule')->select();
		$ruleTree = model('AuthRule')->ruletree($ruleRes);
		$this->assign([
			'ruleTree' => $ruleTree,
			'rules' => $rules,
		]);
		return view();
	}

	//前端ajax请求栏目下的所有子栏目，调用model下的AuthRule模型下的childrenids方法获取子栏目id
	public function ajaxlst() {
		if (request()->isAjax()) {
			$ruleid = input('ruleid');
			$sonids = model('AuthRule')->childrenids($ruleid);
			echo json_encode($sonids);
		} else {
			$this->error('非法操作！');
		}
	}

	public function add() {

		if (request()->isPost()) {
			$data = input('post.');
			$validate = validate('AuthRule');
			if (!$validate->scene('add')->check($data)) {
				$this->error($validate->getError());
			}
			$add = db('authRule')->insert($data);
			if ($add) {
				$this->success('添加规则成功！', 'lst');
			} else {
				$this->error('添加规则失败！');
			}
		}
		//调用模型下的无限极分类方法获得规则子级
		$ruleRes = db('authRule')->select();
		$ruleTree = model('AuthRule')->ruletree($ruleRes);
		$this->assign([
			'ruleTree' => $ruleTree,
		]);
		return view();
	}

	public function del($id) {

		$cid = model('AuthRule')->childrenids($id);
		$cid[] = $id;
		$del = db('authRule')->delete($cid);
		if ($del) {
			$this->success('删除成功！', 'lst');
		} else {
			$this->error('删除失败！');
		}
	}

}
