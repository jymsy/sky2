<?php
namespace Sky\web;

use Sky\base\Component;
use Sky\base\IWidgetFactory;
class WidgetFactory extends Component implements IWidgetFactory{
	
	public function init(){
		
	}
	
	/**
	 * 基于给定的类名和属性创建一个小部件。
	 * @param BaseController $owner 新小部件的所有者。
	 * @param string $className 小部件的类名。
	 * @param array $properties 小部件的初始化属性值 (name=>value)
	 * @return Widget 新创建的小部件。
	 */
	public function createWidget($owner,$className,$properties=array()){
		$widget=new $className($owner);
		
		foreach($properties as $name=>$value)
			$widget->$name=$value;
		return $widget;
	}
}