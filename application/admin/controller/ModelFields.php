<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class ModelFields extends Common {

	//查询数据，发送给前端列表数据
	public function lst() {

		//获取前缀
		$prefix = config('database.prefix');
		$modelName = $prefix . 'model';
		//连表查询
		$fieldRes = db('model_fields')->field('a.*,b.model_name')->alias('a')->join("$modelName b", 'a.model_id=b.id')->paginate(2);
		$this->assign([
			'fieldRes' => $fieldRes,
		]);
		return view();
	}

	//查询model数据发送给前端add页面，选择一个model(例如电影)，对模型对应的film表格添加字段，且字段类型由用户进行选择
	public function add() {

		if (request()->isPost()) {
			$data = input('post.');
			$tableName = db('model')->field('table_name')->find($data['model_id']);
			$tableName = config("database.prefix") . $tableName['table_name'];
			//过滤中文"，"
			if ($data['field_values']) {
				$data['field_values'] = str_replace('，', ',', $data['field_values']);
			}
			//验证数据
			$validate = validate('model_fields');
			if (!$validate->scene('add')->check($data)) {
				$this->error($validate->getError());
			}
			//添加
			$add = db('model_fields')->insert($data);
			if ($add) {
				//字段类型: 1:单行文本 2:单选按钮 3:复选框 4:下拉菜单 5:文本域 6:附件 7:浮点 8整型 9:长文本类型 longtext
				switch ($data['field_type']) {
				case 1:
				case 2:
				case 3:
				case 4:
				case 6:
					$fieldType = 'varchar(150) not null default ""';
					break;
				case 5:
					$fieldType = 'varchar(600) not null default ""';
					break;
				case 7:
					$fieldType = 'float not null default "0.0"';
					break;
				case 8:
					$fieldType = 'int not null default "0"';
					break;
				case 9:
					$fieldType = 'longtext';
					break;
				default:
					$fieldType = 'varchar(150) not null default ""';
					break;
				}
				$sql = "alter table {$tableName} add {$data['field_ename']} {$fieldType}";
				Db::execute($sql);
				$this->success('添加字段成功！', 'lst');
			} else {
				$this->error('添加字段失败！');
			}
			return;
		}
		$modelRes = db('model')->field('id,model_name')->select();
		$this->assign([
			'modelRes' => $modelRes,
		]);
		return view();
	}

	//接收前端edit页面POST过来的数据进行修改
	public function edit() {

		if (request()->isPost()) {
			$data = input('post.');
			//获取前缀
			$prefix = config('database.prefix');
			$modelName = $prefix . 'model';
			//连表查询所需要的表名及字段名称
			$fieldInfo = db('model_fields')->field('a.field_ename,b.table_name')->alias('a')->join("$modelName b", 'a.model_id = b.id')->find($data['id']);
			//需要修改字段的表名
			$tableName = $prefix . $fieldInfo['table_name'];
			//需要修改的字段名称
			$oldfieldName = $fieldInfo['field_ename'];
			//过滤中文"，"
			if ($data['field_values']) {
				$data['field_values'] = str_replace('，', ',', $data['field_values']);
			}
			//验证数据
			$validate = validate('model_fields');
			if (!$validate->scene('edit')->check($data)) {
				$this->error($validate->geterror());
			}
			//执行记录修改
			$save = db('model_fields')->update($data);
			//修改数据表的字段
			//字段类型: 1:单行文本 2:单选按钮 3:复选框 4:下拉菜单 5:文本域 6:附件 7:浮点 8整型 9:长文本类型 longtext
			switch ($data['field_type']) {
			case 1:
			case 2:
			case 3:
			case 4:
			case 6:
				$fieldType = 'varchar(150) not null default ""';
				break;
			case 5:
				$fieldType = 'varchar(600) not null default ""';
				break;
			case 7:
				$fieldType = 'float not null default "0.0"';
				break;
			case 8:
				$fieldType = 'int not null default "0"';
				break;
			case 9:
				$fieldType = 'longtext';
				break;
			default:
				$fieldType = 'varchar(150) not null default ""';
				break;
			}
			//$sql = "alter table {$tableName} add {$data['field_ename']} {$fieldType}";
			$sql = "alter table {$tableName} change {$oldfieldName} {$data['field_ename']} {$fieldType}";
			Db::execute($sql);
			if ($save) {
				$this->success('修改字段成功！');
			} else {
				$this->error('修改字段失败！');
			}
			return;
		}
		$modelRes = db('model')->field('id,model_name')->select();
		$modelFields = db('model_fields')->find(input('id'));
		$this->assign([
			'modelRes' => $modelRes,
			'modelFields' => $modelFields,
		]);
		return view();
	}

	//接收前端lst页面ajax的方法，处理ajax删除表中字段及其副表数据
	public function ajaxdel() {
		$modelFieldsID = input('id');
		//获取前缀
		$prefix = config('database.prefix');
		$modelName = $prefix . 'model';
		//连表查询所需要的表名及字段名称
		$fieldInFo = db('model_fields')->field('a.field_ename,b.table_name')->alias('a')->join("$modelName b", 'a.model_id = b.id')->find($modelFieldsID);
		//需要删除字段的表名
		$tableName = $prefix . $fieldInFo['table_name'];
		//需要删除的字段名称
		$fieldName = $fieldInFo['field_ename'];
		$del = db('modelFields')->delete($modelFieldsID);
		//sql
		$sql = "alter table {$tableName} drop column {$fieldName}";
		Db::execute($sql);
		if ($del) {
			echo 1; //删除记录和附加表成功
		} else {
			echo 2; //删除失败;
		}
	}
}
