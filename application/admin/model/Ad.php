<?php
namespace app\admin\model;
use think\Model;

class Ad extends Model {

	//忽略不是本表的字段
	protected $field = true;

	protected static function init() {

		//前置事件
		Ad::beforeInsert(function ($ad) {
			$data = input('post.');
			if ($data['type'] == 1) {
				if ($_FILES['img_src']['tmp_name']) {
					// 获取表单上传文件 例如上传了001.jpg
					$file = request()->file('img_src');
					// 移动到框架应用根目录/public/uploads/ 目录下
					$info = $file->move(ROOT_PATH . 'public/static/index' . DS . 'ad');
					if ($info) {
						// 成功上传后 获取上传信息
						// 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
						$imgSrc = $info->getSaveName();
						$ad['img_src'] = $imgSrc;
					} else {
						// 上传失败获取错误信息
						echo $file->getError();
					}
				}
			}
			if ($data['on'] == 1) {
				db('ad')->where(array('adpos_id' => $data['adpos_id']))->update(['on' => 0]);
			}
		});

		//后置事件
		Ad::afterInsert(function ($ad) {
			$data = input('post.');
			if ($data['type'] == 2) {
				foreach ($_FILES['fimg_src']['tmp_name'] as $k => $v) {
					if (!$v) {
						unset($data['flink'][$k]);
					}
				}
				sort($data['flink']);
				// 获取表单上传文件
				$files = request()->file('fimg_src');
				foreach ($files as $k => $file) {
					// 移动到框架应用根目录/public/uploads/ 目录下
					$info = $file->move(ROOT_PATH . 'public/static/index' . DS . 'ad');
					if ($info) {
						// 成功上传后 获取上传信息
						$arr = array();
						$arr['ad_id'] = $ad->id;
						$arr['fimg_src'] = $info->getSaveName();
						$arr['flink'] = $data['flink'][$k];
						db('adflash')->insert($arr);
					} else {
						// 上传失败获取错误信息
						echo $file->getError();
					}
				}
			}
		});

		//删除广告前置事件
		Ad::beforeDelete(function ($ad) {
			$aid = $ad->data['id'];
			$ads = Ad::find($aid);
			if ($ads['type'] == 1) {
				$imgSrc = $ads['img_src'];
				$imgSrc = INDEXAD . $imgSrc;
				if (file_exists($imgSrc)) {
					@unlink($imgSrc);
				}
			} else {
				$fimgSrcRes = db('adflash')->field('fimg_src')->where(array('ad_id' => $aid))->select();
				foreach ($fimgSrcRes as $k => $v) {
					$fimgSrc = INDEXAD . $v['fimg_src'];
					if (file_exists($fimgSrc)) {
						@unlink($fimgSrc);
					}
				}
				db('adflash')->where(array('ad_id' => $aid))->delete();
			}
		});

		//修改广告的前置事件
		Ad::beforeUpdate(function ($ad) {
			$data = input('post.');
			//如果类型为图片
			if ($data['type'] == 1) {
				if ($_FILES['img_src']['tmp_name']) {
					$oldimgsrc = INDEXAD . $data['oldimgsrc'];
					if (file_exists($oldimgsrc)) {
						@unlink($oldimgsrc);
					}
					$file = request()->file('img_src');
					// 移动到框架应用根目录/public/uploads/ 目录下
					$info = $file->move(ROOT_PATH . 'public/static/index' . DS . 'ad');
					if ($info) {
						// 成功上传后 获取上传信息
						// 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
						$imgSrc = $info->getSaveName();
						Ad::where('id', $data['id'])->update(['img_src' => $imgSrc]);
					} else {
						// 上传失败获取错误信息
						echo $file->getError();
					}
				}
				//启用状态
				if ($data['on'] == 1) {
					Ad::where('adpos_id', $data['adpos_id'])->update(['on' => 0]);
					//之所以再将广告位下所有的广告设为关闭后，不需要将对于的on再开启是，是因为这里的更新数据是在Update之前所有这里更新完后，回到控制器后将提交的数据进行更新
					//Ad::where('id', $data['id'])->update(['on' => 1]);
				}
			} else {
				//判断有没有新上传文件
				if (isset($_FILES['fimg_src']['tmp_name'])) {
					if ($_FILES['fimg_src']) {
						foreach ($_FILES['fimg_src']['tmp_name'] as $k => $v) {
							if (!$v) {
								//之所以加一是因为$_FILES['fimg_src']['tmp_name']的第一个是老图片，所有为空，但其实是老数据，所以-1跳过第一个文件
								unset($data['flink'][$k - 1]);
							}
						}
					}
					sort($data['flink']);
					// 获取表单上传文件
					$files = request()->file('fimg_src');
					foreach ($files as $k => $file) {
						// 移动到框架应用根目录/public/uploads/ 目录下
						$info = $file->move(ROOT_PATH . 'public/static/index' . DS . 'ad');
						if ($info) {
							// 成功上传后 获取上传信息
							$arr = array();
							$arr['ad_id'] = $data['id'];
							$arr['fimg_src'] = $info->getSaveName();
							$arr['flink'] = $data['flink'][$k];
							db('adflash')->insert($arr);
						} else {
							// 上传失败获取错误信息
							echo $file->getError();
						}
					}
				}
				//修改链接
				if (isset($data['old_flink'])) {
					foreach ($data['old_flink'] as $k => $v) {
						db('adflash')->where(array('id' => $k))->update(['flink' => $v]);
					}
				}
			}
		});
	}

}