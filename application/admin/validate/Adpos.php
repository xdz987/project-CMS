<?php

namespace app\admin\validate;
use think\validate;

class Adpos extends Validate {
	protected $rule = [
		'name' => 'require|unique:adpos',
		'width' => 'require|number',
		'height' => 'require|number',
	];

	protected $message = [
		'name.require' => '广告位名称不能为空！',
		'name.unique' => '广告位名称不得重复!',
		'width.require' => '广告位宽度不能为空！',
		'width.number' => '广告位宽度必须是数字！',
		'height.number' => '广告位高度必须是数字！',
		'height.unique' => '广告位高度不能为空！',
	];

	protected $scene = [
		'add' => ['name', 'width', 'height'],
		'edit' => ['name', 'width', 'height'],
	];
}