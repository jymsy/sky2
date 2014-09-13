<?php
namespace Sky\rbac;

use Sky\base\Component;
abstract class Manager extends Component{
	/**
	 * @var boolean 让bizRules能够报错.
	 */
	public $showErrors = false;
	
	public $defaultRoles = array();
	
	/**
	 * 创建一个角色。
	 * @param string $name 项目的名字
	 * @param string $description 项目的描述。
	 * @param string $bizRule 关联到这个项目的业务规则。
	 * @param mixed $data 在验证规则时传递的额外参数。
	 * @return Item 授权项目
	 */
	public function createRole($name, $description = '', $bizRule = null, $data = null)
	{
		return $this->createItem($name, Item::TYPE_ROLE, $description, $bizRule, $data);
	}
	
	/**
	 * 创建一个任务。
	 * @param string $name 项目的名字
	 * @param string $description 项目的描述。
	 * @param string $bizRule 关联到这个项目的业务规则。
	 * @param mixed $data 在验证规则时传递的额外参数。
	 * @return Item 授权项目
	 */
	public function createTask($name, $description = '', $bizRule = null, $data = null)
	{
		return $this->createItem($name, Item::TYPE_TASK, $description, $bizRule, $data);
	}
	
	/**
	 * 创建一个操作。
	 * @param string $name 项目的名字
	 * @param string $description 项目的描述。
	 * @param string $bizRule 关联到这个项目的业务规则。
	 * @param mixed $data 在验证规则时传递的额外参数。
	 * @return Item 授权项目
	 */
	public function createOperation($name, $description = '', $bizRule = null, $data = null)
	{
		return $this->createItem($name, Item::TYPE_OPERATION, $description, $bizRule, $data);
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
	abstract public function createItem($name, $type, $description = '', $bizRule = null, $data = null);
	
	/**
	 * 将一个项目添加为另一个项目的子项目。
	 * @param string $itemName 父项目的名字
	 * @param string $childName 子项目的名字
	 * @throws \Exception 如果父项目或子项目不存在或检测到了循环。
	 */
	abstract public function addItemChild($itemName, $childName);
	
	/**
	 * 检测项目的类型，以确保可以添加到父项目
	 * @param integer $parentType 父项目类型
	 * @param integer $childType 子项目类型
	 * @throws \Exception 如果类型不符的话
	 */
	protected function checkItemChildType($parentType, $childType)
	{
		$types = array('operation', 'task', 'role');
		if ($parentType < $childType) 
		{
			throw new \Exception("Cannot add an item of type '{$types[$childType]}' to an item of type '{$types[$parentType]}'.");
		}
	}
}