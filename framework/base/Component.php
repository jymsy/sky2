<?php
namespace Sky\base;

/**
 * Component 是所有组件类的基类。 
 * 
 * Component 实现了定义、使用属性和事件的协议。 
 * 属性是通过getter方法或/和setter方法定义。 
 * 访问属性就像访问普通的对象变量。 
 * 读取或写入属性将调用应相的getter或setter方法， 例如：
 * <pre>
 * $a=$component->text;     // equivalent to $a=$component->getText();
 * $component->text='abc';  // equivalent to $component->setText('abc');
 * </pre>
 * getter和setter方法的格式如下:
 * <pre>
 * // getter, defines a readable property 'text'
 * public function getText() { ... }
 * // setter, defines a writable property 'text' with $value to be set to the property
 * public function setText($value) { ... }
 * </pre>
 * @author Jiangyumeng
 */
class Component{
	private $_e;
	private $_events;
	
	
	/**
	 * 初始化应用组件
	 */
	protected function init(){
	}
	
	/**
	 * 设置一个组件的属性值。 
	 * 
	 * 不能直接调用此方法。
	 * 这是重载了PHP的魔术方法， 允许使用以下语法设置一个属性：
	 * <pre>
	 * $this->propertyName=$value;
	 * </pre>
	 * @param string $name 属性名
	 * @param mixed $value 属性值
	 * @throws \Exception if the property is not defined or read only
	 */
	public function __set($name,$value){
		$setter='set'.$name;
// 		echo "wo get setter $setter.\n";
		if(method_exists($this,$setter))
			return $this->$setter($value);
		if(method_exists($this,'get'.$name))
			throw new \Exception('Property '.$name.' is read only.');
		else
			throw new \Exception('Property '.$name.' is not defined.');
	}
	
	/**
	 * 返回一个属性值。
	 * 
	 *  不能直接调用此方法。
	 *  这是重载了PHP的魔术方法， 允许使用以下语法读取一个属性：
	 *  
	 * @param string $name 属性名
	 * @throws \Exception if property is not defined.
	 */
	public function __get($name){
		$getter='get'.$name;
		if(method_exists($this,$getter))
			return $this->$getter();
		throw new \Exception('Property '.get_class($this).'->'.$name.' is not defined.');
	}
	
	/**
	 * 检测一个属性值是否是null
	 * 不能直接调用此方法。 
	 * 这是一个PHP魔术方法，重写该方法是为了能用isset()来检测一个组件的属性是否被设置。
	 * @param string $name 属性名
	 * @return boolean
	 */
	public function __isset($name){
		$getter='get'.$name;
		if(method_exists($this,$getter))
			return $this->$getter()!==null;
		return false;
	}
	
	/**
	 * 检查事件是否有附加的处理程序。
	 * @param string $name 事件名
	 * @return boolean 事件是否附加了一个或多个处理程序
	 */
	public function hasEventHandler($name){
// 		$name=strtolower($name);
		return !empty($this->_e[$name]);
// 		return isset($this->_e[$name]) && $this->_e[$name]->getCount()>0;
	}
	
	/**
	 * 返回所有附加在一个事件的处理程序列表。
	 * @param string $name 事件名
	 * @return \Sky\help\SList 返回所有附加在一个事件的处理程序列表。
	 * @throws \Exception 如果event为定义
	 */
// 	public function getEventHandlers($name){
// 		if($this->hasEvent($name)){
// 			$name=strtolower($name);
// 			if(!isset($this->_e[$name]))
// 				$this->_e[$name]=new \Sky\help\SList;
// 			return $this->_e[$name];
// 		}else
// 			throw new \Exception('Event '.get_class($this).'->'.$name.' is not defined.');
// 	}
	
	/**
	 * 发起一个事件。
	 * 该方法发起一个事件。
	 * 它调用该事件所有附加的处理程序。
	 * @param string $name 事件名
	 * @param Event $event 事件参数
	 * @throws \Exception 如果事件没有定义或事件的处理程序非法。
	 */
	public function raiseEvent($name,$event){
// 		$name=strtolower($name);
		if(isset($this->_e[$name]))
		{
			foreach($this->_e[$name] as $handler){
// 				if(is_string($handler))
					call_user_func($handler,$event);
// 				elseif(is_callable($handler,true)){
					
// 					if(is_array($handler)){
// 						// an array: 0 - object, 1 - method name
// 						list($object,$method)=$handler;
// 						if(is_string($object))	// static method call
// 							call_user_func($handler,$event);
// 						elseif(method_exists($object,$method))
// 							$object->$method($event);
// 						else
// 							throw new \Exception('Event '.get_class($this).'->'.$name.' is attached with an invalid handler '.$handler[1]);
// 					}
// 					else // PHP 5.3: anonymous function
// 						call_user_func($handler,$event);
// 				}else
// 					throw new \Exception('Event '.get_class($this).'->'.$name.' is attached with an invalid handler '.$handler);
				// stop further handling if param.handled is set true
				if(($event instanceof Event) && $event->handled)
					return;
			}
		}
// 			elseif(SKY_DEBUG && !$this->hasEvent($name))
// 			throw new \Exception('Event '.get_class($this).'->'.$name.' is not defined.');
	}
	
	public function attachEventHandler($name,$handler){
// 		$this->getEventHandlers($name)->add($handler);
		$this->_e[$name][] = $handler;
	}
	
	/**
	 * 判断是否一个事件已被定义。
	 * 如果一个类包含名为“onXXX”的方法，则这个事件被定义了。
	 * @param string $name 事件名
	 * @return boolean 是否一个事件已被定义
	 */
	public function hasEvent($name){
		return !strncasecmp($name,'on',2) && method_exists($this,$name);
	}
}

/**
 * Event是所有事件类的基类。 
 *
 * 它封装了与事件相关的参数。
 * sender属性指的是谁发起来的事件。 
 * handled属性指的是事件的处理方式. 
 * 如果一个事件处理程序设置了handled为true， 
 * 其它未处理的事件处理程序将不会被调用。
 */
class Event extends Component{
	/**
	 * @var object 事件的发起者
	 */
	public $sender;
	/**
	 * @var boolean 事件是否已被处理。默认为false。
	 * 当这个值设置为true，其它未处理的事件将不会被处理。
	 */
	public $handled=false;
	/**
	 * @var mixed 附加的事件参数。
	 */
	public $params;

	/**
	 * @param mixed $sender 事件的发起者
	 * @param mixed $params 附加的事件参数
	 */
	public function __construct($sender=null,$params=null){
		$this->sender=$sender;
		$this->params=$params;
	}
}