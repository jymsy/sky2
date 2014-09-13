<?php
namespace Sky\base;

use Sky\Sky;
use Sky\web\WebApplication;
/**
 * ErrorHandler是用来处理未捕获的PHP错误和异常。
 * 
 * 它根据应用程序的运行模式， 显示相应的错误。
 * ErrorHandler使用两种视图：
 * <ul>
 * <li>开发视图，名为exception.php;</li>
 * <li>生产视图，名为error<StatusCode>.php;</li>
 * </ul>
 * <StatusCode>表示PHP错误代码（例如error500.php）。
 * 
 * 开发视图是当应用程序为调试模式时显示的视图 （即SKY_DEBUG定义为true）。 
 * 这种视图显示了详细的错误信息和源代码。 
 * 生产视图是当应用程序为产品模式时显示给最终用户的视图。
 * 出于安全原因，它们只显示了错误信息， 没有其它机密信息。 
 * 
 * ErrorHandler按以下顺序查找视图模板：
 * 1.views/system
 * 2.framework/views
 * 如果在目录下没有找到视图文件，它将在下一个目录查找。
 * 
 * maxSourceLines属性可以指定在开发视图中 显示的最大源代码行数。
 * 
 * @property array $error The error details. Null if there is no error.
 * @author Jiangyumeng
 */
class ErrorHandler extends \Sky\base\Component{
	private $_error;
	/**
	 * @var string route 被用来显示外部错误信息的控制其动作的路由(例如‘site/error')。 
	 * 在action中，它通过\Sky\Sky::$app->errorHandler->error得到相关的错误信息。
	 * 这个属性默认为null，意味着ErrorHandler将处理错误显示。
	 * 
	 */
	public $errorAction;
	/**
	 * @var string 应用程序管理员信息。它会显示在最终用户的错误面页，默认为‘@网络业务组’。
	 */
	public $adminInfo='@网络业务组';
	/**
	 * @var integer 显示最大源代码行数，默认20。
	 */
	public $maxSourceLines=20;
	
	/**
	 * @var integer 显示最大跟踪源代码行数，默认10。
	 */
	public $maxTraceSourceLines = 10;
	
	/**
	 * 返回当前正在处理的错误的详细信息。 
	 * 该错误返回的数组包含如下信息：
	 * 	<ul>
	 * <li>code -  HTTP状态码（例如403‘500）</li>
	 * <li>type - 错误类型(例如‘HttpException','PHP Error')</li>
	 * <li>message - 错误信息</li>
	 * <li>file - 发生错误的PHP脚本文件名</li>
	 * <li>line - 发生错误的代码行号</li>
	 * <li>trace - 错误的调用堆栈</li>
	 * </ul>
	 * @return array the error details. Null if there is no error.
	 */
	public function getError(){
		return $this->_error;
	}
	
	public function beforeHandle()
	{
		$gzHandler=false;
		foreach(ob_list_handlers() as $h)
		{
			if(strpos($h,'gzhandler')!==false)
				$gzHandler=true;
		}
		// the following manual level counting is to deal with zlib.output_compression set to On
		// for an output buffer created by zlib.output_compression set to On ob_end_clean will fail
		for($level=ob_get_level();$level>0;--$level)
		{
			if(!@ob_end_clean())
				ob_clean();
		}
		// reset headers in case there was an ob_start("ob_gzhandler") before
		if($gzHandler && !headers_sent() && ob_list_handlers()===array())
		{
			if(function_exists('header_remove')) // php >= 5.3
			{
				header_remove('Vary');
				header_remove('Content-Encoding');
			}
			else
			{
				header('Vary:');
				header('Content-Encoding:');
			}
		}
	}
	
	/**
	 * 处理异常
	 * @param \Exception $exception 捕获到的异常
	 */
	public function handleException($exception){
// 		$this->beforeHandle();
		if(Sky::$app instanceof WebApplication){
			
			if(($trace=$this->getExactTrace($exception))===null){
				$fileName=$exception->getFile();
				$errorLine=$exception->getLine();
			}else{
				$fileName=$trace['file'];
				$errorLine=$trace['line'];
			}
			$trace = $exception->getTrace();
			
			$this->_error=$data=array(
					'code'=>($exception instanceof \Sky\base\HttpException)?$exception->statusCode:500,
					'type'=>get_class($exception),
					'errorCode'=>$exception->getCode(),
					'message'=>$exception->getMessage(),
					'file'=>$fileName,
					'line'=>$errorLine,
					'trace'=>$exception->getTraceAsString(),
					'traces'=>$trace,
			);
			
			if(!headers_sent())
				header("HTTP/1.0 {$data['code']} ".$this->getHttpHeader($data['code'], get_class($exception)));
			$message=$data['type'].'  '.$data['message'].'('.$data['file'].':'.$data['line'].')';
			
// 			if(isset($_REQUEST['ws'])){
// 				if(SKY_DEBUG)
// 					echo $message."\n".$data['trace'];
// 				else
// 					echo $data['message'];
// 			}else{
				if(SKY_DEBUG)
					$this->render('exception', $data);
				else
// 					echo $data['message'];
					$this->render('error', $data);
// 			}
		}else{
				Sky::$app->displayException($exception);
		}
	}
	

	/**
	 * 处理PHP error
	 * @param int $code 错误代码
	 * @param string $message 错误信息
	 * @param string $file 错误文件
	 * @param int $line 错误行
	 */
	public function handleError($code,$message,$file,$line){
// 		$this->beforeHandle();
		$trace=debug_backtrace();
		// skip the first 3 stacks as they do not tell the error position
		if(count($trace)>3)
			$trace=array_slice($trace,3);
		$traceString='';
		
		foreach($trace as $i=>$t){
			if(!isset($t['file']))
				$trace[$i]['file']='unknown';
		
			if(!isset($t['line']))
				$trace[$i]['line']=0;
		
			if(!isset($t['function']))
				$trace[$i]['function']='unknown';
		
			$traceString.="#$i {$trace[$i]['file']}({$trace[$i]['line']}): ";
			if(isset($t['object']) && is_object($t['object']))
				$traceString.=get_class($t['object']).'->';
			$traceString.="{$trace[$i]['function']}()\n";
		
			unset($trace[$i]['object']);
		}
		
		if(Sky::$app instanceof WebApplication){
			switch($code)
			{
				case E_WARNING:
					$type = 'PHP warning';
					break;
				case E_NOTICE:
					$type = 'PHP notice';
					break;
				case E_USER_ERROR:
					$type = 'User error';
					break;
				case E_USER_WARNING:
					$type = 'User warning';
					break;
				case E_USER_NOTICE:
					$type = 'User notice';
					break;
				case E_RECOVERABLE_ERROR:
					$type = 'Recoverable error';
					break;
				default:
					$type = 'PHP error';
			}
			$this->_error=$data=array(
					'code'=>500,
					'type'=>$type,
					'message'=>$message,
					'file'=>$file,
					'line'=>$line,
					'trace'=>$traceString,
					'traces'=>$trace,
			);
			
			if(!headers_sent())
				header('HTTP/1.0 500 Internal Server Error');
			$message=$data['type'].' ['.$data['code'].'] '."$message ($file:$line)";
// 			if(isset($_REQUEST['ws'])){
// 				if(SKY_DEBUG){
// 					echo $message."\n".$data['trace'];
// 				}else
// 					echo $data['message'];
// 			}else{
				if(SKY_DEBUG)
					$this->render('exception', $data);
				else
// 					echo $data['message'];
					$this->render('error', $data);
// 			}
		}else 
			Sky::$app->displayError($code, $message, $file, $line);

	}
	
	/**
	 * 返回是否从应用程序代码中调用堆栈。
	 * @param array $trace 跟踪数据
	 * @return boolean 
	 */
	protected function isCoreCode($trace){
		if(isset($trace['file'])){
			$systemPath=realpath(dirname(__FILE__).'/..');
			return $trace['file']==='unknown' || strpos(realpath($trace['file']),$systemPath.DIRECTORY_SEPARATOR)===0;
		}
		return false;
	}
	
	/**
	 * 判断一个error是否是致命错误
	 *
	 * @param array $error 从error_get_last()得到的error数组
	 * @return boolean 如果该错误是致命错误
	 */
	public static function isFatalError($error){
		return isset($error['type']) && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING));
	}
	
	/**
	 * 转换参数数组为字符串。
	 *
	 * @param array $args 要转换的参数数组
	 * @return string string 转换后的数组
	 */
	protected function argumentsToString($args){
		$count=0;
	
		$isAssoc=$args!==array_values($args);
	
		foreach($args as $key => $value){
			$count++;
			if($count>=5){
				if($count>5)
					unset($args[$key]);
				else
					$args[$key]='...';
				continue;
			}
	
			if(is_object($value))
				$args[$key] = get_class($value);
			elseif(is_bool($value))
			$args[$key] = $value ? 'true' : 'false';
			elseif(is_string($value)){
				if(strlen($value)>64)
					$args[$key] = '"'.substr($value,0,64).'..."';
				else
					$args[$key] = '"'.$value.'"';
			}
			elseif(is_array($value))
			$args[$key] = 'array('.$this->argumentsToString($value).')';
			elseif($value===null)
			$args[$key] = 'null';
			elseif(is_resource($value))
			$args[$key] = 'resource';
	
			if(is_string($key)){
				$args[$key] = '"'.$key.'" => '.$args[$key];
			}elseif($isAssoc){
				$args[$key] = $key.' => '.$args[$key];
			}
		}
		$out = implode(", ", $args);
	
		return $out;
	}
	
	/**
	 * 返回问题发生的详细跟踪信息。
	 * @param Exception $exception 未捕获的异常
	 * @return array 问题发生的准确trace
	 */
	protected function getExactTrace($exception)
	{
		$traces=$exception->getTrace();
	
		foreach($traces as $trace)
		{
			// property access exception
			if(isset($trace['function']) && ($trace['function']==='__get' || $trace['function']==='__set'))
				return $trace;
		}
		return null;
	}
	
	/**
	 * 渲染错误view
	 * @param string $view 错误视图文件名 (没有扩展名).
	 * @param array $data 要传递给视图的数据。
	 */
	protected function render($view,$data){
		if($view==='error' && $this->errorAction!==null)
			\Sky\Sky::$app->runController($this->errorAction);
		elseif($view==='error')
			echo $data['message'];
		else{
			// additional information to be passed to view
			$data['version']=$this->getVersionInfo();
			$data['time']=time();
			$data['admin']=$this->adminInfo;
			include($this->getViewFile($view,$data['code']));
		}
	}
	
	/**
	 * 显示错误行周围的代码
	 * @param string $file 源文件路径
	 * @param integer $errorLine 错误行号
	 * @param integer $maxLines 最大显示的行数
	 * @return string 显示的结果
	 */
	protected function renderSourceCode($file,$errorLine,$maxLines){
		$errorLine--;	// adjust line number to 0-based from 1-based
		if($errorLine<0 || ($lines=@file($file))===false || ($lineCount=count($lines))<=$errorLine)
			return '';
	
		$halfLines=(int)($maxLines/2);
		$beginLine=$errorLine-$halfLines>0 ? $errorLine-$halfLines:0;
		$endLine=$errorLine+$halfLines<$lineCount?$errorLine+$halfLines:$lineCount-1;
		$lineNumberWidth=strlen($endLine+1);
	
		$output='';
		for($i=$beginLine;$i<=$endLine;++$i){
			$isErrorLine = $i===$errorLine;
			$code=sprintf("<span class=\"ln".($isErrorLine?' error-ln':'')."\">%0{$lineNumberWidth}d</span> %s",$i+1,\Sky\help\Html::encode(str_replace("\t",'    ',$lines[$i])));
			if(!$isErrorLine)
				$output.=$code;
			else
				$output.='<span class="error">'.$code.'</span>';
		}
		return '<div class="code"><pre>'.$output.'</pre></div>';
	}
	
	/**
	 * 决定使用哪个视图文件
	 * @param string $view 视图名( 'exception' 或 'error')
	 * @param integer $code HTTP状态码
	 * @return string 视图文件路径
	 */
	protected function getViewFile($view,$code){
		$viewPaths=array(
				\Sky\Sky::$app->getSystemViewPath(),
				SKY_PATH.DIRECTORY_SEPARATOR.'views',
		);
	
		foreach($viewPaths as $viewPath){
			if($viewPath!==null){
				$viewFile=$this->getViewFileInternal($viewPath,$view,$code);
				if(is_file($viewFile))
					return $viewFile;
			}
		}
	}
	
	/**
	 * 在指定的目录下寻找视图文件
	 * @param string $viewPath 包含视图文件的目录
	 * @param string $view 视图名( 'exception' 或 'error')
	 * @param integer $code HTTP状态码
	 * @return string 视图文件路径
	 */
	protected function getViewFileInternal($viewPath,$view,$code){
		if($view==='error'){
			$viewFile=$viewPath.DIRECTORY_SEPARATOR."error{$code}.php";
			if(!is_file($viewFile))
				$viewFile=$viewPath.DIRECTORY_SEPARATOR.'error.php';
		}
		else
			$viewFile=$viewPath.DIRECTORY_SEPARATOR.'exception.php';
		return $viewFile;
	}
	
	/**
	 * 返回服务器软件版本信息
	 * 如果应用部署在生产模式，将返回空字符
	 * @return string
	 */
	protected function getVersionInfo(){
		if(SKY_DEBUG){
			$version='<a href="http://www.skyworth.com/">SKY Framework</a>/'.\Sky\Sky::getVersion();
			if(isset($_SERVER['SERVER_SOFTWARE']))
				$version=$_SERVER['SERVER_SOFTWARE'].' '.$version;
		}else
			$version='';
		return $version;
	}
	
	/**
	 * 返回已知的http状态码信息
	 * @param integer $httpCode http状态码
	 * @param string $replacement 如果未找到状态码的话返回的信息
	 * @return string
	 */
	protected function getHttpHeader($httpCode, $replacement='')
	{
		$httpCodes = array(
				100 => 'Continue',
				101 => 'Switching Protocols',
				102 => 'Processing',
				118 => 'Connection timed out',
				200 => 'OK',
				201 => 'Created',
				202 => 'Accepted',
				203 => 'Non-Authoritative',
				204 => 'No Content',
				205 => 'Reset Content',
				206 => 'Partial Content',
				207 => 'Multi-Status',
				210 => 'Content Different',
				300 => 'Multiple Choices',
				301 => 'Moved Permanently',
				302 => 'Found',
				303 => 'See Other',
				304 => 'Not Modified',
				305 => 'Use Proxy',
				307 => 'Temporary Redirect',
				310 => 'Too many Redirect',
				400 => 'Bad Request',
				401 => 'Unauthorized',
				402 => 'Payment Required',
				403 => 'Forbidden',
				404 => 'Not Found',
				405 => 'Method Not Allowed',
				406 => 'Not Acceptable',
				407 => 'Proxy Authentication Required',
				408 => 'Request Time-out',
				409 => 'Conflict',
				410 => 'Gone',
				411 => 'Length Required',
				412 => 'Precondition Failed',
				413 => 'Request Entity Too Large',
				414 => 'Request-URI Too Long',
				415 => 'Unsupported Media Type',
				416 => 'Requested range unsatisfiable',
				417 => 'Expectation failed',
				418 => 'I’m a teapot',
				422 => 'Unprocessable entity',
				423 => 'Locked',
				424 => 'Method failure',
				425 => 'Unordered Collection',
				426 => 'Upgrade Required',
				449 => 'Retry With',
				450 => 'Blocked by Windows Parental Controls',
				500 => 'Internal Server Error',
				501 => 'Not Implemented',
				502 => 'Bad Gateway ou Proxy Error',
				503 => 'Service Unavailable',
				504 => 'Gateway Time-out',
				505 => 'HTTP Version not supported',
				507 => 'Insufficient storage',
				509 => 'Bandwidth Limit Exceeded',
		);
		if(isset($httpCodes[$httpCode]))
			return $httpCodes[$httpCode];
		else
			return $replacement;
	}
}