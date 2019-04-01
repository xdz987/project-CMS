<?php
namespace app\admin\controller;
use app\admin\controller\Common;

//可省略，因为在控制器同一目录下

class Conf extends Common {

	//所有的配置项完成后显示并可编辑保存
	public function conflst() {

		if (request()->isPOST()) {
			//dump(input('post.'));die;
			$data = input('post.');
			$enameArr = db('conf')->column('ename');
			$confArr = array();
			//附件类型数据处理
			$imgcolumn = db('conf')->where('dt_type', 6)->column('ename');
			foreach ($data as $k => $v) {
				$confArr[] = $k;
				if (is_array($v)) {
					$v = implode(',', $v);
				}
				db('conf')->where('ename', $k)->update(['value' => $v]);
			}
			foreach ($enameArr as $k => $v) {
				if (!in_array($v, $confArr) && !in_array($v, $imgcolumn)) {
					db('conf')->where('ename', $v)->update(['value' => '']);
				}
			}
			foreach ($imgcolumn as $k => $v) {
				if ($_FILES[$v]['tmp_name'] != '') {
					$file = request()->file($v);
					$info = $file->move(ROOT_PATH . 'public/static/admin/uploads');
					$imgSrc = $info->getSaveName();
					if ($imgSrc != '') {
						db('conf')->where('ename', $v)->update(['value' => $imgSrc]);
					}
				}
			}
			$this->success('修改配置成功!', url('conflst'));
			return;
		}
		$confRes = db('conf')->select();
		$this->assign('confRes', $confRes);
		return view();
	}

	//配置项数据发送至前端，前端分别单独显示
	public function lst() {

		$confRes = db('conf')->field('id,cname,ename,value,values')->paginate(6);
		$this->assign('confRes', $confRes);
		return view();
	}

	//添加配置项
	public function add() {

		if (request()->isPOST()) {
			$data = input('post.');
			$validate = validate('conf');
			if (!$validate->scene('add')->check($data)) {
				$this->error($validate->getError());
			}
			$add = db('conf')->insert($data);
			if ($add) {
				$this->success('添加配置项成功！ ', url('lst'));
			} else {
				$this->error('添加配置项失败！');
			}
		}
		return view();
	}

	//编辑单独的配置项，将id对应的配置项进行查询，发送至前端进行编辑
	public function edit($id) {
		if (request()->isPOST()) {
			$data = input('post.');
			$validate = validate('conf');
			if (!$validate->scene('edit')->check($data)) {
				$this->error($validate->getError());
			}
			$save = db('conf')->update($data);
			if ($save) {
				$this->success('修改配置成功！', url('lst'));
			} else {
				$this->error('修改配置失败！');
			}
		}
		$confs = db('conf')->find($id);
		$this->assign('confs', $confs);
		return view();
	}

	//删除配置项，接收前端发送过来的id，实施数据库删除操作
	public function del($id) {
		$del = db('conf')->delete($id);
		if ($del) {
			$this->success("删除配置项成功！", url('lst'));
		} else {
			$this->error('删除配置失败！');
		}
	}
}