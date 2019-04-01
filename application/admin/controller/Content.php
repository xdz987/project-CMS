<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Content extends Common {

	//查询数据发送给前端lst页面
	public function lst() {

		$artRes = db('archives')->field('a.id,a.title,a.attr,c.cate_name,m.model_name,a.model_id,m.table_name')->alias('a')->join('tp_cate c', 'a.cate_id=c.id')->join('tp_model m', 'a.model_id = m.id')->paginate(2);
		//判断是从文档管理还是从栏目管理进来，栏目管理可以添加文章，文档管理没有Add按钮
		$modelId = input('model_id');
		$cateId = input('cate_id');
		if (!$modelId) {
			$modelId = 0;
		}
		$this->assign([
			'modelId' => $modelId,
			'cateId' => $cateId,
			'artRes' => $artRes,
		]);
		//dump($artRes);die;
		return view('lst');
	}

	//接收全段发送过来的文件，目录存入数据库及保存文件
	public function upload($picName) {
		// 获取表单上传文件 例如上传了001.jpg
		$file = request()->file($picName);
		// 移动到框架应用根目录/public/uploads/ 目录下
		if ($file) {
			$info = $file->move(ROOT_PATH . 'public/static/index/uploads/att');
			if ($info) {
				// 成功上传后 获取上传信息
				// 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
				return $info->getSaveName();
			} else {
				// 上传失败获取错误信息
				return $file->getError();
			}
		}
	}

	//查询数据库发送至前端，相对应的模型应当相对应之字段，接收POST过来的数据分别写入主表和副表当中
	public function add() {

		$modelId = input('model_id');
		$cateId = input('cate_id');
		if (!$modelId) {
			$modelId = 0;
		}
		if (request()->isPost()) {
			$data = input('post.');
			$data['cate_id'] = $cateId;
			$columns = array();
			//获取前缀
			$prefix = config('database.prefix');
			$tbArchives = $prefix . 'archives'; //主文章表名称
			$_columns = Db::query("SHOW COLUMNS FROM {$tbArchives}");
			foreach ($_columns as $v) {
				$columns[] = $v['Field'];
			}
			$archives = array();
			$addtable = array(); //附加表
			foreach ($data as $k => $v) {
				if (in_array($k, $columns)) {
					if (is_array($v)) {
						$v = implode(',', $v);
					}
					$archives[$k] = $v;
				} else {
					if (is_array($v)) {
						$v = implode(',', $v);
					}
					$addtable[$k] = $v;
				}
			}
			//附加表单图或者多图上传处理
			if ($_FILES) {
				unset($_FILES['fileselect']);
				foreach ($_FILES as $k => $v) {
					if ($v['name'] != '') {
						$addtable[$k] = $this->upload($k);
					}
				}
			} else {

			}
			$archives['time'] = time();
			$archives['model_id'] = $modelId;
			$tableName = db('model')->field('table_name')->find($modelId); //获取附加表名称
			$tableName = $tableName['table_name'];
			$addArchives = db('archives')->insertGetId($archives); //添加主表数据，并且返回附表id主键
			$addtable['aid'] = $addArchives;
			$_addTable = db($tableName)->insert($addtable); //添加附加表数据
			if ($addArchives && $_addTable) {
				$this->success('添加数据成功！', 'lst');
			} else {
				$this->error('添加数据失败!');
			}

		}
		//获取栏目
		$_cateRes = db('cate')->select();
		$cateRes = model('cate')->catetree($_cateRes);
		//dump($cateRes);die;
		//获取当前模型自定义字段
		$diyFields = db('model_fields')->where(['model_id' => $modelId])->select();
		$longTextFields = db('model_fields')->where(['model_id' => $modelId, 'field_type' => 9])->select();
		$this->assign([
			'cateRes' => $cateRes,
			'modelId' => $modelId,
			'cateId' => $cateId,
			'diyFields' => $diyFields,
			'longTextFields' => $longTextFields,
		]);
		return view();
	}

	//查询主表副表数据发送至前端，同时接收前端edit的POST数据，分别对应修改主表和副表的数据
	public function edit() {

		$modelId = input('model_id');
		$artId = input('art_id');
		if (!$modelId) {
			$modelId = 0;
		}
		//附加表数据
		$models = db('model')->field('table_name')->find($modelId);
		$tableName = $models['table_name'];
		$diyvals = db($tableName)->where(array('aid' => $artId))->find();
		if (request()->isPost()) {
			$data = input('post.');
			$columns = array();
			//获取前缀
			$prefix = config('database.prefix');
			$tbArchives = $prefix . 'archives'; //主文章表名称
			$_columns = Db::query("SHOW COLUMNS FROM {$tbArchives}");
			foreach ($_columns as $v) {
				$columns[] = $v['Field'];
			}
			$archives = array();
			$addtable = array(); //附加表
			foreach ($data as $k => $v) {
				if (in_array($k, $columns)) {
					if (is_array($v)) {
						$v = implode(',', $v);
					}
					$archives[$k] = $v;
				} else {
					if (is_array($v)) {
						$v = implode(',', $v);
					}
					$addtable[$k] = $v;
				}
			}
			//附加表单图或者多图上传处理
			if ($_FILES) {
				unset($_FILES['fileselect']);
				foreach ($_FILES as $k => $v) {
					if ($v['name'] != '') {
						$addtable[$k] = $this->upload($k);
					}

				}
			}
			$saveArchives = db('archives')->update($archives);
			$saveAddtable = db($tableName)->where(array('aid' => $archives['id']))->update($addtable);
			if ($saveArchives !== false && $saveAddtable !== false) {
				$this->success('添加数据成功！');
			} else {
				$this->error('添加数据失败!');
			}
			return;
		}
		//主表数据
		$arts = db('archives')->find($artId);
		//获取栏目
		$_cateRes = db('cate')->select();
		$cateRes = model('cate')->catetree($_cateRes);
		//dump($cateRes);die;
		//获取当前模型自定义字段
		$diyFields = db('model_fields')->where(['model_id' => $modelId])->select();
		$longTextFields = db('model_fields')->where(['model_id' => $modelId, 'field_type' => 9])->select();
		$this->assign([
			'cateRes' => $cateRes,
			'modelId' => $modelId,
			'cateId' => $arts['cate_id'],
			'diyFields' => $diyFields,
			'longTextFields' => $longTextFields,
			'arts' => $arts,
			'diyvals' => $diyvals,
			'aid' => $artId,
		]);
		//dump($diyvals);die;
		return view();
	}

	//ajax异步删除图片
	public function ajaxDelImg() {
		$aid = input('aid');
		$modelId = input('model_id');
		$fieldName = input('field_name');
		$models = db('model')->find($modelId);
		$tableName = $models['table_name'];
		$jl = db($tableName)->where(array('aid' => $aid))->find();
		$imgSrc = INDEXATT . $jl[$fieldName];
		@unlink($imgSrc);
		$save = db($tableName)->where(array('aid' => $aid))->setField($fieldName, '');
		if ($save) {
			echo 1; //删除图片成功
		} else {
			echo 2; //删除图片失败
		}
	}

	//异步上传缩略图，同时对缩略图进行裁剪和谁水印修改
	public function upimg() {
		$file = request()->file('img');
		$info = $file->move(ROOT_PATH . 'public/static/index/uploads/img');
		if ($info) {
			if ($this->config['thumb'] == '是') {
				$thumb_width = $this->config['thumb_width'];
				$thumb_height = $this->config['thumb_height'];
				$water_pos = $this->config['water_pos'];
				$tmd = $this->config['tmd'];
				//缩略图裁剪方式
				switch ($this->config['thumb_way']) {
				case '等比例缩放':
					$thumb_way = 1;
					break;
				case '缩放后填充':
					$thumb_way = 2;
					break;
				case '居中裁剪':
					$thumb_way = 3;
					break;
				case '左上角裁剪':
					$thumb_way = 4;
					break;
				case '右下角裁剪':
					$thumb_way = 5;
					break;
				case '固定尺寸缩放':
					$thumb_way = 6;
					break;

				default:
					$thumb_way = 1;
					break;
				}
				//水印图位置
				switch ($this->config['water_pos']) {
				case '左上角':
					$water_pos = 1;
					break;
				case '上居中':
					$water_pos = 2;
					break;
				case '右上角':
					$water_pos = 3;
					break;
				case '左居中':
					$water_pos = 4;
					break;
				case '居中':
					$water_pos = 5;
					break;
				case '右居中':
					$water_pos = 6;
					break;
				case '左下角':
					$water_pos = 7;
					break;
				case '下居中':
					$water_pos = 8;
					break;
				case '右下角':
					$water_pos = 9;
					break;

				default:
					$water_pos = 1;
					break;
				}
				$imgsrc = INDEXIMG . $info->getSaveName();
				$image = \think\Image::open($imgsrc);
				// $water = INDEXIMG . 'water.jpg'; //水印图片
				$water = ADMIN_STATIC . '/uploads/' . $this->config['water_img'];
				if ($this->config['water'] == '是') {
					//生成缩略图、删除原图以及添加水印
					$image->thumb($thumb_width, $thumb_height, $thumb_way)->water($water, $water_pos, $tmd)->save($imgsrc);
				} else {
					//生成缩略图、删除原图
					$image->thumb($thumb_width, $thumb_height, $thumb_way)->save($imgsrc);
				}
			}
			// 成功上传后 获取上传信息
			// 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
			echo $info->getSaveName();
		} else {
			// 上传失败获取错误信息
			echo $file->getError();
		}
	}

	//ajax删除缩略图
	public function delimg() {
		$artid = input('artid');
		$imgurl = input('imgurl');
		$imgurl = INDEXIMG . $imgurl;
		$res = unlink($imgurl);
		if ($artid) {
			db('archives')->where('id', $artid)->setField('litpic', '');
		}
		if ($res) {
			echo 1; //删除文件成功
		} else {
			echo 2; //删除文件失败
		}
	}

	public function ajaxdel() {
		$artId = input('id');
		$tableName = input('tablename');
		//获取表前缀
		//$prefix = config('database.prefix');
		//$tableName = $prefix . $tableName;
		//删除副表信息
		$result1 = db($tableName)->where('aid', $artId)->delete();
		//删除主表信息
		$result2 = db('archives')->where('id', $artId)->delete();
		if ($result1 && $result2) {
			echo 1;
		} else {
			echo 2;
		}
	}
}
