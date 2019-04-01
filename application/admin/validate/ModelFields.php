<?php

namespace app\admin\validate;
use think\validate;

class ModelFields extends Validate {
	protected $rule = [
		'model_id' => 'require',
		'field_type' => 'require',
		'field_cname' => 'require|unique:model_fields',
		'field_ename' => 'require|unique:model_fields',
		'status' => 'require|number',
	];

	protected $message = [
		'model_id.require' => '所属模型不能为空！',
		'field_type.unique' => '字段类型不能为空!',
		'field_cname.unique' => '字段中文名称不能重复！',
		'field_cname.require' => '字段中文名称不能为空！',
		'field_ename.unique' => '字段英文名称不能重复！',
		'field_ename.require' => '字段英文名称不能为空！',
	];

	protected $scene = [
		'add' => ['model_id', 'field_type', 'field_cname', 'field_ename'],
		'edit' => ['field_type', 'field_cname', 'field_ename'],
	];
}