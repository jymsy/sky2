<?php
namespace Sky\logging;

class WebLogRoute extends LogRoute{
	
	public function processLogs($logs){
		
			$this->render('log',$logs);
	}
	
	protected function render($view,$data){
		if(isset($_REQUEST['ws']) || isset($_REQUEST['IsWsdlRequest']))
			return;
		$viewFile=SKY_PATH.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.$view.'.php';
		include($viewFile);
	}
	
}