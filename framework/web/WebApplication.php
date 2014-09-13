<?php
namespace Sky\web;

use Sky\base\Application;
use Sky\Sky;

/**
 * @property string $controllerPath 包含controller 类文件的目录。默认是'controllers'.
 * @property Controller $controller 当前的Controller。
 * @property \Sky\web\Session $session
 * @property string $viewPath view文件的根目录，默认为'views'。
 * @property string $systemViewPath 系统view文件的根目录，默认为 'views/system'。
 * @property string $layoutPath 布局文件根目录。默认是'views/layouts'.
 * @property IWidgetFactory $widgetFactory 小部件工厂。
 * @property \Sky\web\User $user 用户组件。
 * @property string $homeUrl 主页 URL.
 * 
 * @author Jiangyumeng
 *
 */
class WebApplication extends Application{
	/**
	 * @return string 默认路由。默认为'site'.
	 */
	public $defaultController='site';
	/**
	 * @var mixed 应用范围的布局。默认是'main' 。
	 * 如果为false的话没有布局被使用。
	 */
	public $layout='main';

    public $defaultVersion='';

    private $_version;
	private $_controller;
	private $_controllerPath;
	private $_viewPath;
	private $_layoutPath;
	private $_systemViewPath;
	private $_homeUrl;
	
	/**
	 * 处理当前的请求。
	 * 首先将请求解析成controller和action。
	 * 然后创建controller去执行action。
	 * @return Response 响应的结果。
	 */
	public function processRequest(){
		$route=$this->getUrlManager()->parseUrl($this->getRequest());
        $this->_version = $this->getVersion();
		$result = $this->runController($route);
		if ($result instanceof Response) {
			return $result;
		}else{
			$response = $this->getResponse();
			if ($result !== null) {
				$response->data = $result;
			}
			return $response;
		}
	}

    /**
     * 获取请求的版本
     * @return string
     */
    public function getVersion()
    {
        return (isset($_GET['v']) && is_string($_GET['v']))?$_GET['v']:$this->defaultVersion;
    }
	
	/**
	 * 注册应用核心组件。
	 * 该方法通过重写父类的实现来组册额外的组件。
	 * @see setComponents
	 */
	protected function registerCoreComponents(){
		parent::registerCoreComponents();
		$this->setComponents(array(
				'widgetFactory'=>array(
						'class'=>'Sky\web\WidgetFactory',
				),
				'user' => array(
						'class' => 'Sky\web\User',
				),
				'response'=>array(
					'class'=>'Sky\web\Response'
				),
		));
	}
	
	/**
	 * 创建controller和执行具体的action.
	 * @param string $route 当前请求的路由。
	 * @return mixed 响应的结果。
	 * @throws \Sky\base\HttpException 如果controller创建失败。
	 */
	public function runController($route){
		if(($ca=$this->createController($route))!==null){
			list($controller,$actionID)=$ca;
			$this->_controller=$controller;
			$controller->init();
			return $controller->run($actionID);
		}else
			throw new \Sky\base\HttpException(404,'unable to reslove route');
	}
	
	/**
	 * 	基于route创建一个controller实例。
	 * route需要包含contorller ID和action ID。
	 *
	 * @param string $route 请求的路由
	 * @param object $owner
	 * @param string $parentId
	 * @return array controller实例和action ID。
	 * 如果controller类不存在或者是非法route返回null。
	 */
	public function createController($route,$owner=null,$parentId=null){
		if($owner===null)
			$owner=$this;
		if(($route=trim($route,'/'))==='')
			$route=$owner->defaultController;
		$route.='/';

		if(($pos=strpos($route,'/'))!==false){
				
			$id=substr($route,0,$pos);
			if(!preg_match('/^\w+$/',$id))
				return null;
			if($parentId!==null)
				$id=$parentId.'/'.$id;
			$route=(string)substr($route,$pos+1);
	
			if(!isset($basePath))
			{
				if(($module=$owner->getModule($id))!==null)
					return $this->createController($route,$module,$id);
				$basePath=$owner->getControllerPath();
			}
			if(($pos=strrpos($id, '/'))!==false)
				$id=substr($id, $pos+1);
			$className=ucfirst($id).'Controller'.$this->_version;

//            echo $className;
	
			$classFile=$basePath.DIRECTORY_SEPARATOR.$className.'.php';
			$controllerNamespace=$owner->name.'\\controllers\\'.$className;
			if(is_file($classFile)){
				$actionID=$this->parseActionParams($route);
				if(!class_exists($controllerNamespace,false)){
					require($classFile);
				}
				if(class_exists($controllerNamespace,false) && is_subclass_of($controllerNamespace,/*__NAMESPACE__.'\\Controller'*/'Sky\base\Controller')){
					return array(
							new $controllerNamespace($id,$owner===$this?null:$owner),
							$actionID,
					);
				}
				return null;
			}
		}
	}
	
	/**
	 * 解析path为action ID和GET 变量。
	 * @param string $pathInfo path info
	 * @return string action ID
	 */
	protected function parseActionParams($pathInfo){
		$manager=$this->getUrlManager();
		if(($pos=strpos($pathInfo,'/'))!==false){
			$manager->parsePathInfo((string)substr($pathInfo,$pos+1));
			$actionID=substr($pathInfo,0,$pos);
			$this->setGlobalVar();
			return ucfirst($actionID);
		}else{
			$this->setGlobalVar();
			return ucfirst($pathInfo);
		}
	}
	
	/**
	 *
	 */
	public function setGlobalVar(){
		$params=Sky::$app->params;
		if(is_array($params)){
			foreach($params as $k=>$v){
				if (is_string($v)) {
					if(isset($_GET[$v])){
						$GLOBALS[$k]=$_GET[$v];
						unset($_GET[$v]);
					}elseif(isset($_POST[$v])){
						$GLOBALS[$k]=$_POST[$v];
						unset($_POST[$v]);
					}
				}
			}
		}
	}
	
	/**
	 * @return Controller 当前的controller.
	 */
	public function getController(){
		return $this->_controller;
	}
	
	/**
	 * @param Controller $value 当前的controller
	 */
	public function setController($value){
		$this->_controller=$value;
	}
	
	/**
	 * @return Session the session组件
	 */
	public function getSession(){
		return $this->getComponent('session');
	}
	
	/**
	 * 返回Response组件。
	 * @return Response
	 */
	public function getResponse()
	{
		return $this->getComponent('response');
	}
	
	/**
	 * @return string 包含controller类文件的目录。 默认为 'controllers'.
	 */
	public function getControllerPath(){
		if($this->_controllerPath!==null)
			return $this->_controllerPath;
		else
			return $this->_controllerPath=$this->getBasePath().DIRECTORY_SEPARATOR.'controllers';
	}
	
	/**
	 * @param string $value 包含controller 类文件的目录
	 * @throws \Exception 如果目录非法
	 */
	public function setControllerPath($value){
		if(($this->_controllerPath=realpath($value))===false || !is_dir($this->_controllerPath))
			throw new \Exception('The controller path "'.$value.'" is not a valid directory.');
	}
	
	/**
	 * @return string view文件的根目录，默认为'views'。
	 */
	public function getViewPath(){
		if($this->_viewPath!==null)
			return $this->_viewPath;
		else
			return $this->_viewPath=$this->getBasePath().DIRECTORY_SEPARATOR.'views';
	}
	
	/**
	 * @param string $path view文件的根目录。
	 * @throws Exception 如果目录不存在。
	 */
	public function setViewPath($path){
		if(($this->_viewPath=realpath($path))===false || !is_dir($this->_viewPath))
			throw new \Exception('The view path "'.$path.'" is not a valid directory.');
	}
	
	/**
	 * @return string 布局文件的根目录。默认是'views/layouts'.
	 */
	public function getLayoutPath(){
		if($this->_layoutPath!==null)
			return $this->_layoutPath;
		else
			return $this->_layoutPath=$this->getViewPath().DIRECTORY_SEPARATOR.'layouts';
	}
	
	/**
	 * @param string $path 布局文件的根目录。
	 * @throws \Exception 如果目录不存在。
	 */
	public function setLayoutPath($path){
		if(($this->_layoutPath=realpath($path))===false || !is_dir($this->_layoutPath))
			throw new \Exception("The layout path '$path' is not a valid directory.");
	}
	
	/**
	 * 返回小部件工厂实例。
	 * @return IWidgetFactory
	 */
	public function getWidgetFactory(){
		return $this->getComponent('widgetFactory');
	}
	
	/**
	 * @return string 自定义系统view文件的根目录，默认为 'views/system'。
	 */
	public function getSystemViewPath(){
		if($this->_systemViewPath!==null)
			return $this->_systemViewPath;
		else
			return $this->_systemViewPath=$this->getViewPath().DIRECTORY_SEPARATOR.'system';
	}
	
	/**
	 * @param string $path 系统view文件的根目录。
	 * @throws Exception 如果目录不存在。
	 */
	public function setSystemViewPath($path){
		if(($this->_systemViewPath=realpath($path))===false || !is_dir($this->_systemViewPath))
			throw new \Exception('The system view path "'.$path.'" is not a valid directory.');
	}
	
	public function beforeControllerAction($controller,$action){
		return true;
	}
	
	/**
	 * @return string 主页 URL
	 */
	public function getHomeUrl()
	{
		if ($this->_homeUrl === null) {
			if ($this->getUrlManager()->showScriptName) {
				return $this->getRequest()->getScriptUrl();
			} else {
				return $this->getRequest()->getBaseUrl() . '/';
			}
		} else {
			return $this->_homeUrl;
		}
	}
	
	/**
	 * @param string $value 主页 URL
	 */
	public function setHomeUrl($value)
	{
		$this->_homeUrl = $value;
	}
	
	/**
	 * 返回用户组件。
	 * @return User 用户组件
	 */
	public function getUser()
	{
		return $this->getComponent('user');
	}
}