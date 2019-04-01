<?php
namespace app\admin\controller;
use app\admin\controller\Common;

class Ad extends Common {

	//连表查询数据表发送至前端显示
	public function lst() {

		//获取前缀
		$prefix = config('database.prefix');
		$adposName = $prefix . 'adpos';
		$adRes = db('ad')->field('a.*,b.name')->alias('a')->join("$adposName b", "a.adpos_id = b.id")->paginate(5);
		$page = $adRes->render();
		$this->assign([
			'adRes' => $adRes,
			'page' => $page,
		]);
		return view('list');
	}

	//根据选择的广告位作为id插入ad数据表
	public function add() {

		if (request()->isPost()) {
			$data = input('post.');
			//验证
			$validate = validate('ad');
			if (!$validate->scene('add')->check($data)) {
				$this->error($validate->getError());
			}
			//使用模型事件，将数据data传入事件
			$ad = model('ad')->data($data);
			$add = $ad->save();
			if ($add) {
				$this->success('添加广告成功！', 'lst');
			} else {
				$this->error('添加广告失败！');
			}
		}
		$adposRes = db('adpos')->field('id,name')->select();
		$this->assign([
			'adposRes' => $adposRes,
		]);
		return view('add');
	}

	//编辑广告信息，修改数据表
	public function edit($id) {

		if (request()->isPost()) {
			$data = input('post.');
			//验证
			$validate = validate('ad');
			if (!$validate->scene('edit')->check($data)) {
				$this->error($validate->getError());
			}
			$ad = model('ad');
			$save = $ad->update($data);
			if ($save !== false) {
				$this->success('修改广告成功！', 'lst');
			} else {
				$this->error('修改广告失败');
			}
		}
		$adposRes = db('adpos')->field('id,name')->select();
		$ad = db('ad')->find($id);
		//如果不是轮换类型传个空值，防止未定义变量
		$adflashRes = '';
		if ($ad['type'] == 2) {
			$adflashRes = db('adflash')->where(array('ad_id' => $id))->select();
		}
		$this->assign([
			//编辑轮换广告的时候显示轮换广告对应的广告数据
			'adflashRes' => $adflashRes,
		]);
		$this->assign([
			'ad' => $ad,
			'adposRes' => $adposRes,
		]);
		return view();
	}

	public function del($id) {
		$adpos = model('ad');
		$del = $adpos::destroy($id);
		if ($del) {
			$this->success('删除广告位成功！', 'lst');
		} else {
			$this->error('删除广告位失败！');
		}
	}

	public function changeStatus() {
		$id = input('id');
		$adposId = input('adposId');
		$ads = db('ad')->find($id);
		if ($ads['on'] == 0) {
			db('ad')->where('adpos_id', $adposId)->update(['on' => 0]);
			db('ad')->where('id', $id)->setField('on', 1);
		} else {
			db('ad')->where('id', $id)->setField('on', 0);
		}
	}

	//异步删除轮换广告记录
	public function ajaxdel() {
		$id = input('id');
		$adflash = db('adflash')->find($id);
		$imgSrc = $adflash['fimg_src'];
		$imgSrc = INDEXAD . $imgSrc;
		if (file_exists($imgSrc)) {
			@unlink($imgSrc);
		}
		$del = db('adflash')->delete($id);
		if ($del) {
			echo 1; //删除成功
		} else {
			echo 2; //删除失败
		}
	}
}
