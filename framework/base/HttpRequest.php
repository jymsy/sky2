<?php
namespace Sky\base;

use Sky\Sky;
/**
 * HttpRequest 是被{@link Sky\web\WebApplication}默认加载的组件，
 * 可以通过{@link Sky\web\WebApplication::getRequest()}访问
 * 
 * @property string $userHostAddress 用户IP地址
 * @author Jiangyumeng
 *
 */
class HttpRequest extends Component{
	
	private $_requestUri;
	private $_pathInfo;
	private $_scriptUrl;
	private $_baseUrl;
	private $_hostInfo;
	private $_securePort;
	private $_port;
	
	/**
	 * 返回请求的类型，例如 GET, POST, HEAD, PUT, DELETE.
	 * @return string 返回类型GET, POST, HEAD, PUT, DELETE.
	 */
	public function getRequestType()
	{
		if(isset($_POST['_method']))
			return strtoupper($_POST['_method']);
	
		return strtoupper(isset($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:'GET');
	}
	
	/**
	 * 返回当前请求的路径信息。
	 * 指的是在入口脚本之后和查询标记之前的部分。
	 * 启始和结束的'/'已经被过滤。
	 * @return string 请求的路径信息。
	 * @throws \Exception 如果无法找到路径信息。
	 */
	public function getPathInfo(){
		if($this->_pathInfo===null){
			$pathInfo=$this->getRequestUri();
			if(($pos=strpos($pathInfo,'?'))!==false)
				$pathInfo=substr($pathInfo,0,$pos);
	
// 			$pathInfo=$this->decodePathInfo($pathInfo);
			
			$pathInfo = urldecode($pathInfo);
			
			// try to encode in UTF8 if not so
			// http://w3.org/International/questions/qa-forms-utf-8.html
			if (!preg_match('%^(?:
				[\x09\x0A\x0D\x20-\x7E]              # ASCII
				| [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
				| \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
				| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
				| \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
				| \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
				| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
				| \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
				)*$%xs', $pathInfo)) {
				$pathInfo = utf8_encode($pathInfo);
			}
			
			$scriptUrl=$this->getScriptUrl();
// 			echo $scriptUrl; ///skyframework/demos/skyapp/index.php
			$baseUrl=$this->getBaseUrl();
			
			if(strpos($pathInfo,$scriptUrl)===0)
				$pathInfo=substr($pathInfo,strlen($scriptUrl));
			elseif($baseUrl==='' || strpos($pathInfo,$baseUrl)===0)
				$pathInfo=substr($pathInfo,strlen($baseUrl));
			elseif(strpos($_SERVER['PHP_SELF'],$scriptUrl)===0)
				$pathInfo=substr($_SERVER['PHP_SELF'],strlen($scriptUrl));
			else
				throw new \Exception('HttpRequest is unable to determine the path info of the request.');
	
			if ($pathInfo[0] === '/') {
				$pathInfo = substr($pathInfo, 1);
			}
			
			if($pathInfo[strlen($pathInfo)-1]==='/')
				$pathInfo=substr($pathInfo,0,strlen($pathInfo)-1);
			$this->_pathInfo=$pathInfo;
// 			$this->_pathInfo=trim($pathInfo,'/');
		}
		return $this->_pathInfo;
	}
	
	/**
	 * 返回当前请求的主机信息。
	 * 返回的URL没有用'/'结尾。
	 * @return string返回当前请求的主机信息。
	 */
	public function getHostInfo()
	{
		if ($this->_hostInfo === null) {
			$secure = $this->getIsSecureConnection();
			$http = $secure ? 'https' : 'http';
			if (isset($_SERVER['HTTP_HOST'])) {
				$this->_hostInfo = $http . '://' . $_SERVER['HTTP_HOST'];
			} else {
				$this->_hostInfo = $http . '://' . $_SERVER['SERVER_NAME'];
				$port = $secure ? $this->getSecurePort() : $this->getPort();
				if (($port !== 80 && !$secure) || ($port !== 443 && $secure)) {
					$this->_hostInfo .= ':' . $port;
				}
			}
		}
	
		return $this->_hostInfo;
	}
	
	/**
	 * 返回非安全请求的端口
	 * 默认为80或当前指定的端口。
	 * request is insecure.
	 * @return integer 端口号。
	 */
	public function getPort()
	{
		if ($this->_port === null) {
			$this->_port = !$this->getIsSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 80;
		}
		return $this->_port;
	}
	
	/**
	 * @return boolean 请求是否通过安全的信道(https)
	 */
	public function getIsSecureConnection()
	{
		return isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'],'on') === 0 || $_SERVER['HTTPS'] == 1)
		|| isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'],'https') === 0;
	}
	
	/**
	 * 返回安全请求的端口。
	 * 默认为443,或当前服务器设置的端口。
	 * @return integer 端口号
	 * @see setSecurePort
	 */
	public function getSecurePort()
	{
		if ($this->_securePort === null) {
			$this->_securePort = $this->getIsSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 443;
		}
		return $this->_securePort;
	}
	
	/**
	 * 设置安全请求的端口。
	 * @param integer $value 端口号。
	 */
	public function setSecurePort($value)
	{
		if ($value != $this->_securePort) {
			$this->_securePort = (int)$value;
			$this->_hostInfo = null;
		}
	}
	
	/**
	 * 返回当前请求的url
	 * 和{@link getRequestUri}相同.
	 * @return string URL中主机信息之后的部分。.
	 */
	public function getUrl()
	{
		return $this->getRequestUri();
	}
	
	/**
	 * 返回是否是 AJAX (XMLHttpRequest) 请求.
	 * @return boolean 是否是 AJAX (XMLHttpRequest) 请求.
	 */
	public function getIsAjaxRequest()
	{
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';
	}
	
	/**
	 * 返回当前请求URL的URI部分。
	 * 指的是在主机名之后的部分。
	 * 包括了查询字符串部分，如果有的话。
	 * 该方法的实现参考了Zend Framework的Zend_Controller_Request_Http
	 * @return string 当前请求URL的URI部分。
	 * @throws \Exception 如果无法解析出URI。
	 */
	public function getRequestUri(){
		if($this->_requestUri===null){
			if(isset($_SERVER['HTTP_X_REWRITE_URL'])) // IIS
				$this->_requestUri=$_SERVER['HTTP_X_REWRITE_URL'];
			elseif(isset($_SERVER['REQUEST_URI'])){
				$this->_requestUri=$_SERVER['REQUEST_URI'];
				
// 				if(!empty($_SERVER['HTTP_HOST'])){
// 					if(strpos($this->_requestUri,$_SERVER['HTTP_HOST'])!==false)
// 						$this->_requestUri=preg_replace('/^\w+:\/\/[^\/]+/','',$this->_requestUri);
// 				}else
// 					$this->_requestUri=preg_replace('/^(http|https):\/\/[^\/]+/i','',$this->_requestUri);
				
				if ($this->_requestUri !== '' && $this->_requestUri[0] !== '/') {
					$this->_requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $this->_requestUri);
				}
			}elseif(isset($_SERVER['ORIG_PATH_INFO'])){ // IIS 5.0 CGI
				$this->_requestUri=$_SERVER['ORIG_PATH_INFO'];
				if(!empty($_SERVER['QUERY_STRING']))
					$this->_requestUri.='?'.$_SERVER['QUERY_STRING'];
			}else
				throw new \Exception('HttpRequest is unable to determine the request URI.');
		}
	
		return $this->_requestUri;
	}
	
	/**
	 * 将浏览器重定向到指定的URL
	 * @param string $url 要重定向到的URL。当URL不是绝对路径（不以'/'开头）
	 * 它就是相对于当前请求的URL。
	 * @param boolean $terminate 是否终止当前的应用。
	 * @param integer $statusCode HTTP状态码。默认是302. 详细参见{@link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html}
	 */
	public function redirect($url,$terminate=true,$statusCode=302){
		if(strpos($url,'/')===0 && strpos($url,'//')!==0)
			$url=$this->getHostInfo().$url;
		header('Location: '.$url, true, $statusCode);
		if($terminate)
			Sky::$app->end();
	}
	
	/**
	 * 解码路径信息
	 * @param string $pathInfo 编码后的路径信息。
	 * @return string 解码后的路径信息。
	 */
	protected function decodePathInfo($pathInfo){
		$pathInfo = urldecode($pathInfo);
	
		// is it UTF-8?
		// http://w3.org/International/questions/qa-forms-utf-8.html
		if(preg_match('%^(?:
		   [\x09\x0A\x0D\x20-\x7E]            # ASCII
		 | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
		 | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
		 | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
		 | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
		 | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
		 | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
		 | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
		)*$%xs', $pathInfo))
		{
			return $pathInfo;
		}else{
			return utf8_encode($pathInfo);
		}
	}	
	
	/**
	 * 返回入口脚本的相对URL。
	 * @return string 入口脚本的相对URL。
	 */
	public function getScriptUrl(){
		if($this->_scriptUrl===null){
			
			$scriptName=basename($_SERVER['SCRIPT_FILENAME']);
			if(basename($_SERVER['SCRIPT_NAME'])===$scriptName)
				$this->_scriptUrl=$_SERVER['SCRIPT_NAME'];
			elseif(basename($_SERVER['PHP_SELF'])===$scriptName)
				$this->_scriptUrl=$_SERVER['PHP_SELF'];
			elseif(isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME'])===$scriptName)
				$this->_scriptUrl=$_SERVER['ORIG_SCRIPT_NAME'];
			elseif(($pos=strpos($_SERVER['PHP_SELF'],'/'.$scriptName))!==false)
				$this->_scriptUrl=substr($_SERVER['SCRIPT_NAME'],0,$pos).'/'.$scriptName;
			elseif(isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'],$_SERVER['DOCUMENT_ROOT'])===0)
				$this->_scriptUrl=str_replace('\\','/',str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['SCRIPT_FILENAME']));
			else
				throw new \Exception('HttpRequest is unable to determine the entry script URL.');
		}
		return $this->_scriptUrl;
	}
	
	/**
	 * 返回应用的相对URL
	 * 跟{@link getScriptUrl scriptUrl}相似，除了没有脚本文件名，
	 * 结尾的'/'也被过滤掉。
	 * @return string 应用的相对URL
	 */
	public function getBaseUrl(){
		if($this->_baseUrl===null)
			$this->_baseUrl=rtrim(dirname($this->getScriptUrl()),'\\/');
		return $this->_baseUrl;
	}
	
	/**
	 * 返回用户IP地址
	 * @return string 用户IP地址
	 */
	public function getUserHostAddress(){
		$unknown = 'unknown';
		$ip = '';
		if ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif ( isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		/*
		 处理多层代理的情况
		或者使用正则方式：$ip = preg_match("/[\d\.]{7,15}/", $ip, $matches) ? $matches[0] : $unknown;
		*/
		if (($pos=strpos($ip, ',')) !== false)
			$ip = substr($ip, 0, $pos);
		return $ip;
		//return isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'127.0.0.1';
	}
}