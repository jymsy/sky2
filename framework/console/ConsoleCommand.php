<?php
namespace Sky\console;

use Sky\base\Component;
/**
 * ConsoleCommand代表一个可执行的控制台命令。 
 * 
 * 它的工作原理跟 {@link \Sky\base\Controller}一样，通过解析命令行选项并根据适当的选项值将请求调度到一个指定的动作。 
 * 用户通过以下命令格式来调用控制台命令：
 * <pre>
 * 		php skyc.php CommandName ActionName --Option1=Value1 --Option2=Value2 ...
 * </pre>
 * 
 * 子类主要实现各种动作方法，这个动作方法名必须以“action”作为前缀。
 * 传递给动作方法的参数被认为是指定动作的选项。
 * 通过指定defaultAction来实现当用户没有在命令里面明确调用某个命令时默认会调用哪个动作。
 * 
 * 选项通过参数名称绑定到动作参数。
 * 例如，下面的动作方法会允许我们运行命令skyc sitemap --type=News:
 * <pre>
 * 	class SitemapCommand {
 * 		public function actionIndex($type) {
 *         ....
 *     }
 * }	
 * </pre>
 * 
 * @property string $name 命令名
 * @property ConsoleCommandRunner $commandRunner command runner实例
 * @author Jiangyumeng
 *
 */
abstract class ConsoleCommand extends Component{
	/**
	 * @var string 默认action的名字。默认为'index'
	 */
	public $defaultAction='index';
	
	private $_name;
	private $_runner;
	
	/**
	 * 构造函数
	 * @param string $name 命令名字
	 * @param ConsoleCommandRunner $runner  command runner
	 */
	public function __construct($name,$runner){
		$this->_name=$name;
		$this->_runner=$runner;
	}
	
	/**
	 * 初始化command对象。
	 * 这个方法是在命令对象被创建并根据配置初始化之后执行的。
	 * 你可以重写这个方法，使它在命令执行之前来做更多的处理。
	 */
	public function init()
	{
	}
	
	/**
	 * 提供命令帮助描述。
	 * 该方法可以被重写来返回自定义的描述。
	 * @return string 命令帮助描述。默认为 'Usage: php entry-script.php command-name'.
	 */
	public function getHelp(){
		$help='Usage: '.$this->getCommandRunner()->getScriptName().' '.$this->getName();
		$options=$this->getOptionHelp();
		if(empty($options))
			return $help;
		if(count($options)===1)
			return $help.' '.$options[0];
		$help.=" <action>\nActions:\n";
		foreach($options as $option)
			$help.='    '.$option."\n";
		return $help;
	}
	
	/**
	 * 提供命令选项的帮助信息。
	 * 默认的实现是返回所有可用的动作，同时返回相关的选项信息。
	 * @return array 命令选项帮助信息。每一个数组元素描述了一个独立的动作帮助信息。
	 */
	public function getOptionHelp(){
		$options=array();
		$class=new \ReflectionClass(get_class($this));
		foreach($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method){
			$name=$method->getName();
			if(!strncasecmp($name,'action',6) && strlen($name)>6){
				$name=substr($name,6);
				$name[0]=strtolower($name[0]);
				$help=$name;
	
				foreach($method->getParameters() as $param){
					$optional=$param->isDefaultValueAvailable();
					$defaultValue=$optional ? $param->getDefaultValue() : null;
					$name=$param->getName();
					if($optional)
						$help.=" [--$name=$defaultValue]";
					else
						$help.=" --$name=value";
				}
				$options[]=$help;
			}
		}
		return $options;
	}
	
	/**
	 * 运行命令
	 * 默认的实现是解析输入参数，然后根据命令请求的选项值执行对应的动作。
	 * @param array $args 得到的命令行参数
	 * @return integer 应用退出码。如果动作没有任何返回信息的话为0.
	 */
	public function run($args){
		list($action, $options, $args)=$this->resolveRequest($args);
		$methodName='action'.$action;
		if(!preg_match('/^\w+$/',$action) || !method_exists($this,$methodName))
			$this->usageError("Unknown action: ".$action);
		
		$method=new \ReflectionMethod($this,$methodName);
		$params=array();
		// named and unnamed options
		foreach($method->getParameters() as $i=>$param){
			$name=$param->getName();
			if(isset($options[$name])){
				if($param->isArray())
					$params[]=is_array($options[$name]) ? $options[$name] : array($options[$name]);
				elseif(!is_array($options[$name]))
					$params[]=$options[$name];
				else
					$this->usageError("Option --$name requires a scalar. Array is given.");
			}
			elseif($name==='args')
				$params[]=$args;
			elseif($param->isDefaultValueAvailable())
				$params[]=$param->getDefaultValue();
			else
				$this->usageError("Missing required option --$name.");
			unset($options[$name]);
		}
		
		// try global options
		if(!empty($options)){
			$class=new \ReflectionClass(get_class($this));
			foreach($options as $name=>$value){
				if($class->hasProperty($name)){
					$property=$class->getProperty($name);
					if($property->isPublic() && !$property->isStatic()){
						$this->$name=$value;
						unset($options[$name]);
					}
				}
			}
		}
		
		if(!empty($options))
			$this->usageError("Unknown options: ".implode(', ',array_keys($options)));
		
// 		$exitCode=0;
// 		if($this->beforeAction($action,$params)){
			$exitCode=$method->invokeArgs($this,$params);
// 			$exitCode=$this->afterAction($action,$params,is_int($exitCode)?$exitCode:0);
// 		}
		return is_int($exitCode)?$exitCode:0;
	}
	
	/**
	 * 显示使用错误。
	 * 该方法会终止当前正在执行的应用。
	 * @param string $message 错误信息
	 */
	public function usageError($message){
		echo "Error: $message\n\n".$this->getHelp()."\n";
		exit(1);
	}
	
	/**
	 * 解析命令行参数并决定执行哪个action
	 * @param array $args 命令行参数
	 * @return array action名, 命名的选项(name=>value)和未命名选项
	 */
	protected function resolveRequest($args){
		$options=array();	// named parameters
		$params=array();	// unnamed parameters
		
		foreach($args as $arg){
			if(preg_match('/^--(\w+)(=(.*))?$/',$arg,$matches)){ // an option
				$name=$matches[1];
				$value=isset($matches[3]) ? $matches[3] : true;
				if(isset($options[$name])){
					if(!is_array($options[$name]))
						$options[$name]=array($options[$name]);
					$options[$name][]=$value;
				}else
					$options[$name]=$value;
			}elseif(isset($action))
				$params[]=$arg;
			else
				$action=$arg;
		}
		if(!isset($action))
			$action=$this->defaultAction;
	
		return array($action,$options,$params);
	}
	
	/**
	 * @return string 命令名
	 */
	public function getName(){
		return $this->_name;
	}
	
	/**
	 * @return ConsoleCommandRunner command runner实例
	 */
	public function getCommandRunner(){
		return $this->_runner;
	}
}