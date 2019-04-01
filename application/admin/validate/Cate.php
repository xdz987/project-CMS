<?php

namespace app\admin\validate;
use think\validate;

class Cate extends Validate {
	protected $rule = [
		'model_id' => 'require|number',
		'pid' => 'require|number',
		'cate_name' => 'require|unique:cate',
		'cate_attr' => 'require|number',
		'title' => 'require',
		'keywords' => 'require',
		'desc' => 'require',
	];

	protected $message = [
		'cate_name.require' => '栏目名称不能为空！',
		'cate_name.unique' => '栏目名称不得重复!',
		'cate_attr.require' => '栏目属性不能为空！',
		'cate_attr.number' => '栏目属性必须是数字！',
		'pid.number' => '上级栏目必须是数字！',
		'pid.unique' => '上级栏目不能为空！',
		'model_id.number' => '模型id必须是数字！',
		'model_id.unique' => '模型名称不能为空！',
		'title.require' => '栏目标题不能为空！',
		'keywords.require' => '关键词不能为空！',
		'desc.require' => '描述不能为空！',
	];

	protected $scene = [
		'add' => ['model_id', 'pid', 'cate_name', 'cate_attr', 'title', 'keywords', 'desc'],
		'edit' => ['model_id', 'pid', 'cate_name', 'cate_attr'],
	];
}