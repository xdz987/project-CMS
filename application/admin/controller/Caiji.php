<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use QL\QueryList;

//可省略

class Caiji extends Common {

	public function index() {

		/*$rules = [
				"title" => array('.clearfix .list_con .title h2', 'text'),
				"url" => array('.unit h1 a', 'href'),
					"desc" => array('.unit dl dd', 'text'),

			];
			$hj = QueryList::get('https://www.csdn.net/nav/mobile')->rules($rules)->queryData();
		*/

		$data = QueryList::get('https://www.baidu.com/s?wd=QueryList')
		// 设置采集规则
			->rules([
				'title' => array('h3', 'text'),
				'link' => array('h3>a', 'href'),
			])
			->queryData();

		print_r($data);

	}
}