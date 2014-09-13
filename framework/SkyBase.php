<?php
namespace Sky;
use Sky\base\Application;

/**
 * SkyBase class file.
 */
/**
 * 获取应用的开始时间
 */
defined('SKY_BEGIN_TIME') or define('SKY_BEGIN_TIME', microtime(true));
/**
 * 定义Sky Framework的安装目录
 */
defined('SKY_PATH') or define('SKY_PATH', __DIR__);
/**
 * 定义Framework是否要捕获错误，默认为true
*/
defined('SKY_ENABLE_ERROR_HANDLER') or define('SKY_ENABLE_ERROR_HANDLER', true);
/**
 * 定义应用是否在debug模式，默认为false
 */
defined('SKY_DEBUG') or define('SKY_DEBUG', false);
/**
 * 该项定义了Sky::trace()要记录的信息(文件名和行号)的个数
 * 默认为0，意味着没有backtrace信息 
 * 如果大于零的话则最多记录该条信息，
 * 注意，只有应用的trace信息会被记录
 */
defined('SKY_TRACE_LEVEL') or define('SKY_TRACE_LEVEL', 0);

/**
 * SkyBase是一个助手类，它服务于整个框架。
 * 不要直接使用SkyBase。相反，你应该使用它的子类{@link sky}， 
 * 你可以在sky中定制SkyBase的方法。
 * 
 * @author Jiangyumeng
 */
class SkyBase
{
	
	/**
	 * @return string Sky framework的版本
	 */
	public static function getVersion(){
		return '0.0.1';
	}
	
	/**
	 * @var array class map 用于skyframework的自动加载机制。
	 * 数组的key是类的namespace，对应的value是类文件路径。
	 */
	private static $classMap=array();
	private static $_imports=array();
	/**
	 * @var \Sky\console\ConsoleApplication|\Sky\web\WebApplication 应用实例
	 */
	public static $app;
	private static $_logger;
	
	private static $_paths=array('Sky'=>SKY_PATH);
	
	/**
	 * 创建一个应用程序实例
     * @param string $class 应用类名
	 * @param mixed $config 应用程序配置。
	 * 如果是string的话，则是配置文件的路径；
	 * 如果是数组的话，则是配置文件本身。
	 * 请确保你在配置文件中设置了 {@link Application::basePath} 属性。
	 * 它应该指向应用程序的根路径。
	 * @return Application
	 */
	public static function createApplication($class,$config=null){
//		return new \Sky\base\Application($config);
        return new $class($config);
	}
	
	/**
	 * 创建一个控制台应用实例
	 * @param mixed $config 应用配置
	 * 如果是string的话，则是配置文件的路径；
	 * 如果是数组的话，则是配置文件本身。
	 * 请确保你在配置文件中设置了 {@link Application::basePath} 属性。
	 * 它应该指向应用程序的根路径。
	 * @return \Sky\console\ConsoleApplication
	 */
	public static function createConsoleApplication($config=null){
		return new \Sky\console\ConsoleApplication($config);
	}
	
	public static function createWebApplication($config=null){
		return new \Sky\web\WebApplication($config);
	}
	
	/**
	 * 返回应用程序实例，如果实例还没创建为null。
	 * @return Application
	 */
	public static function app(){
		return static::$app;
	}
	
	/**
	 * 在类静态成员中存储应用程序实例。
	 * 
	 *  这个方法帮助实现Application的单例模块。 
	 *  重复调用该方法或Application构造器 将抛出一个异常。 
	 *  使用{@link app()}获取应用程序实例。
	 * @param Application $app 应用程序实例。
	 * 如果为null， 这个已经存在的应用程序实例将被移除。
	 * @throws \Exception 如果多个应用程序实例被注册的话。
	 */
	public static function setApplication($app){
		if(static::$app===null || $app===null)
            static::$app=$app;
		else
			throw new \Exception('application can only be created once.');
	}
	
	/**
	 * 写入trace信息。
	 * 该方法只在debug模式的时候记录日志。
	 * @param string $msg 要记录的信息
	 * @param string $category 信息分类。
	 */
	public static function trace($msg,$category='application'){
		if(SKY_DEBUG)
			self::log($msg,\Sky\logging\Logger::LEVEL_TRACE,$category);
	}
	
	/**
	 * 记录日志信息。
	 * 通过该方法记录的日志信息可以由{@link \Sky\logging\Logger::getLogs}获得，
	 * 通过{@link \Sky\logging\LogRouter}也可以记录到不同的地方，
	 * 例如文件，邮件，数据库
	 * @param string $msg 要记录的信息
	 * @param string $level 信息的级别 (比如 'trace', 'warning', 'error').大小写敏感
	 * @param string $category 信息分类 (比如 'system.web'). 大小写敏感
	 */
	public static function log($msg,$level=\Sky\logging\Logger::LEVEL_INFO,$category='application'){
		if(self::$_logger===null)
			self::$_logger=new \Sky\logging\Logger;
		if(SKY_DEBUG && SKY_TRACE_LEVEL>0 && $level!==\Sky\logging\Logger::LEVEL_PROFILE && $level!=\Sky\logging\Logger::LEVET_BI){
			$traces=debug_backtrace();
			$count=0;
			foreach($traces as $trace){
				if(isset($trace['file'],$trace['line']) && strpos($trace['file'],SKY_PATH)!==0){
					$msg.="\nin ".$trace['file'].' ('.$trace['line'].')';
					if(++$count>=SKY_TRACE_LEVEL)
						break;
				}
			}
		}
		self::$_logger->log($msg,$level,$category);
	}
	
	/**
	 * 标记用来分析的一块代码的开始位置。
	 * 标记开始分析一块代码。它必须有一个同样token的{@link endProfile()}匹配。 
	 * begin-和end-的调用必须是正确的嵌套，例如:
	 * <pre>
	 * \Sky\Sky::beginProfile('block1');
	 * \Sky\Sky::beginProfile('block2');
	 * \Sky\Sky::endProfile('block2');
	 * \Sky\Sky::endProfile('block1');
	 * </pre>
	 * 下面的语句是无效的：
	 * <pre>
	 * \Sky\Sky::beginProfile('block1');
	 * \Sky\Sky::beginProfile('block2');
	 * \Sky\Sky::endProfile('block1');
	 * \Sky\Sky::endProfile('block2');
	 * </pre>
	 * @param string $token 代码块的标记
	 * @param string $category 日志信息类别
	 */
	public static function beginProfile($token,$category='application'){
		self::log('begin:'.$token,\Sky\logging\Logger::LEVEL_PROFILE,$category);
	}
	
	/**
	 * 标记用来分析的一块代码的结束位置。 
	 * 它必须有一个同样token的{@link beginProfile()}匹配。
	 * @param string $token 代码块标记
	 * @param string $category 日志信息类别
	 * @see beginProfile
	 */
	public static function endProfile($token,$category='application'){
		self::log('end:'.$token,\Sky\logging\Logger::LEVEL_PROFILE,$category);
	}
	
	/**
	 * 开始XHProfile。{@link Application::enableProf}要为true
	 * @param number $flag
	 * @param array $options
	 */
	public static function beginXProfile($flag=0,$options=array()){
// 		if(SKY_DEBUG)
		if(static::$app->enableProf && mt_rand(1, static::$app->profProbability) == 1)
		{
			xhprof_enable($flag,$options);
            static::$app->beginXprof=true;
		}
	}
	
	/**
	 * 结束XHProfile。
	 * @param string $type
	 * @return string 保存的路径
	 */
	public static function endXProfile($type){
		if(/*SKY_DEBUG && */static::$app->beginXprof){
            static::$app->beginXprof=false;
			$xhprof_data = xhprof_disable();
			include_once SKY_PATH.'/logging/xhprof_lib/utils/xhprof_lib.php';
			include_once SKY_PATH.'/logging/xhprof_lib/utils/xhprof_runs.php';
			$xhprof_runs = new \XHProfRuns_Default();
			return $xhprof_runs->save_run($xhprof_data, $type);
		}
	}
	
	/**
	 * 创建一个对象并根据指定的配置初始化。
	 *  
	 * 指定的配置可以是一个字符串或一个数组。
	 * 如果是前者，该字符串被当作（指定一个类名的）对象类型。 
	 * 如果是后者，‘class’元素对当作对象类型对待， 并且数组中其余的键值对被用作初始化相应的对象属性。 
	 * 传递给这个方法的任何额外的参数 将传递给将要创建的component对象的构造方法。
	 * @param mixed $config 配置信息，array或string
	 * @throws \Exception 如果配置信息中没有class元素。
	 * @return mixed the created object
	 */
	public static function createComponent($config){
		if(is_string($config)){
			$type=$config;
			$config=array();
			
		}elseif(isset($config['class'])){
			$type=$config['class'];
			unset($config['class']);
		}
		else
			throw new \Exception('Object configuration must be an array containing a "class" element.');
// 		\Sky\Sky::log("type".$type);
		if(!class_exists($type,false))
			$type=static::import($type,true);
		
		if(($n=func_num_args())>1){
			$args=func_get_args();
			if($n===2)
				$object=new $type($args[1]);
			elseif($n===3)
				$object=new $type($args[1],$args[2]);
			elseif($n===4)
				$object=new $type($args[1],$args[2],$args[3]);
			else{
				unset($args[0]);
				$class=new \ReflectionClass($type);
				// Note: ReflectionClass::newInstanceArgs() is available for PHP 5.1.3+
				$object=$class->newInstanceArgs($args);
// 				$object=call_user_func_array(array($class,'newInstance'),$args);
			}
		}
		else
			$object=new $type;
	
		foreach($config as $key=>$value)
			$object->$key=$value;
	
		return $object;
	}
	
	/**
	 * 使用namespace的格式导入一个类。
	 * @param string $namespace 导入类的namespace
	 * @param boolean $forceInclude 是否立即包含类文件。 如果为flase，则类文件仅在被使用时包含。
	 * @throws \Exception 非法namespace。
	 * @return string 类的namespace或者是类的文件路径。
	 */
	public static function import($namespace,$forceInclude=false){
		if(isset(self::$_imports[$namespace]))  // previously imported
			return self::$_imports[$namespace];
		
		if(class_exists($namespace,false) || interface_exists($namespace,false))
			return self::$_imports[$namespace]=$namespace;
		
		if(($pos=strpos($namespace,'\\'))!==false){ // a class name in PHP 5.3 namespace format
			if(($path=self::getPathofNamespace($namespace))!==false){
// 				\Sky\Sky::log("import namespace:".$namespace);
				$classFile=$path.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, substr($namespace,$pos+1)).'.php';

				if($forceInclude){
					if(is_file($classFile)){
// 						\Sky\Sky::log("require file:".$classFile);
						require($classFile);
					}else
						throw new \Exception('classFile "'.$classFile.'" is invalid. Make sure it points to an existing PHP file and the file is readable.');
					self::$_imports[$namespace]=$namespace;
				}else
					self::$classMap[$namespace]=$classFile;
				return $namespace;
			}
		}else 
			throw new \Exception('Namespace "'.$namespace.'" is invalid. Make sure the format is correct.');
	}
	
	/**
	 * @param string $namespace 类namespace
	 * @return string|boolean
	 */
	public static function getPathofNamespace($namespace){
// 		var_dump(self::$_paths);
		if(isset(self::$_paths[$namespace]))
			return self::$_paths[$namespace];
		elseif(($pos=strpos($namespace,'\\'))!==false){
			$rootPath=substr($namespace,0,$pos);
			if(isset(self::$_paths[$rootPath])){
				return self::$_paths[$namespace]=SKY_PATH;
			}else if(static::$app->findModule($rootPath)!==false){
// 				return self::$_paths[$namespace]=static::$app->basePath;
// 				static::$app->getModule($rootPath);
					return self::$_paths[$namespace]=self::$_paths[$rootPath.'\\'.ucfirst($rootPath).'Module'];
			}else{
				return self::$_paths[$namespace]=static::$app->basePath;
			}
		}
		return false;
	}
	
	public static function setPathofNamespace($namespace,$path){
		if(empty($path))
			unset(self::$_paths[$namespace]);
		else
			self::$_paths[$namespace]=rtrim($path,'\\/');
// 		\Sky\Sky::log($namespace,'info','setpath');
	}
	
	/**
	 * @return \Sky\logging\Logger 消息日志对象
	 */
	public static function getLogger(){
		if(self::$_logger!==null)
			return self::$_logger;
		else
			return self::$_logger=new \Sky\logging\Logger;
	}
	
	/**
	 * 类自动加载器。 这个方法是提供给__autoload()魔术方法使用。
	 * @param string $className 类名
	 * @throws \Exception 类文件不存在
	 * @return boolean 是否类被成功加载
	 */
	public static function autoload($className){
		$className=ltrim($className,'\\');
		if(isset(self::$classMap[$className])){
			include(self::$classMap[$className]);
		}elseif(($pos=strpos($className,'\\'))!==false){

			if(($path=self::getPathOfNamespace($className))!==false){	
				$filepath=$path.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, substr($className,$pos+1)).'.php';
			}else 
				return false;
// 			$filepath=SKY_PATH.str_replace(array(__NAMESPACE__,"\\"), array("",DIRECTORY_SEPARATOR), $className).".php";
// 			echo $filepath.':'."\n";
			if(is_file($filepath)){
// 				echo "include file :".$filepath."\n";
				include($filepath);
			}else{
				if(SKY_DEBUG)
					throw new \Exception('the file path '.$filepath.' of the '.$className.' is not exist.');
			}
			return class_exists($className,false) || interface_exists($className,false);
		}
		return true;
	}
}
