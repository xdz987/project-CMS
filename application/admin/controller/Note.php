<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use QL\QueryList;

//可省略

class Note extends Common {

	//查询连表查询note和model表发送至前端Lst页面
	public function noteList() {

		//获取前缀
		$prefix = config('database.prefix');
		$modelName = $prefix . 'model';
		$noteRes = db('note')->field('n.id,n.note_name,n.model_id,n.output_encoding,n.input_encoding,n.addtime,n.lasttime,m.model_name')->alias('n')->join("$modelName m", 'n.model_id=m.id')->paginate(4);
		$this->assign([
			'noteRes' => $noteRes,
		]);
		return view('list');
	}

	//添加采集节点规则分两步进行，第一步是采集列表将接收的列表采集规则写入数据库
	public function addlistrules() {
		if (request()->isPost()) {
			$_data = input('post.');
			$data = array();
			$data['note_name'] = $_data['note_name'];
			$data['model_id'] = $_data['model_id'];
			$data['output_encoding'] = $_data['output_encoding'];
			$data['input_encoding'] = $_data['input_encoding'];
			$data['list_rules'] = array(
				'list_url' => $_data['list_url'],
				'start_page' => $_data['start_page'],
				'end_page' => $_data['end_page'],
				'step' => $_data['step'],
				'range' => $_data['range'],
				'list_rules' => array(
					'url' => $_data['url'],
					'litpic' => $_data['litpic'],
				),
			);
			//列表页面采集规则
			$data['list_rules'] = json_encode($data['list_rules']);
			$data['addtime'] = time();
			//新添加数据并获取id主键
			$id = db('note')->insertGetId($data);
			if ($id) {
				$notes = db('note')->field('model_id')->find($id);
				$modelId = $notes['model_id'];
				$this->redirect('Note/additemrules', ['model_id' => $modelId, 'note_id' => $id]);
			} else {
				$this->error('添加节点失败！');
			}
		}
		$modelRes = db('model')->select();
		$this->assign([
			'modelRes' => $modelRes,
		]);
		return view();
	}

	//添加采集节点规则分两步进行，第二步是内容采集规则配置，写入相对应的数据表
	public function additemrules() {

		$noteId = input('note_id');
		if (request()->isPost()) {
			$data = input('post.');
			$rules = array();
			if ($data) {
				foreach ($data as $k => $v) {
					$rules[$k][0] = $v[0];
					$rules[$k][1] = $v[1];
					$rules[$k][2] = $v[2];
					array_values($rules[$k]);
				}
			}
			$rules = json_encode($rules);
			$save = db('note')->where(array('id' => $noteId))->update(['item_rules' => $rules]);
			if ($save) {
				$this->success('添加节点成功！', 'noteList');
			} else {
				$this->error('添加节点失败！');
			}
			return;
		}
		//自定义模型字段
		$modelId = input('model_id');
		$modelFieldRes = db('model_fields')->field('field_cname,field_ename')->where(array('model_id' => $modelId))->select();
		$this->assign([
			'modelFieldRes' => $modelFieldRes,
		]);
		return view();
	}

	//展示采集页面
	public function showcaiji($id) {

		//获取栏目
		$_cateRes = db('cate')->select();
		$cateRes = model('cate')->catetree($_cateRes);
		$notes = db('note')->field('model_id,note_name')->find($id);
		$modelId = $notes['model_id'];
		$noteName = $notes['note_name'];
		$this->assign([
			'id' => $id,
			'cateRes' => $cateRes,
			'modelId' => $modelId,
			'noteName' => $noteName,
		]);
		return view();
	}

	//根据前端notelist选择的相对应节点进行采集，使用querylistV4版本工具分为两步走，第一步采集列表数据,
	//第二步根据采集到的列表数据和数据表设定好的内规则再采集内容
	//将采集到的数据分别写入临时表html中（方便后面导入）和sucai表中
	public function doCaiji($id) {
		$notes = db('note')->find($id);
		//采集参数
		//输出编码
		$outputEncoding = $notes['output_encoding'];
		//输入编码
		$inputEncoding = $notes['input_encoding'];
		//列表采集配置
		$listRules = $notes['list_rules'];
		//json转换为数组
		$listRules = json_decode($listRules, true);

		//内容页采集配置
		$itemRules = $notes['item_rules'];
		//json转换为数组
		$itemRules = json_decode($itemRules, true);
		//采集列表网址
		$listUrl = $listRules['list_url'];
		//采集开始页码
		$startPage = $listRules['start_page'];
		//采集结束页码
		$endPage = $listRules['end_page'];
		//采集步长
		$step = $listRules['step'];
		//采集范围
		$range = $listRules['range'];
		//采集规则
		$listcaijiRules = $listRules['list_rules'];
		//动态读取列表采集规则
		$listcaijiRules1 = array(
			'url' => array($listcaijiRules['url'], 'href'),
		);
		$listcaijiRules2 = array(
			//之所以分两次采集是因为采集出的url的array会跳1个位置，和litpic不匹配
			//针对包图网的数据缩略图为data-url
			'litpic' => array($listcaijiRules['litpic'], 'data-url'),
		);
		$_listUrl = []; //转换为实际页码的列表网址
		//处理采集列表网址
		for ($i = $startPage; $i <= $endPage; $i++) {
			$_listUrl[] = str_replace('(*)', $i, $listUrl);
		}
		$datalist = [];
		$rtt = []; //用来去除空url
		//循环采集列表数据
		foreach ($_listUrl as $k => $url) {
			//有的网站被protect了，选择采集对象要测试，记得转码
			// rt 不使用queryData语法糖是意难忘要使用flatten转换二维数组为一维（去掉了key值）
			$rt = QueryList::get($url)->rules($listcaijiRules1)->encoding($outputEncoding, $inputEncoding)->queryData();
			$rt2 = QueryList::get($url)->rules($listcaijiRules2)->encoding($outputEncoding, $inputEncoding)->queryData();
			//print_r($rt2);
			//去除空的url
			foreach ($rt as $k1 => $v1) {
				if ($v1['url'] != '') {
					$rtt[] = $v1;
				}
			}
			//将url和litpic合为二维数组
			for ($i = 0; $i < sizeof($rtt); $i++) {
				$dataList[$i] = $rtt[$i] + $rt2[$i];
			}
		}
		//内容数据采结果集
		$_dataItem = [];
		foreach ($dataList as $k => $v) {
			//手动添加url到数组，目的是写入临时表中
			$_dataItem[] = QueryList::get($v['url'])->rules($itemRules)->encoding($outputEncoding, $inputEncoding)->queryData();
			$_dataItem[$k][0]['url'] = $v['url'];
			$_dataItem[$k][0]['litpic'] = $v['litpic'];
		}
		//数组重构，二维变一维，列表采集数据结果集
		$dataItem = [];
		foreach ($_dataItem as $k => $v) {
			foreach ($v as $k1 => $v1) {
				$dataItem[] = $v1;
			}
		}
		//将数据写入临时表
		$data = [];
		foreach ($dataItem as $k => $v) {
			$data['nid'] = $id;
			$data['title'] = $v['title'];
			//防止重复采集
			$reData = db('html')->where(array('title' => $data['title']))->find();
			if ($reData) {
				continue;
			}
			$data['url'] = $v['url'];
			$data['addtime'] = time();
			$data['result'] = json_encode($v);
			db('html')->insert($data);
		}
		//节点最后采集时间
		db('note')->where(array('id' => $id))->update(['lasttime' => time()]);
		echo 1; //采集完成
	}

	//从html临时表中采集的数据分配之前模型的表中（表是属于栏目），例如(sucai表)，例如包图网列表中每个的编号 格式 和体积。并设置为已导出1
	public function exportdata() {

		$noteId = input('id'); //当前节点id
		$cateId = input('cate_id'); //要导出数据所属栏目
		$cate = db('cate')->field('model_id')->find($cateId);
		$modelId = $cate['model_id']; //动态获取模型id
		$model = db('model')->field('table_name')->find($modelId);
		$tableName = $model['table_name'];
		//要导出的数据
		$data = db('html')->field('id,export,result')->where(array('export' => 0, 'nid' => $noteId))->select();
		$arr = array('title', 'keywords', 'description', 'writer', 'source', 'content', 'litpic', 'url');
		$_archive = []; //主表元素数组
		$_addTable = []; //附加表元素数组
		$i = 0;
		foreach ($data as $k => $v) {
			$_data = json_decode($v['result'], true);
			foreach ($_data as $k1 => $v1) {
				if (in_array($k1, $arr)) {
					$_archive[$k1] = $v1;
					if ($k1 == 'url') {
						unset($_archive[$k1]);
					}
				} else {
					$_addTable[$k1] = $v1;
				}
			}
			$_archive['cate_id'] = $cateId;
			$_archive['model_id'] = $modelId;
			$addId = db('archives')->insertGetId($_archive);
			$_addTable['aid'] = $addId;
			db($tableName)->insert($_addTable);
			db('html')->where(array('id' => $v['id']))->update(['export' => 1]);
			//批量导出
		}
		echo 1; //数据导出完成
	}

	//通过节点id查询相对应的数据库发送至前端页面，编辑节点信息修改数据库
	public function edit($id) {
		/*$notes = db('note')->where(array('id' => 21))->find();
			$listRules = json_decode($notes['list_rules'], true);
			$itemRules = json_decode($notes['item_rules'], true);
			print_r($listRules);
			print_r($itemRules);
		*/

		if (request()->isPost()) {
			$data = input('post.');
			$base = [];
			$base['id'] = $data['id'];
			$base['note_name'] = $data['note_name'];
			$base['model_id'] = $data['model_id'];
			$base['output_encoding'] = $data['output_encoding'];
			$base['input_encoding'] = $data['input_encoding'];
			$base['list_rules']['list_url'] = $data['list_url'];
			$base['list_rules']['start_page'] = $data['start_page'];
			$base['list_rules']['end_page'] = $data['end_page'];
			$base['list_rules']['step'] = $data['step'];
			$base['list_rules']['range'] = $data['range'];
			$base['list_rules']['list_rules']['url'] = $data['url'];
			$base['list_rules']['list_rules']['litpic'] = $data['litpic'];
			$base['list_rules'] = json_encode($base['list_rules']);
			$arr = array('id', 'note_name', 'model_id', 'output_encoding', 'input_encoding', 'list_url', 'start_page', 'end_page', 'step', 'range', 'url', 'litpic');
			foreach ($data as $k => $v) {
				if (in_array($k, $arr)) {
					unset($data[$k]);
				}
			}
			$base['item_rules'] = json_encode($data);
			$save = db('note')->update($base);
			if ($save !== false) {
				$this->success('修改节点成功！', 'noteList');
			} else {
				$this->error('修改节点失败！');
			}
			return;
		}
		//通过节点id获取节点所属模型的id
		$notes = db('note')->find($id);
		$listRules = json_decode($notes['list_rules'], true);
		$itemRules = json_decode($notes['item_rules'], true);
		$modelId = $notes['model_id'];
		//根据模型id获取模型名称
		$models = db('model')->field('model_name')->find($modelId);
		$modelName = $models['model_name'];
		$modelRes = db('model')->select();
		$modelFieldRes = db('model_fields')->field('field_cname,field_ename')->where(array('model_id' => $modelId))->select();
		$this->assign([
			'modelRes' => $modelRes,
			'modelFieldRes' => $modelFieldRes,
			'notes' => $notes,
			'modelId' => $modelId,
			'modelName' => $modelName,
			'listRules' => $listRules,
			'itemRules' => $itemRules,
			'id' => $id, //节点id
		]);
		return view();
	}

	//删除节点
	public function del($id) {
		$del = db('note')->delete($id);
		if ($del) {
			db('html')->where(array('nid' => $id))->delete();
			$this->success('删除节点成功！', 'noteList');
		} else {
			$this->error('删除节点失败！');
		}
	}

}