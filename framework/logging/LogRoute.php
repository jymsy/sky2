<?php
namespace Sky\logging;

/**
 * LogRoute是所有日志路由类的基类。
 * 
 * 一个日志路由对象从一个日志记录器取回日志信息并将它发送到任何地方。
 * 例如文件，电子邮件。取回的信息在发送给目标前可以被过滤。 
 * 过滤器包括日志等级过滤器和日志类别过滤器。 
 * 
 * @author Jiangyumeng
 */
abstract class LogRoute extends \Sky\base\Component{
	/**
	 * @var boolean 是否启用这个日志路由。默认为true。
	 */
	public $enabled=true;
	/**
	 * @var string 用逗号或空格分隔的等级列表。默认是空，意味着获取所有等级。
	 */
	public $levels='';
	/**
	 * @var mixed 被逗号或空格分隔的类别列表。默认为空，意味着获取所有类别。
	 */
	public $categories=array();
	/**
	 * @var array 到目前为止这个日志路由搜集的日志。
	 */
	public $logs=array();
	
	/**
	 * 初始化路由。
	*/
	public function init()
	{
	}
	
	/**
	 * 格式化一条日志信息。
	 * @param string $message 消息内容
	 * @param integer $level 消息等级
	 * @param string $category 消息分类
	 * @param integer $time 时间戳
	 * @return string 格式化后的消息
	 */
	protected function formatLogMessage($message,$level,$category,$time){
		return @date('Y/m/d H:i:s',$time)." [$level] [$category] $message\n";
	}
	
	/**
	 * 从日志记录器取回已过滤的日志信息以便进一步处理。
	 * @param \Sky\logging\Logger $logger logger实例
	 * @param boolean $processLogs 搜集到日志记录器的日志后是否进行处理
	 */
	public function collectLogs($logger, $processLogs=false){
		$logs=$logger->getLogs($this->levels,$this->categories);
		$this->logs=empty($this->logs) ? $logs : array_merge($this->logs,$logs);

		if($processLogs && !empty($this->logs)){
			if($this->logs!==array())
				$this->processLogs($this->logs);
			$this->logs=array();
		}
	}
	
	/**
	 * 处理日志信息并将它们发送到指定的目标。 派生类必须实现这个方法。
	 * @param array $logs 信息列表 
	 */
	abstract protected function processLogs($logs);
}