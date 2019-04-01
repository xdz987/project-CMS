<?php
namespace app\admin\model;
use think\Model;

class Adpos extends Model {

	protected static function init() {

		//删除广告位前置事件
		Adpos::beforeDelete(function ($adpos) {

			$adposid = input('id');
			Ad::destroy(['adpos_id' => $adposid]);
		});
	}

}