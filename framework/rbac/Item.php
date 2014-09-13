<?php
namespace Sky\rbac;

use Sky\base\Component;
class Item extends Component{
	const TYPE_OPERATION = 0;
	const TYPE_TASK = 1;
	const TYPE_ROLE = 2;
	/**
	 * @var Manager 
	 */
	public $_manager;
	/**
	* @var string 项目描述
	*/
	private $_description;
	/**
	 * @var string 关联到项目的业务规则
	 */
	private $_bizRule;
	/**
	 * @var mixed 关联到项目的额外数据
	 */
	private $_data;
	/**
	 * @var integer 认证项目类型。 0 (操作), 1 (任务), 2 (角色).
	 */
	private $_type;
	/**
	 * @var string 认证项目的名字
	 */
	private $_name;
	
	/**
	 * Constructor.
	 */
	public function __construct($auth,$name,$type,$description='',$bizRule=null,$data=null)
	{
		$this->_type=(int)$type;
		$this->_manager=$auth;
		$this->_name=$name;
		$this->_description=$description;
		$this->_bizRule=$bizRule;
		$this->_data=$data;
	}
	
	/**
	 * 添加一个子项目
	 * @param string $name 子项目的名字
	 * @return boolean 是否成功添加
	 * @throws \Exception 如果父项目或子项目不存在或检测到了循环。
	 * @see Manager::addItemChild
	 */
	public function addChild($name)
	{
		return $this->_manager->addItemChild($this->_name, $name);
	}
}