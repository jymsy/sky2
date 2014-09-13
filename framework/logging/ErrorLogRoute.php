<?php
namespace Sky\logging;

/**
 * 错误日志收集程序
 * @author Jiangyumeng
 *
 */
class ErrorLogRoute extends SocketLogRoute{
	public $logFilePath='/data/framework/error/error';
	
	public function init(){
		$this->levels =Logger::LEVEL_ERROR;
	}
	
	protected function process($log,$socket){
		$msgLen=strlen('<beginMsg>'.$log[0]);
		$filenameLen=strlen($this->logFilePath);
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
				'logfilename'=>array('a*',$this->logFilePath),
				'shLogCount'=>array('n','1'),
				'msgLen'=>array('n',$msgLen),
				'msg'=>array('a*','<beginMsg>'.$log[0]),
		);
		
		$str=$this->packByArr($logArr);
		
		$this->sendLog($socket,$str);
	}
	
}