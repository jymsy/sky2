<?php
namespace Sky\rbac;

use Sky\db\ConnectionPool;
use Sky\Sky;
class DbManager extends Manager{
	/**
	 * @var ConnectionPool|string db 连接对象或db连接的组件ID。
	 * 如果你想在DbManager对象创建后改变这个属性的值，你应该传递DB连接对象。
	 */
	public $db = 'db';
	/**
	 * @var string 存储授权项目的表。默认为AuthItem
	 */
	public $itemTable = 'AuthItem';
	/**
	 * @var string 存储授权项目层级的表。默认为AuthItemChild
	 */
	public $itemChildTable = 'AuthItemChild';
	/**
	 * @var string 存储授权项目任务的表。默认为AuthAssignment
	 */
	public $assignmentTable = 'AuthAssignment';
	
	public function init()
	{
		if (is_string($this->db)) {
			$this->db = Sky::$app->getComponent($this->db);
		}
		if (!$this->db instanceof ConnectionPool) {
			throw new \Exception("DbManager::db must be either a DB connection instance or the application component ID of a DB connection.");
		}
	}
	
	/**
	 * 创建一个授权项目。
	 *  一个授权项目就是一个做某件事的许可 (例如新帖发布).
	 * 授权项目可分为操作（operations），任务（tasks） 和 角色（roles）。
	 * 高等级的项目继承了低等级项目的权限。
	 * @param string $name 项目名。必须是一个唯一的值。
	 * @param integer $type 项目类型 (0: 操作, 1: 任务, 2: 角色).
	 * @param string $description 项目的描述
	 * @param string $bizRule 关联到项目的业务规则。这是一小段PHP代码，
	 * 当{@link checkAccess()}被调用的时候将会执行。
	 * @param mixed $data 与项目关联的额外数据。
	 * @throws \Exception 如果有同名的项目存在。
	 * @return Item 授权项目
	 */
	public function createItem($name, $type, $description = '', $bizRule = null, $data = null)
	{
		$this->db->createCommand(
			'insert into '.$this->itemTable.' (name,type,description,blzrule,data)
				values(:name,:type,:description,:blzrule,:data)',
				array('name'=>$name,
						'type'=>$type,
						'description'=>$description,
						'blzrule'=>$bizRule,
						'data'=>$data,
				)
		)->exec();
		
		return new Item($this, $name, $type,$description,$bizRule,$data);
	}
	
	/**
	 * 将一个项目添加为另一个项目的子项目。
	 * @param string $itemName 父项目的名字
	 * @param string $childName 子项目的名字
	 * @return boolean 是否成功添加
	 * @throws \Exception 如果父项目或子项目不存在或检测到了循环。
	 */
	public function addItemChild($itemName, $childName)
	{
		if ($itemName === $childName) 
		{
			throw new \Exception("Cannot add '$itemName' as a child of itself.");
		}
		
		$rows = $this->db->createCommand(
			'select * from '.$this->itemTable.' where name=:iname or name=:cname',
			array('iname'=>$itemName,'cname'=>$childName)
		)->toList();
		
		if (count($rows) == 2) 
		{
			if ($rows[0]['name'] === $itemName) 
			{
				$parentType = $rows[0]['type'];
				$childType = $rows[1]['type'];
			} else {
				$childType = $rows[0]['type'];
				$parentType = $rows[1]['type'];
			}
			$this->checkItemChildType($parentType, $childType);
			
		}else{
			throw new \Exception("Either '$itemName' or '$childName' does not exist.");
		}
	}
	
	/**
	 * 检测在验证项目层级中是否有循环
	 * @param string $itemName 父项目名。
	 * @param string $childName 子项目名
	 * @return boolean 是否存在循环
	 */
	protected function detectLoop($itemName, $childName)
	{
		if ($childName === $itemName) {
			return true;
		}
		foreach ($this->getItemChildren($childName) as $child) 
		{
			if ($this->detectLoop($itemName, $child->getName())) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 返回指定项目的子项目。
	 * @param mixed $names 父项目名。数组或字符串
	 * @return Item
	 */
	public function getItemChildren($names)
	{
		if (is_string($names)) {
			$condition='parent='.addslashes($names);
		}elseif(is_array($names) && !empty($names)){
			foreach ($names as $name)
			{
// 				$condition.=parent
			}
		}
	}
}