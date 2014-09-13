<?php
namespace Sky\base;
use \Sky\Sky;
/**
 * Module 是模块类和应用类的基类
 * 
 * Module主要管理应用组件和子模块。
 * 
 * @property array $modules 当前已经安转的模块
 * @property array $params 用户定义的参数数组。
 * @property string $basePath 应用程序根目录。
 * @property string $id 模块ID
 * @property string $modulePath 包含应用模块的目录。默认是{@link basePath}的子目录'modules'.
 * @property array $components 应用的组件
 * 
 * @author Jiangyumeng
 */
class Module extends Component{
	private $_id;
	
	private $_basePath;
	/**
	 * @var array 需要被预先加在的模块的ID
	 */
	public $preload=array();
	private $_params=array();
	private $_modulePath;
	private $_modules=array();
	private $_moduleConfig=array();
	private $_parentModule;
	
	private $_components=array();
	private $_componentConfig=array();
	
	/**
	 * 构造函数.
	 * @param string $id 当前模块的ID
	 * @param Module $parent 父模块，如果有的话。
	 * @param mixed $config 模块的配置。既可以是数组也可以是包含配置数组的PHP文件路径
	 */
	public function __construct($id,$parent,$config=null){
		$this->_id=$id;
		$this->_parentModule=$parent;
		
		if(is_string($config))
			$config=require($config);
		if(isset($config['basePath'])){
			$this->setBasePath($config['basePath']);
			unset($config['basePath']);
		}
		
		$this->configure($config);
		$this->init();
	}
	
	/**
	 * Getter 魔术方法
	 * 重写该方法是为了支持能够像访问模块的属性那样访问应用组件。
	 * @param string $name 应用组件或是属性名。
	 * @return mixed 
	 */
	public function __get($name){
		if($this->hasComponent($name))
			return $this->getComponent($name);
		else
			return parent::__get($name);
	}
	
	/**
	 * 检测一个属性值是否是null
	 * 该方法重写了父类的方法为了检测该组件是否被加载
	 * @param string $name 组件名或属性名
	 * @return boolean
	 */
	public function __isset($name){
		if($this->hasComponent($name))
			return $this->getComponent($name)!==null;
		else
			return parent::__isset($name);
	}
	
	/**
	 * 检测指定的组件是否存在
	 * @param string $id 应用组件ID
	 * @return boolean 是否该应用组件存在（包括被disabled）
	 */
	public function hasComponent($id){
		return isset($this->_components[$id]) || isset($this->_componentConfig[$id]);
	}
	
	/**
	 * 用具体的配置来配置模块
	 * @param array $config 配置数组
	 */
	public function configure($config){
		if(is_array($config)){
			foreach($config as $key=>$value)
				$this->$key=$value;
		}
	}
	
	/**
	 * 设置模块的根目录。
	 * 该方法只能在构造函数的开头被调用
	 * @param string $path 模块的根目录
	 * @throws \Exception 如果目录不存在
	 */
	public function setBasePath($path){
		if(($this->_basePath=realpath($path))===false || !is_dir($this->_basePath))
			throw new \Exception('Base path '.$path.' is not a valid directory.');
	}
	
	/**
	 * 预先加载组件
	 */
	protected function preloadComponents(){
		foreach($this->preload as $id)
		{
			$this->getComponent($id);
		}
		
	}
	
	/**
	 * 设置应用的多个组件
	 *
	 * 当配置当中要包括一个组件的时候，应该包括组件属性的初始值（name-value）。
	 * 除此之外，通过在配置中设置'enabled'值，一个组件可以被打开（默认）或禁用。
	 *
	 * 如果一个配置和当前存在的组件同名，则组件的配置会被替换。
	 *
	 * 以下是两个组件的配置：
	 * <pre>
	 * array(
	 *     'session'=>array(
	 *					'class'=>'demos\components\SkySession',
	 *		),
	 *     'cache'=>array(
	 *					'class'=>'Sky\caching\MemCache',
	 *					'keyPrefix'=>'',
	 *					'enabled'=>true,
	 * 					'servers'=>array(
	 *							array('host'=>MEMCACHE_IP, 'port'=>MEMCACHE_PORT),
	 *					)
	 *		),
	 * )
	 * </pre>
	 *
	 * @param array $components 应用的多个组件(id=>component)
	 */
	public function setComponents($components){
		foreach($components as $id=>$component)
			$this->setComponent($id,$component);
	}
	
	/**
	 * 将一个组件交给模块管理
	 * 通过调用他的{@link ApplicationComponent::init() init()}方法，组件会被初始化
	 * @param string $id 组件ID
	 * @param array $component 应用组件。如果该参数是null的话，该组件会从模块上卸下。
	 */
	public function setComponent($id,$component){
		if($component===null){
			unset($this->_components[$id]);
			return;
		}elseif(isset($this->_components[$id])){
			return ;
		}
	
		if(isset($this->_componentConfig[$id])){
			$this->_componentConfig[$id]=self::mergeArray($this->_componentConfig[$id],$component);
		}else{
			$this->_componentConfig[$id]=$component;
		}
	}
	
	/**
	 * 取回应用组件
	 * @param string $id 应用组件ID (大小写敏感)
	 * @param boolean $createIfNull 如果不存在的话是否创建组件。
	 * @return Component 应用组件实例，如果组件被禁用或不存在的话返回null。
	 * @see hasComponent
	 */
	public function getComponent($id,$createIfNull=true){
		if(isset($this->_components[$id]))
			return $this->_components[$id];
		elseif(isset($this->_componentConfig[$id]) && $createIfNull){
			$config=$this->_componentConfig[$id];
			if(!isset($config['enabled']) || $config['enabled'])
			{
				Sky::trace('Loading '.$id.' application component','system.Component');
				unset($config['enabled']);
				$component=Sky::createComponent($config);
				$component->init();
				return $this->_components[$id]=$component;
			}
		}
	}
	
	/**
	 * 设置模块的namespace
	 * @param array $namespace 要导入的namespace列表。
	 */
	public function setImport($namespaces){
		foreach($namespaces as $namespace)
			Sky::import($namespace);
	}
	
	/**
	 * 返回模块所在的根目录
	 * @return string 模块所在的根目录。
	 */
	public function getBasePath(){
		if($this->_basePath===null){
			$class=new \ReflectionClass(get_class($this));
			$this->_basePath=dirname($class->getFileName());
		}
		
		return $this->_basePath;
	}
	
	/**
	 * 返回模块 ID.
	 * @return string 模块 ID.
	 */
	public function getId(){
		return $this->_id;
	}
	
	/**
	 * 设置模块 ID.
	 * @param string $id 模块 ID
	 */
	public function setId($id){
		$this->_id=$id;
	}
	
	/**
	 * 返回父模块。
	 * @return Module 父模块。如果没有父模块的话返回null。
	 */
	public function getParentModule(){
		return $this->_parentModule;
	}
	
	/**
	 * 取回已经配置的模块。
	 * 模块必须要在 {@link modules}中声明。
	 * 当通过id第一次调用该方法的时候，一个新的实例将会创建。
	 * @param string $id 模块 ID (大小写敏感)
	 * @return Module 模块实例, 如果模块被禁用或不存在的话返回null。
	 */
	public function getModule($id){
// 		Sky::log('get module '.$id);
		if(isset($this->_modules[$id]) || array_key_exists($id,$this->_modules)){
// 			Sky::log($this->_modules[$id]);
			return $this->_modules[$id];
		}
		elseif(isset($this->_moduleConfig[$id])){
			
			$config=$this->_moduleConfig[$id];

			if(!isset($config['enabled']) || $config['enabled'])
			{
				Sky::trace('Loading '.$id.' module','system.base.Module');
				$class=$config['class'];
				unset($config['class']);
	// 			\Sky\Sky::log('this is class '.$class);
				if($this===Sky::$app){
	// 				Sky::log('createcompontn '.$id);
					$module=Sky::createComponent($class,$id,null,$config);
	// 				Sky::log('createcompontn over '.$id);
				}else{
	// 				Sky::log($this->getId().'/'.$id);
					$module=Sky::createComponent($class,/*$this->getId().'/'.*/$id,$this,$config);
	// 				Sky::log('set child module finish');
				}
				
				$this->_modules[$id]=$module;
	// 			Sky::log('set modules array');
				return $module;
			}
		}
	}
	
	/**
	 * 配置当前模块的子模块。
	 *
	 * 调用该方法来声明子模块并用它们的初始属性值配置它们。
	 * 参数应该是一个包含模块配置的数组。数组的每一个元素代表了一个模块。
	 * 它既可以是一个代表模块id的字符串，也可以是一个包含模块配置的数组。
	 *
	 * 例如，下面的数组声明了两个模块：
	 * <pre>
	 * array(
	 *     'admin',                //一个单独的模块 ID
	 *     'payment'=>array(       //包含配置的数组
	 *         'server'=>'paymentserver.com',
	 *     ),
	 * )
	 * </pre>
	 *
	 * 你也可以通过配置'enabled'属性来启用或禁用一个模块。
	 * 
	 * @param array $modules 模块配置。
	 */
	public function setModules($modules){
		foreach($modules as $id=>$module){
			if(is_int($id)){
				$id=$module;
				$module=array();
			}
			if(!isset($module['class'])){
				if(($pos=strrpos($id,'\\'))!==false){
					$tid=substr($id,$pos+1);
					$module['class']=$id.'\\'.ucfirst($tid).'Module';					
// 					$id=$tid;
					$id=str_replace('\\', '/', $id);

				}else{
					$namespace=$id.'\\'.ucfirst($id).'Module';
					// 				Sky::log('SET module path:'.$this->getModulePath());
					// 				Sky::log('get class name:'.get_class($this).'***** namespace :'.$namespace);
					Sky::setPathofNamespace($namespace,$this->getModulePath().DIRECTORY_SEPARATOR.$id);
					// 				Sky::setPathofNamespace($id,$this->getModulePath().DIRECTORY_SEPARATOR.$id);
					$module['class']=$namespace;
				}
			}
			if(isset($this->_moduleConfig[$id]))
				$this->_moduleConfig[$id]=self::mergeArray($this->_moduleConfig[$id],$module);
			else
				$this->_moduleConfig[$id]=$module;
		}
	}
	
	/**
	 * 获取模块所在路径。
	 * @return string 模块所在路径。
	 */
	public function getModulePath(){
		if($this->_modulePath!==null)
			return $this->_modulePath;
		else
			return $this->_modulePath=$this->getBasePath();
// 			return $this->_modulePath=$this->getBasePath().DIRECTORY_SEPARATOR.'modules';
	}
	
	/**
	 * 根据模块id判断模块是否存在。
	 * @param string $id 模块id
	 * @return boolean 存在返回true，否则返回false。
	 */
	public function moduleExist($id){
// 		var_dump($this->_modules);
		if(isset($this->_modules[$id]) || array_key_exists($id,$this->_modules) || isset($this->_moduleConfig[$id])){
			return true;
		}
		return false;
	}
	
	/**
	 * 返回用户自定义的参数。
	 */
	public function getParams(){
		return $this->_params;
	}
	
	/**
	 * 设置用户自定义参数。
	 */
	public function setParams($params){
		foreach($params as $k=>$v){
			$this->_params[$k]=$v;
		}
	}
	
	public static function mergeArray($a,$b){
		$args=func_get_args();
		$res=array_shift($args);
		while(!empty($args)){
			$next=array_shift($args);
			foreach($next as $k => $v){
				if(is_integer($k))
					isset($res[$k]) ? $res[]=$v : $res[$k]=$v;
				elseif(is_array($v) && isset($res[$k]) && is_array($res[$k]))
				$res[$k]=self::mergeArray($res[$k],$v);
				else
					$res[$k]=$v;
			}
		}
		return $res;
	}
}