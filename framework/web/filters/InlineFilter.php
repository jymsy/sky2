<?php
namespace Sky\web\filters;

class InlineFilter extends Filter{
	public $name;
	
	public static function create($controller,$filterName)
	{
		if(method_exists($controller,'filter'.$filterName))
		{
			$filter=new self();
			$filter->name=$filterName;
			return $filter;
		}
		else
			throw new \Exception('Filter "'.$filterName.'" is invalid. Controller "'.
					get_class($controller).'" does not have the filter method "filter'.$filterName.'".');
	}
	
	/**
	 * 执行过滤器。
	 * 该方法调用了controller类中的过滤方法。
	 * @param FilterChain $filterChain 过滤器所在的过滤器链
	 */
	public function filter($filterChain)
	{
		$method='filter'.$this->name;
		$filterChain->controller->$method($filterChain);
	}
}