<?php
namespace Sky\base;

use Sky\web\Response;
/**
 * Application 类文件
 * 
 * Application是所有应用程序类的基类。 
 * 一个应用程序服务在用户请求处理的全局范围内。 
 * 它负责为整个应用程序管理 提供具体功能的应用程序组件。 
 * @author Jiangyumeng
 * 
 * @property string $id 应用的唯一标识。
 * @property string $name The name of this app
 * @property string $basePath 应用程序根目录。
 * @property Controller $controller 当前的Controller。
 * @property \Sky\db\ConnectionPool $db 数据库连接
 * @property \Sky\base\HttpRequest $request request 组件.
 * @property \Sky\caching\Cache $cache
 */

abstract class Application extends Module{
	
	/**
	 * @var string the charset currently used for the application. Defaults to 'UTF-8'.
	 */
	public $charset='UTF-8';
	
	private $_id;
	private $route;
	private $_basePath;
	private $_name;
	private $_ended=false;
	public $beginXprof=false;
	public $enableProf=false;
	public $profProbability=10000;

	
	/**
	 * 构造函数
	 * @param mixed $config 应用程序配置。 
	 * 如果是一个字符串，它将被当作包含配置的文件路径；
	 *  如果是一个数组，它将被当作具体的配置信息， 
	 *  你确保在配置文件中指定basePath属性， 
	 *  它通常应该指向含所有的应用程序逻辑、模板和数据的目录包。
	 */
	function __construct($config=null){
// 		\Sky\Sky::setApplication($this);
		\Sky\Sky::$app=$this;
		
		$this->registerErrorHandlers();
		$this->registerCoreComponents();
		
		$this->setAppConfig($config);
		$this->preloadComponents();
		
		$this->init();
		\Sky\Sky::beginXProfile();
	}
	
	/**
	 * 配置basepath，name和模块属性。
	 * @param mixed $config
	 */
	public function setAppConfig($config){
		if(is_string($config))
			$config=require($config);
		if(isset($config['basePath']))
		{
			$this->setBasePath($config['basePath']);
			unset($config['basePath']);
		}else 
			$this->setBasePath('');
		if(isset($config['name']))
		{
			$this->setName($config['name']);
			unset($config['name']);
		}
		
		$this->configure($config);
	}
	
	/**
	 * 处理请求。
	 * 子类要重写该方法。
	 * @return Response 响应的结果
	 */
	abstract public function processRequest();
	
	/**
	 *初始化exception handler和error handler。
	 */
	protected function registerErrorHandlers(){
		if(SKY_ENABLE_ERROR_HANDLER){
			ini_set('display_errors', 0);
			set_exception_handler(array($this,'handleException'));
			set_error_handler(array($this,'handleError'),error_reporting());
		}
	}
	
	/**
	 * 注册一些核心组件
	 */
	protected function registerCoreComponents(){
		$this->setComponents(array(
				'urlManager'=>array(
						'class'=>'Sky\base\UrlManager',
				),
				'request'=>array(
						'class'=>'Sky\base\HttpRequest',
				),
				'errorHandler'=>array(
						'class'=>'Sky\base\ErrorHandler',
				),
				'db'=>array(
						'class'=>'Sky\db\ConnectionPool',
				),
		));
	}
	
	public function getBasePath(){
		return $this->_basePath;
	}
	
	public function getName(){
		return $this->_name;
	}
	
	/**
	 * 处理未捕获的PHP异常。 
	 *
	 * 这个方法实现了一个PHP的异常handler。 它需要SKY_ENABLE_EXCEPTION_HANDLER被定义为true. 
	 *
	 * 应用程序会被该方法终止
	 *
	 * @param Exception $exception 未捕获的异常
	 */
	public function handleException($exception){
		// disable error capturing to avoid recursive errors
		restore_error_handler();
		restore_exception_handler();
		
		$category='exception.'.get_class($exception);
		if($exception instanceof HttpException)
			$category.='.'.$exception->statusCode;
		// php <5.2 doesn't support string conversion auto-magically
		$message=$exception->__toString();
		if(isset($_SERVER['REQUEST_URI']))
			$message.="\nREQUEST_URI=".$_SERVER['REQUEST_URI'];
		if(isset($_SERVER['HTTP_REFERER']))
			$message.="\nHTTP_REFERER=".$_SERVER['HTTP_REFERER'];
		$message.="\n---";
		\Sky\Sky::log($message,\Sky\logging\Logger::LEVEL_ERROR,$category);
		
		if(($handler=$this->getErrorHandler())!==null)
			$handler->handleException($exception);
		else
			$this->displayException($exception);
		$this->end(1);
	}
	
	/**
	 * 显示未捕获的PHP异常
	 * 当没有激活的error handler的时候该方法会在HTML中显示异常
	 * @param Exception $exception 未捕获的异常。
	 */
	public function displayException($exception){
		if(SKY_DEBUG){
			echo '<h1>'.get_class($exception).'</h1>\n';
			echo '<p>'.$exception->getMessage().' ('.$exception->getFile().':'.$exception->getLine().')</p>';
			echo '<pre>'.$exception->getTraceAsString().'</pre>';
		}else{
			echo '<h1>'.get_class($exception).'</h1>\n';
			echo '<p>'.$exception->getMessage().'</p>';
		}
	}
	
	/**
	 * 处理PHP异常错误，如警告，通知。
	 *
	 * 这个方法实现了一个PHP的error handler。 
	 * 它需要常量SKY_ENABLE_ERROR_HANDLER被定义为true。
	 *
	 * 应用程序将被此方法终止。
	 *
	 * @param integer $code 发起错误的等级
	 * @param string $message 错误消息
	 * @param string $file 发起错误的文件
	 * @param integer $line 发起错误的行号
	 */
	public function handleError($code,$message,$file,$line){
		if($code & error_reporting()){
			// disable error capturing to avoid recursive errors
			restore_error_handler();
			restore_exception_handler();
			
			$log=$message.' ('.$file.':'.$line.")\nStack trace:\n";
			$trace=debug_backtrace();
			// skip the first 3 stacks as they do not tell the error position
			if(count($trace)>3)
				$trace=array_slice($trace,3);
			foreach($trace as $i=>$t)
			{
				if(!isset($t['file']))
					$t['file']='unknown';
				if(!isset($t['line']))
					$t['line']=0;
				if(!isset($t['function']))
					$t['function']='unknown';
				$log.="#$i {$t['file']}({$t['line']}): ";
				if(isset($t['object']) && is_object($t['object']))
					$log.=get_class($t['object']).'->';
				$log.="{$t['function']}()\n";
			}
			if(isset($_SERVER['REQUEST_URI']))
				$log.='REQUEST_URI='.$_SERVER['REQUEST_URI'];
			$log.="\n---";
			\Sky\Sky::log($log,\Sky\logging\Logger::LEVEL_ERROR,'php');
			
			if (!class_exists('\Sky\base\ErrorHandler', false)) {
				require_once(__DIR__ . '/ErrorHandler.php');
			}
			
			if(($handler=$this->getErrorHandler())!==null)
				$handler->handleError($code,$message,$file,$line);
			else
				$this->displayError($code,$message,$file,$line);
			$this->end(1);
		}
	}
	
	/**
	 * 处理PHP致命错误
	 */
	public function handleFatalError()
	{
		if (SKY_ENABLE_ERROR_HANDLER) {
// 			unset($this->_memoryReserve);
			$error = error_get_last();
			if (!class_exists('\Sky\base\ErrorHandler', false)) {
				require_once(__DIR__ . '/ErrorHandler.php');
			}
	
			if (ErrorHandler::isFatalError($error)) {
				// use error_log because it's too late to use Sky log
				error_log($error['message']);
				
				if (($handler = $this->getErrorHandler()) !== null) {
					$handler->handleError($error['type'],$error['message'],$error['file'],$error['line']);
				} else {
					$this->displayError($error['type'],$error['message'],$error['file'],$error['line']);
				}
	
				exit(1);
			}
		}
	}
	
	/**
	 * 显示捕获到的PHP error
	 * 当没有error handler的时候该方法会在html中显示错误。
	 * @param integer $code 错误代码
	 * @param string $message 错误消息
	 * @param string $file 错误文件
	 * @param string $line 错误行
	 */
	public function displayError($code,$message,$file,$line){
		if(SKY_DEBUG)
		{
			echo "<h1>PHP Error [$code]</h1>\n";
			echo "<p>$message ($file:$line)</p>\n";
			echo '<pre>';
	
			$trace=debug_backtrace();
			// skip the first 3 stacks as they do not tell the error position
			if(count($trace)>3)
				$trace=array_slice($trace,3);
			foreach($trace as $i=>$t)
			{
				if(!isset($t['file']))
					$t['file']='unknown';
				if(!isset($t['line']))
					$t['line']=0;
				if(!isset($t['function']))
					$t['function']='unknown';
				echo "#$i {$t['file']}({$t['line']}): ";
				if(isset($t['object']) && is_object($t['object']))
					echo get_class($t['object']).'->';
				echo "{$t['function']}()\n";
			}
			echo '</pre>';
		}else{
				echo "<h1>PHP Error [$code]</h1>\n";
				echo "<p>$message</p>\n";
			}
		}
	
	/**
	 * 设置应用的根目录
	 * 该方法只能在构造函数的开始被引入。
	 * @param string $path 应用的根目录
	 * @throws \Exception 如果目录不存在。
	 */
	public function setBasePath($path){
		if(($this->_basePath=realpath($path))===false || !is_dir($this->_basePath))
			throw new \Exception('error basepath');
	}
	
	/**
	 * 返回UrlManager组件的实例
	 * @return \Sky\base\UrlManager urlManager的实例
	 */
	public function getUrlManager(){
		return $this->getComponent('urlManager');
	}
	
	/**
	 * 返回request组件的实例
	 * @return \Sky\base\HttpRequest request组件的实例
	 */
	public function getRequest(){
		return $this->getComponent('request');
	}
	
	/**
	 * 返回错误处理组件。
	 * @return \Sky\base\ErrorHandler 错误处理组件
	 */
	public function getErrorHandler(){
		return $this->getComponent('errorHandler');
	}
	
	public function setName($name){
		$this->_name=$name;
	}
	
	/**
	 * 返回数据库连接组件。
	 * @return \Sky\db\ConnectionPool 数据库连接
	 */
	public function getDb(){
		return $this->getComponent('db');
	}
	
	/**
	 * @return string 应用的唯一标识。
	 */
	public function getId(){
		if($this->_id!==null)
			return $this->_id;
		else
			return $this->_id=sprintf('%x',crc32($this->getBasePath().$this->_name));
	}
	
	/**
	 * @param string $id 应用的唯一标识。
	 */
	public function setId($id){
		$this->_id=$id;
	}
	
	/**
	 * Do not call this method. This method is used internally to search for a module by its ID.
	 * @param string $id module ID
	 * @return WebModule the module that has the specified ID. Null if no module is found.
	 */
	public function findModule($id){
			return $this->moduleExist($id);
	}
	
	/**
	 * @return Controller 当前的controller.Null is returned in this base class.
	 */
	public function getController(){
		return null;
	}
	
	/**
	 * 
	 * @return \Sky\caching\Cache
	 */
	public function getCache(){
		return $this->getComponent('cache');
	}
	
	function run($params=null){
// 		$this->_memoryReserve = str_repeat('x', 1024 * 256);
		register_shutdown_function(array($this,'end'),0,false);
		$response=$this->processRequest();
		$response->send();
		if($this->hasEventHandler('onEndRequest'))
			$this->onEndRequest(new Event($this));
	}
	
	/**
	 *为controller中具体的action创建一个相对URL。
	 * @param string $route URL路由。格式应该为 'ControllerID/ActionID'.
	 * @param array $params 多余的GET参数 (name=>value). name 和 value 都将被URL-encoded.
	 * @param string $ampersand 在URL中分割name-value对的字符。
	 * @return string 创建的URL
	 */
	public function createUrl($route,$params=array(),$ampersand='&'){
		return $this->getUrlManager()->createUrl($route,$params,$ampersand);
	}
	
	/**
	 * 在应用程序处理请求之后发起
	 * @param Event $event 事件参数
	 */
	public function onEndRequest($event){
		if(!$this->_ended){
			$this->_ended=true;
			\Sky\Sky::endXProfile('jym');
			$this->raiseEvent('onEndRequest',$event);
		}
	}
	
	public function end($status=0,$exit=true){
		$this->handleFatalError();
		if($this->hasEventHandler('onEndRequest'))
			$this->onEndRequest(new Event($this));
		
		if($exit)
			exit($status);	
	}
}