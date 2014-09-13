<?php
namespace Sky\base;

use Sky\Sky;
/**
 * 
 * 一个应用模块可以被认为是一种小型的应用，他有自己的控制器，模块和视图。
 * 能够被其他的项目重用。模块内的控制器要想被访问到必须要有模块ID前缀。
 * 
 * @property string $name 模块名
 * @property string $viewPath 视图文件的根目录，默认是'moduleDir/views'。
 * @property string $layoutPath 布局文件根目录。默认是'moduleDir/views/layouts' moduleDir是包含模块类的目录
 * 
 * @author Jiangyumeng
 *
 */
class WebModule extends \Sky\base\Module{
	public $defaultController='default';
	/**
	 * @var mixed 被模块内的controllers共享的布局。
	 * 如果一个controller定义了自己的 {@link \Sky\base\Controller::layout layout},
	 * 该属性将被忽略。
	 * 如果该属性是null(默认), 应用的布局或父模块的布局(如果可用的话)将被使用。
	 * 如果为false，将没有布局被使用。
	 */
	public $layout;
	private $_controllerPath;
	public $controllerNamespace;
	private $_viewPath;
	private $_layoutPath;
	
	/**
	 * @return string 包含控制器类文件的目录。默认是'moduleDir/controllers'
	 */
	public function getControllerPath(){
		if($this->_controllerPath!==null)
			return $this->_controllerPath;
		else
			return $this->_controllerPath=$this->getBasePath().DIRECTORY_SEPARATOR.'controllers';
	}
	
	/**
	 * 返回模块名
	 * 默认是返回将模块id中的'/'替换为'\'后的模块id，
	 * 你可以重写该方法以返回自定义的模块名。
	 * @return string 模块名
	 */
	public function getName(){
// 		return basename($this->getId());
		return str_replace('/', '\\', $this->getId());
	}
	
	/**
	 * @return string 视图文件的根目录，默认是'moduleDir/views'。
	 */
	public function getViewPath(){
		if($this->_viewPath!==null)
			return $this->_viewPath;
		else
			return $this->_viewPath=$this->getBasePath().DIRECTORY_SEPARATOR.'views';
	}
	
	/**
	 * @return string 布局文件的根目录。默认是'moduleDir/views/layouts'
	 */
	public function getLayoutPath(){
		if($this->_layoutPath!==null)
			return $this->_layoutPath;
		else
			return $this->_layoutPath=$this->getViewPath().DIRECTORY_SEPARATOR.'layouts';
	}
	
	/**
	 * 该方法在属于该模块的所有的controller的acton调用之前被调用
	 * 你可以用下面的方式重写该方法：
	 * <pre>
	 * if(parent::beforeControllerAction($controller,$action))
	 * {
	 *     // 你的代码
	 *     return true;
	 * }
	 * else
	 *     return false;
	 * </pre>
	 * @param Controller $controller controller
	 * @param Action $action action
	 * @return boolean acton是否应该被执行。
	 */
	public function beforeControllerAction($controller,$action)
	{
		if(($parent=$this->getParentModule())===null)
			$parent=Sky::$app;
		return $parent->beforeControllerAction($controller,$action);
	}
}