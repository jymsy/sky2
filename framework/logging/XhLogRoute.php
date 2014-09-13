<?php
namespace Sky\logging;

/**
 * Xhprof 日志路由。
 * @author Jiangyumeng
 *
 */
class XhLogRoute extends SocketLogRoute{
	public $logFilePath='/data/framework/xhprof/';
	
	public function init(){
		$this->levels ='xhprof';
	}
	
	public function process($log,$socket){
		$msgLen=strlen($log[0]);
		$filenameLen=strlen($this->logFilePath.$log[2]);
		$iLen=40+$msgLen+$filenameLen;
		
		$uiSeq=substr($log[3], strpos($log[3], '.')+1);
		
		$logArr=array(
				'iLen'=>array('N',$iLen),
				'shVer'=>array('n','2'),
				'shCmd'=>array('n','2'),
				'uiSeq'=>array('N',$uiSeq),
				'backstageIDLen'=>array('n','20'),
				'backstageID'=>array('A20',' '),
				'shLogNameLen'=>array('n',$filenameLen),
				'logfilename'=>array('a*',$this->logFilePath.$log[2]),
				'shLogCount'=>array('n','1'),
				'msgLen'=>array('n',$msgLen),
				'msg'=>array('a*',$log[0]),
		);
		
		$str=$this->packByArr($logArr);
		
		$this->sendLog($socket,$str);
	}
}