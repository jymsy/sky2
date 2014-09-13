<?php
namespace Sky\console;

use Sky\base\Application;
use Sky\base\ErrorHandler;
/**
 * ConsoleApplication代表一个控制台应用程序。 
 * ConsoleApplication继承于{@link Application}，提供了一些功能来处理控制台请求。 
 * 一般来说，它是通过基于命令的方法来处理那些请求：
 * <ul>
 * <li>一个控制台应用包含着一个或者多个可能的用户命令；</li>
 * <li>每条用户命令都是以类的形式实现的，继承于{@link \Sky\console\ConsoleCommand};</li>
 * <li>用户指定哪些命令会通过命令行运行；</li>
 * <li>命令程序根据指定的参数处理用户请求。</li>
 * </ul>
 * 
 * 命令类是放在目录{@link getCommandPath commandPath}下面。 
 * 这些类的命名规则是：<command-name>Command，文件名字跟类的名字一样。 
 * 例如，'ShellCommand'类定义了 一个'shell'命令， 它的文件名字为'ShellCommand.php'。 
 * 
 * 输入以下指令来运行命令行应用：
 * <pre>
 * php path/to/entry_script.php <command name> [param 1] [param 2] ...
 * </pre>
 * 
 * 你可以使用下面命令来查看帮助说明：
 * <pre>
 * php path/to/entry_script.php help <command name>
 * </pre>
 * 
 * @property string $commandPath 包含命令类的目录。默认是'commands'。
 * @property ConsoleCommandRunner $commandRunner The command runner.
 * 
 * @author Jiangyumeng
 *
 */
class ConsoleApplication extends Application
{
	private $_runner;
	private $_commandPath;
	public $commandMap=array();
	/**
	 * @var integer the size of the reserved memory. A portion of memory is pre-allocated so that
	 * when an out-of-memory issue occurs, the error handler is able to handle the error with
	 * the help of this reserved memory. If you set this value to be 0, no memory will be reserved.
	 * Defaults to 256KB.
	 */
	public $memoryReserveSize = 262144;
	/**
	 * @var string Used to reserve memory for fatal error handler.
	 */
	private $_memoryReserve;
	
	/**
	 * 通过创建command runner来初始化这个应用。
	 */
	protected function init()
	{
		if(!isset($_SERVER['argv'])) // || strncasecmp(php_sapi_name(),'cli',3))
			die('This script must be run from the command line.');
		if ($this->memoryReserveSize > 0) {
			$this->_memoryReserve = str_repeat('x', $this->memoryReserveSize);
		}
		$this->_runner=$this->createCommandRunner();
		$this->_runner->commands=$this->commandMap;
		$this->_runner->addCommands($this->getCommandPath());
	}
	
	/**
	 * 处理用户请求。
	 * 该方法使用command runner来处理用户命令
	 */
	public function processRequest()
	{
		$exitCode=$this->_runner->run($_SERVER['argv']);
		if(is_int($exitCode))
			$this->end($exitCode);
	}
	
	/**
	 * 创建command runner实例
	 * @return ConsoleCommandRunner command runner实例
	 */
	protected function createCommandRunner()
	{
		return new ConsoleCommandRunner;
	}
	
	/**
	 * 返回command runner
	 * @return ConsoleCommandRunner
	 */
	public function getCommandRunner()
	{
		return $this->_runner;
	}
	
	/**
	 * @return string 包含命令类文件的目录。默认是'commands'.
	 */
	public function getCommandPath()
	{
		$applicationCommandPath = $this->getBasePath().DIRECTORY_SEPARATOR.'commands';
		if($this->_commandPath===null && file_exists($applicationCommandPath))
			$this->setCommandPath($applicationCommandPath);
		return $this->_commandPath;
	}
	
	/**
	 * @param string $value 包含命令类文件的目录。
	 * @throws \Exception 如果目录非法
	 */
	public function setCommandPath($value)
	{
		if(($this->_commandPath=realpath($value))===false || !is_dir($this->_commandPath))
			throw new \Exception('The command path "'.$value.'" is not a valid directory.');
	}
	
	/**
	 * 处理PHP致命错误
	 */
	public function handleFatalError()
	{
		if (SKY_ENABLE_ERROR_HANDLER) {
			unset($this->_memoryReserve);
			$error = error_get_last();
			if (!class_exists('\Sky\base\ErrorHandler', false)) {
				require_once(__DIR__ . '/../base/ErrorHandler.php');
			}
			if (ErrorHandler::isFatalError($error)) {
				error_log($error['message']);
				$this->displayError($error['type'],$error['message'],$error['file'],$error['line']);
				exit(1);
			}
		}
	}
	
	/**
	 * 显示捕获的PHP错误。
	 * 在命令行模式下当没有激活的error handler的时候会执行该方法。
	 * @param integer $code 错误码
	 * @param string $message 错误消息
	 * @param string $file 报错文件
	 * @param string $line 错误行
	 */
	public function displayError($code,$message,$file,$line)
	{
		echo "PHP Error[$code]: $message\n";
		echo "    in file $file at line $line\n";
		$trace=debug_backtrace();
		//跳过前四个stacks，因为他们并没有错误地址信息
		if(count($trace)>4)
			$trace=array_slice($trace,4);
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
	}
		
	/**
	 * 显示未捕获的PHP异常
	 * 在命令行模式下当没有激活的error handler的时候会执行该方法。
	 * @param Exception $exception 未捕获的异常
	 */
	public function displayException($exception)
	{
		echo $exception;
	}
}