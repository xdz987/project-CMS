<?php

namespace app\admin\validate;
use think\validate;

//配置项的中添加和编辑验证规则方法
class Conf extends Validate {
	protected $rule = [
		'cname' => 'require|max:60|unique:conf',
		'ename' => 'require|max:60|unique:conf',
		'dt_type' => 'require|number',
		'cf_type' => 'require|number',
	];

	protected $message = [
		'cname.require' => '中文名称不得为空!',
		'cname.unique' => '中文名称不得重复!',
		'cname.email' => '必须是邮箱格式！',
		'ename.require' => '英文名称不能为空！',
		'ename.unique' => '英文名称不能重复！',
	];

	protected $scene = [
		'add' => ['cname', 'ename', 'dt_type', 'cf_type'],
		'edit' => ['cname', 'ename', 'dt_type', 'cf_type'],
	];
}