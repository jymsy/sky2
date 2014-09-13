<?php
namespace Sky\console;

use Sky\Sky;
/**
 * HelpCommand代表控制台帮助命令。
 * HelpCommand显示可用的命令列表或是关于指定命令的帮助指示。
 * 
 * 如果要使用这个命令，输入下面的指令：
 * <pre>
 * 		php path/to/entry_script.php help [command name]
 * </pre>
 * 如果没有提供命令名的话他会显示所有可用的命令。
 * 
 * @property string $help 命令帮助描述
 * 
 * @author Jiangyumeng
 *
 */
class HelpCommand extends ConsoleCommand{
	
	/**
	 * 执行动作
	 * @param array $args 命令行参数
	 * @return integer 非0的应用退出码
	 */
	public function run($args){
		$runner=$this->getCommandRunner();
		$commands=$runner->commands;
		if(isset($args[0]))
			$name=strtolower($args[0]);
		if(!isset($args[0]) || !isset($commands[$name])){
			if(!empty($commands)){
				echo "Sky command runner (based on Sky v".Sky::getVersion().")\n";
				echo "Usage: ".$runner->getScriptName()." <command-name> [parameters...]\n";
				echo "\nThe following commands are available:\n";
				$commandNames=array_keys($commands);
				sort($commandNames);
				echo ' - '.implode("\n - ",$commandNames);
				echo "\n\nTo see individual command help, use the following:\n";
				echo "   ".$runner->getScriptName()." help <command-name>\n";
			}else{
				echo "No available commands.\n";
				echo "Please define them under the following directory:\n";
				echo "\t".Sky::$app->getCommandPath()."\n";
			}
		}else
			echo $runner->createCommand($name)->getHelp();
		return 1;
	}
	
	/**
	 * 提供命令帮助
	 * @return string 命令帮助
	 */
	public function getHelp(){
		return parent::getHelp().' [command-name]';
	}
}