<?php
namespace app\admin\model;
use think\Model;

class Cate extends Model {

	//对目录树进行排序
	public function catetree($cateRes) {

		return $this->sort($cateRes);
	}

	//调用递归排序，同时建立level等级标记
	public function sort($cateRes, $pid = 0, $level = 0) {

		static $arr = array();
		foreach ($cateRes as $k => $v) {
			if ($v['pid'] == $pid) {
				$v['level'] = $level;
				$arr[] = $v;
				$this->sort($cateRes, $v['id'], $level + 1);
			}
		}
		return $arr;
	}

	//获取子栏目id
	public function childrenids($cateid) {
		$data = $this->field('id,pid')->select();
		return $this->_childrenids($data, $cateid);
	}

	//调用递归获取子栏目
	private function _childrenids($data, $cateid) {
		static $arr = array();
		foreach ($data as $k => $v) {
			if ($v['pid'] == $cateid) {
				$arr[] = $v['id'];
				$this->_childrenids($data, $v['id']);
			}
		}
		return $arr;
	}

	//处理批量删除，删除父栏目和子栏目
	public function pdel($cateids) {
		foreach ($cateids as $k => $v) {
			$childrenidsarr[] = $this->childrenids($v);
			$childrenidsarr[] = (int) $v;
		}
		$_childrenidsarr = array();
		foreach ($childrenidsarr as $k => $v) {
			if (is_array($v)) {
				foreach ($v as $k1 => $v1) {
					$_childrenidsarr[] = $v1;
				}
			} else {
				$_childrenidsarr[] = $v;
			}
		}
		$_childrenidsarr = array_unique($_childrenidsarr);
		$this::destroy($_childrenidsarr);
	}
}