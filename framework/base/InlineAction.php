<?php
namespace Sky\base;


/**
 * 内部controller调用action，非webservice
 * 
 * InlineAction代表那些被定义成controller方法的action。
 * 方法名类似 'actionXYZ' 其中 'XYZ' 代表action的名字。
 *
 * @author Jiangyumeng
 */
class InlineAction extends Action
{
	/**
	 * 运行action。
	 * 该方法被{@link Action}依赖。
	 */
	public function run()
	{
		$method='action'.$this->getId();
		$this->getController()->$method();
	}

	/**
	 * 运行带参数的action
	 * 该方法被{@link Controller::runAction()}调用.
	 * @param array $params 请求的参数数组(name=>value)
	 * @return mixed action的返回值。
	 * @return boolean whether the request parameters are valid
	 */
	public function runWithParams($params)
	{
		$methodName='action'.$this->getId();
		$controller=$this->getController();
		$method=new \ReflectionMethod($controller, $methodName);
		if($method->getNumberOfParameters()>0)
			return $this->runWithParamsInternal($controller, $method, $params);
		else
			return $this->runInternal($controller, $methodName);
	}

}