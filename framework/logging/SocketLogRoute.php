<?php
namespace Sky\logging;

/**
 * 通过socket发送日志。
 * @author Jiangyumeng
 *
 */
class SocketLogRoute extends LogRoute{
	public $serverIP='127.0.0.1';
	public $serverName;
	public $serverPort=40022;
	public $logFilePath='/data/biLog/';
	
	/* *
	 * 你可以重写该方法。
	 * @see \Sky\logging\LogRoute::processLogs()
	 */
	public function processLogs($logs){
		$socket=new \Sky\utils\Socket();
		if(!empty($this->serverName)){
			$this->serverIP=gethostbyname($this->serverName);
		}
		
		if (!preg_match('/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/',$this->serverIP)) {
			return false;
		}
		
		if($socket->connect($this->serverIP,$this->serverPort)){
			foreach ($logs as $log){
				$this->process($log,$socket);
			}
			$socket->disconnect();
		}else
			return false;
	}
	
	protected function process($log,$socket){
		$this->sendLog($socket,$log[0]);
	}
	
	/**
	 * @param \Sky\utils\Socket $socket
	 * @param string $str
	 */
	protected function sendLog($socket,$str){
		$socket->sendRequest($str);
	}
	
	protected function packByArr($logArr){
		$atStr='';
		foreach ($logArr as $k=>$v){
			if(isset($v[1]))
				$atStr.=pack($v[0],$v[1]);
			else
				$atStr.=pack($v[0]);
		}
		return $atStr;
	}
}