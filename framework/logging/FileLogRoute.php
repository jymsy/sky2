<?php
namespace Sky\logging;

use Sky\help\FileHelper;
/**
 * FileLogRoute 将日志消息记录到文件中。
 * 
 * @author Jiangyumeng
 *
 */
class FileLogRoute extends LogRoute{

	/**
	 * @var string 日志文件路径，如果没有设置的话
	 * 会使用'/tmp/skyframework.log'，如果包含日
	 * 志文件的路径不存在的话将会自动创建。
	 */
	public $logFile;
	/**
	 * @var integer 日志文件最大大小, 单位KB，默认 10240,  10MB.
	 */
	public $maxFileSize = 10240; // in KB
	/**
	 * @var integer 轮转日志的个数. 默认为5.
	 */
	 public $maxLogFiles = 5;
	 /**
	  * @var integer 创建的日志文件的权限。
	  */
	 public $fileMode;
	/**
	 * @var integer 新创建目录的权限。
	 * 这个值将会被PHP的chmod()方法使用。
	 * 默认为0775，意味着该目录对所有者和所在组有读写权限，
	 * 对其他用户只有读权限。
	 */
	public $dirMode = 0775;
	
	/* 初始化
	 * @see \Sky\logging\LogRoute::init()
	 */
	public function init()
	{
		if ($this->logFile === null) {
			$this->logFile =  '/tmp/skyframework.log';
		}
		
		$logPath = dirname($this->logFile);
		if (!is_dir($logPath)) {
			FileHelper::createDirectory($logPath, $this->dirMode, true);
		}
		
		if ($this->maxLogFiles < 1) {
			$this->maxLogFiles = 1;
		}
		if ($this->maxFileSize < 1) {
			$this->maxFileSize = 1;
		}
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
			$text.=$this->formatLogMessage($log[0],$log[1],$log[2],$log[3]);
		
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
	
	/**
	 * 轮转日志文件。
	 */
	protected function rotateFiles()
	{
		$file = $this->logFile;
		for ($i = $this->maxLogFiles; $i > 0; --$i) {
			$rotateFile = $file . '.' . $i;
			if (is_file($rotateFile)) {
				// suppress errors because it's possible multiple processes enter into this section
				if ($i === $this->maxLogFiles) {
					@unlink($rotateFile);
				} else {
					@rename($rotateFile, $file . '.' . ($i + 1));
				}
			}
		}
		if (is_file($file)) {
			@rename($file, $file . '.1'); // suppress errors because it's possible multiple processes enter into this section
		}
	}
}