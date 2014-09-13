<?php
namespace Sky\console;

use Sky\base\Component;
use Sky\Sky;
/**
 * ConsoleCommandRunner 管理命令并执行用户请求的命令。
 * 
 * @property string $scriptName 入口脚本名
 * 
 * @author Jiangyumeng
 *
 */
class ConsoleCommandRunner extends Component{
	public $commands=array();
	
	private $_scriptName;
	
	/**
	 * 执行请求的命令。
	 * @param array $args 用户提供的参数列表 (包括入口脚本名和要执行的命令名).
	 * @return integer|null 命令返回的应用的退出码
	 * 如果返回的是null的话应用将不会显示的退出
	 */
	public function run($args){
		$this->_scriptName=$args[0];
		array_shift($args);
		if(isset($args[0])){
			$name=$args[0];
			array_shift($args);
		}else
			$name='help';
		
		if(($command=$this->createCommand($name))===null)
			$command=$this->createCommand('help');
		$command->init();
		return $command->run($args);
	}
	
	/**
	 * 从具体的命令路径添加命令
	 * 如果命令已经存在，则新的命令会被忽略。
	 * @param string $path 包含命令类文件的文件夹路径
	 */
	public function addCommands($path){
		if(($commands=$this->findCommands($path))!==array()){
			foreach($commands as $name=>$file){
				if(!isset($this->commands[$name]))
					$this->commands[$name]=$file;
			}
		}
	}
	
	/**
	 * @return string 入口脚本名
	 */
	public function getScriptName(){
		return $this->_scriptName;
	}
	
	/**
	 * 在指定目录下搜索命令文件（递归搜索）
	 * @param string $path 包含命令类文件的目录
	 * @return array 命令列表(command name=>command class file)
	 */
	public function findCommands($path){
		if(($dir=@opendir($path))===false)
			return array();
		$commands=array();
		while(($name=readdir($dir))!==false){
			$file=$path.DIRECTORY_SEPARATOR.$name;
			if(!strcasecmp(substr($name,-11),'Command.php') && is_file($file))
				$commands[strtolower(substr($name,0,-11))]=$file;
			elseif ($name!=='.' && $name!=='..' && is_dir($file)){
				$commands=array_merge($commands,$this->findCommands($file));
			}
		}
		closedir($dir);
		return $commands;
	}
	
	/**
	 * @param string $name 命令名 (大小写敏感)
	 * @return ConsoleCommand 命令对象. 如果名字非法的话返回null.
	 */
	public function createCommand($name){
		$name=strtolower($name);

		$command=null;
		if(isset($this->commands[$name]))
			$command=$this->commands[$name];
		else{
			$commands=array_change_key_case($this->commands);
			if(isset($commands[$name]))
				$command=$commands[$name];
		}
// 		echo $command;
		if($command!==null){
			if(is_string($command)) // class file path
			{
				if(strpos($command,'/')!==false){
					$classPathName=substr($command,strpos($command, Sky::$app->name),-4);
					
// 					$classPathName=substr($classPathName, strpos($classPathName, '/')+1);

					$className=str_replace( '/', '\\',$classPathName);
// 					echo $className;
					if(!class_exists($className,false))
						require_once($command);
				}else{
					throw new \Exception('command '.$command.' must be a filepath');
				}
				return new $className($name,$this);
			}else // an array configuration
				return Sky::createComponent($command,$name,$this);
		}elseif($name==='help')
			return new HelpCommand('help',$this);
		else
			return null;
	}
}