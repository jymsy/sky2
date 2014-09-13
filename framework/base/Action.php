<?php
namespace Sky\base;

/**
 * Action是所有控制器动作类的基类。
 * Action类提供了一个分割一个复杂的控制器到几个简单的动作的途径。
 * 派生类必须实现run()方法
 * 一个action实例能通过{@link getController controller}属性访问它的控制器。
 * @property Controller $controller 拥有这个action的控制器。
 * @property string $id action的Id.
 * @author Jiangyumeng
 *
 */
class Action extends Component{
	private $_id;
	private $_controller;
	private $_output;
	
	/**
	 * Constructor.
	 * @param Controller $controller 拥有此action的controller
	 * @param string $id action的id
	 */
	public function __construct($controller,$id){
		$this->_controller=$controller;
		$this->_id=$id;
	}
	
	/**
	 * @return string action的id
	 */
	public function getId(){
		return $this->_id;
	}
	
	/**
	 * @return Controller 拥有此action的controller
	 */
	public function getController(){
		return $this->_controller;
	}
	
	public function getActionOutput(){
		return $this->_output;
	}
	
	public function runWithParams($params){
// 		$method=new \ReflectionMethod($this, 'run');
// 		if($method->getNumberOfParameters()>0)
// 			return $this->runWithParamsInternal($this, $method, $params);
// 		else
			return $this->run($params);
	}
	
	protected function runInternal($controller, $methodName){
		$this->_output=$controller->$methodName();
		return $this->_output;
	}
	
	protected function runWithParamsInternal($object, $method, $params){
		$ps=array();
		$missing = array();
		foreach($method->getParameters() as $i=>$param)
		{
			$name=$param->getName();
			if(isset($params[$name]))
			{
				if($param->isArray())
					$ps[]=is_array($params[$name]) ? $params[$name] : array($params[$name]);
				elseif(!is_array($params[$name]))
					$ps[]=$params[$name];
				else
					throw new HttpException(400,'Invalid data received for parameter "'.$name.'".');
// 					return false;
			}elseif($param->isDefaultValueAvailable()){
				$ps[]=$param->getDefaultValue();
			}else
				$missing[]=$name;
// 				return false;
// 				throw new HttpException(400,'Missing required parameters.');
		}
		
		if (!empty($missing)) {
			throw new HttpException(400, 'Missing required parameters: '.implode(', ', $missing));
		}
		$this->_output=$method->invokeArgs($object,$ps);
		return $this->_output;
	}
	
	/**
	 * url的参数顺序与action的顺序一致
	 * @param unknown_type $object
	 * @param unknown_type $method
	 * @param unknown_type $params
	 * @return boolean
	 */
	protected function runWithParamsOuter($object, $method, $params){
		$ps=array();
		$missing = array();
		foreach($method->getParameters() as $i=>$param){
			//params name order by a,b,c........
			if(isset($params[chr($i+97)])){
				$ps[]=$params[chr($i+97)];
			}elseif($param->isDefaultValueAvailable()){
				$ps[]=$param->getDefaultValue();
			}else
				$missing[]=$param->getName();
// 				return false;
		}
		if (!empty($missing)) {
			throw new HttpException(400, 'Missing required parameters: '.implode(', ', $missing));
		}
		$this->_output=$method->invokeArgs($object,$ps);
		return $this->_output;
	}
}