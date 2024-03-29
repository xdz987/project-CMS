<?php
namespace app\admin\model;
use think\Model;

class AuthRule extends Model {

	//对目录树进行排序
	public function ruletree($ruleRes) {

		return $this->sort($ruleRes);
	}

	//调用递归排序，同时建立level等级标记
	public function sort($ruleRes, $pid = 0, $level = 0) {

		static $arr = array();
		foreach ($ruleRes as $k => $v) {
			if ($v['pid'] == $pid) {
				$v['level'] = $level;
				$arr[] = $v;
				$this->sort($ruleRes, $v['id'], $level + 1);
			}
		}
		return $arr;
	}

	//获取子栏目id
	public function childrenids($ruleid) {
		$data = $this->field('id,pid')->select();
		return $this->_childrenids($data, $ruleid);
	}

	//调用递归获取子栏目
	private function _childrenids($data, $ruleid) {
		static $arr = array();
		foreach ($data as $k => $v) {
			if ($v['pid'] == $ruleid) {
				$arr[] = $v['id'];
				$this->_childrenids($data, $v['id']);
			}
		}
		return $arr;
	}
}