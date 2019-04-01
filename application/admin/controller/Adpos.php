<?php
namespace app\admin\controller;
use app\admin\controller\Common;

class Adpos extends Common {

	//查询数据库发送至前端
	public function lst() {

		$adposRes = db('adpos')->paginate(2);
		$page = $adposRes->render();
		$this->assign([
			'adposRes' => $adposRes,
			'page' => $page,
		]);
		return view('list');
	}

	//添加广告位信息插入数据库中
	public function add() {

		if (request()->isPost()) {
			$data = input('post.');
			$validate = validate('adpos');
			if (!$validate->scene('add')->check($data)) {
				$this->error($validate->getError());
			}
			$add = db('adpos')->insert($data);
			if ($add) {
				$this->success('添加广告位成功！', 'lst');
			} else {
				$this->error('添加广告位失败！');
			}
		}
		return view('add');
	}

	//修改发送过来的相对应数据表中的广告位数据
	public function edit($id) {

		if (request()->isPost()) {
			$data = input('post.');
			$validate = validate('adpos');
			if (!$validate->scene('edit')->check($data)) {
				$this->error($validate->getError());
			}
			$save = db('adpos')->update($data);
			if ($save) {
				$this->success('修改广告位成功！', 'lst');
			} else {
				$this->error('修改广告位失败');
			}
			return view();
		}
		$adpos = db('adpos')->find($id);
		$this->assign([
			'adpos' => $adpos,
		]);
		return view();
	}

	// //异步删除广告位信息
	// public function ajaxdel() {

	// 	$id = input('id');
	// 	$del = db('adpos')->delete($id);
	// 	if ($del) {
	// 		echo 1;
	// 	} else {
	// 		echo 2;
	// 	}
	// }
	public function del($id) {
		$adpos = model('adpos');
		$del = $adpos::destroy($id);
		if ($del) {
			$this->success('删除广告位成功！', 'lst');
		} else {
			$this->error('删除广告位失败！');
		}
	}
}
