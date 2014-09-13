<?php
namespace Sky\utils\rabbitmq;

use Sky\logging\FileLogRoute;
use Sky\Sky;
use Sky\logging\Logger;
class ProfLogRoute extends FileLogRoute{
	protected $stack=array();
	protected $queries=0;
	public function init()
	{
		Sky::getLogger()->autoDump=true;
		Sky::getLogger()->autoFlush=1;
		$this->levels='info,profile';
		parent::init();	
	}
	
	/**
	 * 将日志消息写到文件。
	 * @param array $logs 日志消息列表
	 * @throws \Exception 如果不能打开文件
	 */
	public function processLogs($logs)
	{
		$text='';
		foreach($logs as $log)
		{
			if($log[1]==Logger::LEVEL_PROFILE)
			{
				$message=$log[0];
				if(!strncasecmp($message,'begin:',6)){
					$log[0]=substr($message,6);
					$this->stack[]=$log;
				}else if(!strncasecmp($message,'end:',4))
				{
					$token=substr($message,4);
					if(($last=array_pop($this->stack))!==null && $last[0]===$token)
					{
						$this->queries++;
					}
				}
				return ;
			}else{
				$log[0].=" query:$this->queries";
			}
		}
			$text.=$this->formatLogMessage($log[0],$log[1],$log[2],$log[3]);
		$this->queries=0;
		$this->stack=array();
		if (($fp = @fopen($this->logFile, 'a')) === false) {
			throw new \Exception("Unable to append to log file: {$this->logFile}");
		}
	
		@flock($fp, LOCK_EX);
		if (@filesize($this->logFile) > $this->maxFileSize * 1024) {
			$this->rotateFiles();
			@flock($fp, LOCK_UN);
			@fclose($fp);
			@file_put_contents($this->logFile, $text, FILE_APPEND | LOCK_EX);
		} else {
			@fwrite($fp, $text);
			@flock($fp, LOCK_UN);
			@fclose($fp);
		}
		if ($this->fileMode !== null) {
			@chmod($this->logFile, $this->fileMode);
		}
	}
}