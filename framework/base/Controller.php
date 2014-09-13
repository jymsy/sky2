<?php
namespace Sky\base;

use Sky\Sky;
use Sky\web\filters\FilterChain;
/**
 * Controller 管理用来处理用户请求的actions。
 *
 * 通过actions，Contorller 在models和views之间协调数据流。
 * 当一个用户请求一个action 'XYZ', Controller 将会做以下的事情：
 * 1. 调用“actionXYZ”如果他存在的话；
 * 2. 调用 {@link missingAction()}, 将会抛出一个404 HTTP exception。
 * 
 * @property Action $action 当前正在执行的action，没有的话为null.
 * @property string $id controller的ID.
 * @property WebModule $module controller属于的module，
 * 如果controller不属于任何的module则返回null
 * @author Jiangyumeng
 */
class Controller extends BaseController{
	/**
	 * 用来存储持久化页面状态的隐藏域的名字。
	 */
	const STATE_INPUT_NAME='SKY_PAGE_STATE';
	
	/**
	 * @var mixed 应用到这个controller的视图的布局文件的名字
	 * 默认为null，意味着使用{@link WebApplication::layout application layout}
	 * 如果为false，则没有布局被使用
	 * 如果controller属于一个模块并且layout属性为null，
	 * 那么{@link WebModule::layout module layout}将会被使用。
	 */
	public $layout;
	public $defaultAction='index';
	public $rawOutput=false;
	private $_module;
	private $_action;
	private $_id;
	
	/**
	 * @param string $id controller的id
	 * @param WebModule $module contorller属于的模块
	 */
	public function __construct($id,$module=null){
		$this->_id=$id;
		$this->_module=$module;
	}
	
	/**
	 * 初始化controller
	 * 该方法在controller执行前被应用调用。
	 * 你可以重写该方法来为controller执行必要的初始化。
	 */
	public function init(){
	}
	
	/**
	 * @return string controller的id
	 */
	public function getId(){
		return $this->_id;
	}
	
	/**
	 * 返回过滤器配置。
	 *
	 * 通过重写该方法，子类可以将过滤规则应用到action上。
	 *
	 * 该方法返回过滤规则的数组。每一个数组元素代表一个过滤器。
	 *
	 * 对于一个基于方法的过滤器(称作内联过滤器)，它的格式为'FilterName[ +|- Action1, Action2, ...]',
	 * '+' ('-')操作符描述了哪些actions应该或不该使用过滤器。
	 *
	 * @return array 过滤器规则的列表。
	 * @see \Sky\web\filters\Filter
	 */
	public function filters(){
		return array();
	}
	
	/**
	 * 返回外部action类的数组。
	 * 数组的key是action id，值是namespace
	 * @return array 外部action类的数组
	 */
	public function actions(){
		return array();
	}
	
	/**
	 * 执行指定的action
	 * @param string $actionID action ID
	 * @return mixed action 的返回值.
	 * @throws HttpException 如果action不存在或action名错误
	 */
	public function run($actionID){
		if(($action=$this->createAction($actionID))!==null){
			if(($parent=$this->getModule())===null)
				$parent=Sky::$app;
			if($parent->beforeControllerAction($this,$action))
			{
// 				return $this->runActionWithFilters($action,$this->filters());
				return $this->runAction($action);
			}
		}else
			$this->missingAction($actionID);
	}
	
	/**
	 * 用指定的过滤器执行action。
	 * 将会创建一个基于指定过滤规则的过滤器链，action将稍后被执行。
	 * @param Action $action 要执行的action。
	 * @param array $filters 应用到action的过滤器列表。
     * @return mixed
	 * @see filters
	 * @see createAction
	 * @see runAction
	 */
	public function runActionWithFilters($action,$filters)
	{
		if(empty($filters))
			return $this->runAction($action);
		else
		{
			$this->_action=$action;
			return FilterChain::create($this,$action,$filters)->run();
		}
	}
	
	/**
	 * 运行action
	 * @param Action $action 要执行的action
	 * @return action的返回值
	 */
	public function runAction($action){
		$this->_action=$action;
		$result=null;
		if($this->beforeAction($action))
		{
			$result=$action->runWithParams($this->getActionParams());
// 			if($result===false)
// 				$this->invalidActionParams($action);
		}
		return $result;
	}
	
	/**
	 * @return Action 当前正在执行的action，没有的话为null
	 */
	public function getAction(){
		return $this->_action;
	}
	
	/**
	 * @param Action $value 当前正在执行的action
	 */
	public function setAction($value){
		$this->_action=$value;
	}
	
	/**
	 * Returns the request parameters that will be used for action parameter binding.返回请求的参数。
	 * 默认返回$_REQUEST,你可以重写该方法
	 * @return array 请求参数。
	 */
	public function getActionParams(){
// 		return $_GET;
		return $_REQUEST;
	}
	
	/**
	 * 该方法在action被执行前调用
	 * 你可以重写该方法来做一些在action之前要做的事
	 * @param Action $action 要执行的action
	 * @return boolean 是否这个action要被执行。
	 */
	protected function beforeAction($action){
		return true;
	}
	
	/**
	 * 当请求的参数不满足要求的时候该方法被调用
	 * 默认情况下会抛出HTTP 400 异常
	 * @param Action $action 要执行的action。
     * @throws \Sky\base\HttpException
	 */
	public function invalidActionParams($action){
		throw new \Sky\base\HttpException(400,'Your request param is invalid.');
	}
	
	/**
	 * 用来处理请求没有编写的action
	 * 当controller没有找到请求的action的时候，该方法被调用。
	 * 默认抛出一个异常。
	 * @param string $actionID 不存在的action名
	 * @throws \Sky\base\HttpException 当方法被调用的时候
	 */
	public function missingAction($actionID){
		throw new  \Sky\base\HttpException(404,'The system is unable to find the requested action: '.$actionID);
	}
	
	/**
	 * 基于action名创建一个action实例
	 * 可以是inline action或webservice action
	 * @param string $actionID action的ID。如果为空的话{@link defaultAction default action}将会被使用。
	 * @return Action action的实例。如果不存在的话返回空。
     * @throws \Exception
	 */
    public function createAction($actionID){

		if($actionID==='')
			$actionID=$this->defaultAction;
		if(isset($_REQUEST['ws'])){
			$action=null;
			if(method_exists($this,'action'.$actionID) && strcasecmp($actionID,'s')){
				$action=new WebServiceAction($this,$actionID);
				if(!method_exists($action,'run'))
					throw new \Exception('Action class '.get_class($action).' must implement the "run" method.');
			}
			return $action;
		}else{
			if(method_exists($this,'action'.$actionID) && strcasecmp($actionID,'s')){
				return new InlineAction($this,$actionID);
			}else{
				$action=$this->createActionFromMap($this->actions(),$actionID);
				if($action!==null && !method_exists($action,'run'))
					throw new \Exception('Action class '.get_class($action).' must implement the "run" method.');
				return $action;
			}
		}
	}
	
	protected function createActionFromMap($actionMap,$actionID){
		if(isset($actionMap[$actionID])){
			$baseConfig=is_array($actionMap[$actionID]) ? $actionMap[$actionID] : array('class'=>$actionMap[$actionID]);
			return \Sky\Sky::createComponent($baseConfig,$this,$actionID);
		}else 
			return null;
	}
	
	/**
	 * 访问另一个controller的action方法
	 * @param string $route 另一个controller的路由信息,(module/controller/action or controller/action)
	 * @return mixed 调用action的返回
	 */
	public function forward($route){
		if(($controller = \Sky\Sky::$app->createController($route))!==null){
			$actionName='action'.ucfirst(substr($route, strrpos($route,'/')+1));
			if(($n=func_num_args())>1){
				$args=func_get_args();
				if($n===2)
					return $controller[0]->$actionName($args[1]);
				elseif($n===3)
					return $controller[0]->$actionName($args[1],$args[2]);
				elseif($n===4)
					return $controller[0]->$actionName($args[1],$args[2],$args[3]);
				else{
					unset($args[0]);
					return call_user_func_array(array($controller[0],$actionName), $args);
				}
			}else
				return $controller[0]->$actionName();
		}else{
			throw new \Sky\base\HttpException(404,'unable to reslove route');
		}
	}
	
	/**
	 * 将浏览器重定向到指定的URL或路由(controller/action)
	 * @param mixed $url 要重定向到的URL。如果参数是数组，
	 * 那么第一个元素必须是指定controller和action的路由，
	 * 其余的是GET的参数的键值对。
	 * @param boolean $terminate 是否终止当前的应用。
	 * @param integer $statusCode HTTP状态码。默认是302. 详细参见{@link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html}
	 */
	public function redirect($url,$terminate=true,$statusCode=302)
	{
		if(is_array($url))
		{
			$route=isset($url[0]) ? $url[0] : '';
			$url=$this->createUrl($route,array_splice($url,1));
		}
		Sky::$app->getRequest()->redirect($url,$terminate,$statusCode);
	}
	
	/**
	 * 渲染一个视图
	 *
	 * 该方法首先调用{@link renderPartial} 来渲染视图(称作内容视图).
	 * 
	 * @param string $view 要渲染的视图名. 
	 * @param array $data 要extracted到PHP变量并且能够被视图脚本访问到的数据。
	 * @param boolean $return 是否需要返回渲染的结果而不是显示给终端用户。
	 * @return string 渲染结果。Null如果不需要渲染结果。
	 */
	public function render($view,$data=null,$return=false){
		if($this->beforeRender($view)){
			$output=$this->renderPartial($view,$data,true);
			if(($layoutFile=$this->getLayoutFile($this->layout))!==false)
				$output=$this->renderFile($layoutFile,array('content'=>$output),true);
			
			$this->afterRender($view,$output);
			if($return)
				return $output;
			else
				echo $output;
		}
	}
	
	public function getLayoutFile($layoutName){
		if($layoutName===false)
			return false;
		
		if(empty($layoutName)){
			$module=$this->getModule();
			
			while($module!==null){
				if($module->layout===false)
					return false;
				if(!empty($module->layout))
					break;
				$module=$module->getParentModule();
			}
			if($module===null)
				$module=Sky::$app;
			$layoutName=$module->layout;
		}elseif(($module=$this->getModule())===null)
			$module=Sky::$app;
		
		return $this->resolveViewFile($layoutName,$module->getLayoutPath(),Sky::$app->getViewPath(),$module->getViewPath());
	}
	
	/**
	 * 渲染一个视图。
	 *
	 * 视图PHP脚本通过该方法被引入。
	 * 如果$data是一个关联数组，它将被extracted为PHP变量使脚本能够访问。
	 *
	 * @param string $view 要渲染的视图名，详细参见{@link getViewFile}
	 * @param array $data 要extracted到PHP变量并且能够被视图脚本访问到的数据。
	 * @param boolean $return是否需要返回渲染的结果而不是显示给终端用户。
	 * @return string 渲染结果。Null如果不需要渲染结果。
	 * @throws \Exception 如果视图不存在。
	 */
	public function renderPartial($view,$data=null,$return=false){
		if(($viewFile=$this->getViewFile($view))!==false){
			$output=$this->renderFile($viewFile,$data,true);

			if($return)
				return $output;
			else
				echo $output;
		}
		else
			throw new \Exception('controller:'.get_class($this).' cannot find the requested view '.$view);
	}
	
	/**
	 * 该方法在{@link render()}的开始被调用。
	 * 你可以重写该方法来在显示视图之前做一些处理。
	 * @param string $view 要显示的视图
	 * @return boolean 是否要显示该视图。
	 */
	protected function beforeRender($view){
		return true;
	}
	
	/**
	 * This method is invoked after the specified is rendered by calling {@link render()}.
	 * Note that this method is invoked BEFORE {@link processOutput()}.
	 * You may override this method to do some postprocessing for the view rendering.
	 * @param string $view the view that has been rendered
	 * @param string $output the rendering result of the view. Note that this parameter is passed
	 * as a reference. That means you can modify it within this method.
	 */
	protected function afterRender($view, &$output)
	{
	}
	
	/**
	 * 为controller中具体的action创建一个相对URL。
	 * @param string $route URL路由。格式应该为 'ControllerID/ActionID'.
	 * 如果ControllerID没有指定，使用当前controller的ID
	 * 如果route为空，就是用当前的action
	 * 如果controller属于一个模块，则 {@link WebModule::getId module ID}
	 * 将会加到前缀。(如果你不希望将模块ID加到前缀的话, 路由应该以'/'开头.)
	 * @param array $params 多余的GET参数 (name=>value). name 和 value 都将被URL-encoded.
	 * 如果name是'#'，对应的value将被当作锚添加到URL的末尾。
	 * @param string $ampersand 在URL中分割name-value对的字符。
	 * @return string 创建的URL
	 */
	public function createUrl($route,$params=array(),$ampersand='&'){
		if($route==='')
			$route=$this->getId().'/'.$this->getAction()->getId();
		elseif(strpos($route,'/')===false)
			$route=$this->getId().'/'.$route;
		if($route[0]!=='/' && ($module=$this->getModule())!==null)
			$route=$module->getId().'/'.$route;
		return Sky::$app->createUrl(trim($route,'/'),$params,$ampersand);
	}
	
	/**
	 * 根据名字找到视图文件
	 *
	 * 视图的名字可以是以下几种格式：
	 * <ul>
	 * <li>模块内的绝对视图: 视图名以'/'开头
	 * 这时，将会在当前的模块的视图目录下搜索。
	 * 如果没有激活的模块，将会在应用的视图目录下搜索。</li>
	 * <li>应用内的绝对视图: 视图名字以'//'开头。
	 * 这时，将会在应用的视图目录搜索</li>
	 * <li>相对视图:除此之外，将会在当前Controller的视图路径下搜索。</li>
	 * </ul>
	 *
	 * @param string $viewName 视图名
	 * @return string 视图文件路径, false 如果视图文件不存在。
	 * @see resolveViewFile
	 */
	public function getViewFile($viewName){
// 		return $this->resolveViewFile($viewName,$this->getViewPath());
		$moduleViewPath=$basePath=Sky::$app->getViewPath();
		if(($module=$this->getModule())!==null)
			$moduleViewPath=$module->getViewPath();
		return $this->resolveViewFile($viewName,$this->getViewPath(),$basePath,$moduleViewPath);
	}
	
	/**
	 * 根据名字找到视图文件
	 * 视图的名字可以是以下几种格式：
	 * <ul>
	 * <li>模块内的绝对视图: 视图名以'/'开头
	 * 这时，将会在当前的模块的视图目录下搜索。
	 * 如果没有激活的模块，将会在应用的视图目录下搜索。</li>
	 * <li>应用内的绝对视图: 视图名字以'//'开头。
	 * 这时，将会在应用的视图目录搜索</li>
	 * <li>相对视图:除此之外，将会在当前Controller的视图路径下搜索。</li>
	 * </ul>
	 * For absolute view and relative view, the corresponding view file is a PHP file
	 * whose name is the same as the view name. 
	 * @param string $viewName 视图名
	 * @param string $viewPath 用来搜索相对视图的路径。
	 * @param string $basePath 用来搜索应用绝对视图的路径
	 * @param string $moduleViewPath 用来搜索模块绝对视图的路径，如果没有设置的话将使用$basePath
	 * @return mixed 视图文件路径。False如果文件不存在。
	 */
	public function resolveViewFile($viewName,$viewPath,$basePath,$moduleViewPath=null){
		if(empty($viewName))
			return false;
		
		if($moduleViewPath===null)
			$moduleViewPath=$basePath;
		
		if($viewName[0]==='/'){
			
			if(strncmp($viewName,'//',2)===0)
				$viewFile=$basePath.$viewName;
			else
				$viewFile=$moduleViewPath.$viewName;
		}else
			$viewFile=$viewPath.DIRECTORY_SEPARATOR.$viewName;

		if(is_file($viewFile.'.php')){
			return $viewFile.'.php';
		}else
			return false;
	}
	
	/**
	 * 返回当前controller的视图文件目录。
	 * 默认返回 'views/ControllerID'.
	 * 子类可以重写该方法来使用自定义的视图目录。
	 * @return string 包含当前controller的视图文件的目录。默认为'views/ControllerID'.
	 */
	public function getViewPath(){
		if(($module=$this->getModule())===null)
			$module=Sky::$app;
		return $module->getViewPath().DIRECTORY_SEPARATOR.$this->getId();
	}
	
	/**
	 * @return WebModule controller属于的module，
	 * 如果controller不属于任何的module则返回null
	 */
	public function getModule(){
		return $this->_module;
	}
	
	/**
	 * @return string 以模块id（如果有的话）为前缀的controller ID
	 */
	public function getUniqueId()
	{
		return $this->_module ? $this->_module->getId().'/'.$this->_id : $this->_id;
	}
	
	/**
	 * 将浏览器重定向到主页。
	 * @return Response the current response object
	 */
	public function goHome()
	{
		Sky::$app->getRequest()->redirect(Sky::$app->getHomeUrl());
	}
}
