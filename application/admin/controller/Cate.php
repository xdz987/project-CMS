<?php
namespace app\admin\controller;
use app\admin\controller\Common;

//可省略，因为同意控制器下同一目录

class Cate extends Common {

	//查询所有栏目数据，并按照相应栏目父子关系排序，发送排好顺序给前端，前端根据pid(父)排序
	public function lst() {

		//获取前缀
		$prefix = config('database.prefix');
		$modelName = $prefix . 'model';
		//连表查询
		//获取栏目
		$_cateRes = db('cate')->alias('a')->field('a.*,b.model_name')->join("$modelName b", 'a.model_id = b.id')->order('sort desc')->select();
		$cateRes = model('cate')->catetree($_cateRes);
		$this->assign([
			'cateRes' => $cateRes,
		]);
		return view();
	}

	//前端ajax请求栏目下的所有子栏目，调用model下的Cate模型下的childrenids方法获取子栏目id
	public function ajaxlst() {
		if (request()->isAjax()) {
			$cateid = input('cateid');
			$sonids = model('cate')->childrenids($cateid);
			echo json_encode($sonids);
		} else {
			$this->error('非法操作！');
		}
	}

	//向前端栏目发送栏目和模型列表数据，接收前端发送过来的数据判断后写入数据库
	public function add() {
		if (request()->isPost()) {
			$data = input('post.');
			$validate = validate('cate');
			if (!$validate->scene('add')->check($data)) {
				$this->error($validate->getError());
			}
			$add = db('cate')->insert($data);
			if ($add) {
				$this->success('添加栏目成功!', url('lst'));

			} else {
				$this->error('添加栏目失败！');
			}
			return;
		}

		//获取栏目
		$_cateRes = db('cate')->select();
		$cateRes = model('cate')->catetree($_cateRes);
		//接收栏目id
		$cateid = input('cateid');
		//模型信息
		$modelRes = db('model')->field('id,model_name')->select();
		$this->assign(array(
			'cateRes' => $cateRes,
			'cateid' => $cateid,
			'modelRes' => $modelRes,
		));
		return view();
	}

	//发送栏目和模型数据至前端，数据判断验证POST提交过来的数据，同时修改数据库
	public function edit() {

		if (request()->isPost()) {
			$data = input('post.');
			//判断是否隐藏栏目
			$_data = array();
			foreach ($data as $k => $v) {
				$_data[] = $k;
			}
			if (!in_array('status', $_data)) {
				$data['status'] = 1;
			}
			//验证
			$validate = validate('cate');
			if (!$validate->scene('edit')->check($data)) {
				$this->error($validate->getError());
			}
			//执行栏目修改操作
			$save = db('cate')->update($data);
			if ($save !== false) {
				$this->success('修改栏目成功!', url('lst'));

			} else {
				$this->error('修改栏目失败！');
			}
			return;
		}
		//获取栏目
		$_cateRes = db('cate')->select();
		$cateRes = model('cate')->catetree($_cateRes);
		//接收栏目id
		$cateid = input('cateid');
		$cates = db('cate')->find($cateid);
		//模型信息
		$modelRes = db('model')->field('id,model_name')->select();
		$this->assign(array(
			'cateRes' => $cateRes,
			'cates' => $cates,
			'modelRes' => $modelRes,
		));
		return view();
	}

	//接收前端发送过来的图片和信息，移入相应目录并将目录信息写入数据库
	public function upimg() {
		$file = request()->file('img');
		$info = $file->move(ROOT_PATH . 'public/static/admin/uploads/cateimg');
		if ($info) {
			// 成功上传后 获取上传信息
			// 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
			echo $info->getSaveName();
		} else {
			// 上传失败获取错误信息
			echo $file->getError();
		}
	}

	//ajax异步修改栏目显示状态，异步显示父栏目和子栏目
	public function changestatus() {
		if (request()->isAjax()) {
			$cateid = input('cateid');
			$status = db('cate')->field('status')->where('id', $cateid)->find();
			$status = $status['status'];
			if ($status == 1) {
				db('cate')->where('id', $cateid)->update(['status' => 0]);
				echo 1; //由显示改为隐藏
			} else {
				db('cate')->where('id', $cateid)->update(['status' => 1]);
				echo 2; //由隐藏改为显示
			}
		} else {
			$this->error("非法操作!");
		}
	}

	//lst页面的action目标方法，当前端的checkbox框选中时，调用model模型下的Cate模块下的pdel方法，批量删除父子栏目数据
	public function del_sort() {
		$data = input('post.');
		// foreach ($data['sort'] as $k => $v) {
		// 	db('cate')->where('id', $k)->update(['sort' => $v]);
		// }
		if ($data['itm']) {
			model('cate')->pdel($data['itm']);
		}
		$this->success('数据处理成功！', url('lst'));
	}

	//前端lst的删除按钮，删除栏目及其子栏目数据
	public function del() {
		$cateid = input('cateid');
		$childrenids = model('cate')->childrenids($cateid);
		$childrenids[] = (int) $cateid;
		$del = db('cate')->delete($childrenids);
		if ($del) {
			$this->success('删除栏目成功！', url('lst'));
		} else {
			$this->error('删除栏目失败！');
		}
	}

	//前端edit页面删除图片按钮，结合ajax异步请求，删除数据库及其图片文件
	public function delimg() {
		$cateid = input('cateid');
		$imgurl = input('imgurl');
		$imgurl = ADMINIMG . $imgurl;
		$res = unlink($imgurl);
		if ($cateid) {
			db('cate')->where('id', $cateid)->setField('img', '');
		}
		if ($res) {
			echo 1; //删除文件成功
		} else {
			echo 2; //删除文件失败
		}
	}

	//前端edit页面，查询相对应的栏目信息，将将相对应的模板信息(list_tmp等)用ajax实现变换便于编辑数据
	public function ajaxcateinfo() {
		$cateid = input('cateid');
		$data = db('cate')->find($cateid);
		echo json_encode($data);
	}
}