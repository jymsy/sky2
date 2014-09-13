<?php
namespace Sky\base;

use Sky\Sky;
use Sky\web\Response;
/**
 * UrlManager 用来管理Sky Web应用的URL
 * 
 * 通过UrlManager管理的URL通过设置{@link setUrlFormat urlFormat}属性，可以是以下两种格式：
 * <ul>
 * <li>'path' format: /path/to/EntryScript.php/name1/value1/name2/value2...</li>
 * <li>'get' format:  /path/to/EntryScript.php?name1=value1&name2=value2...</li>
 * </ul>
 * 
 * UrlManager 是默认的应用组件，可以通过{@link WebApplication::getUrlManager()}访问
 * 
 * 
 * @property boolean $useParamName 对webservice的请求url是否需要参数名。
 * @author Jiangyumeng
 *
 */
class UrlManager extends Component{
// 	public $request;

	const GET_FORMAT='get';
	const PATH_FORMAT='path';
	/**
	 * @var string 路由的GET变量名。默认是'_r'.
	 */
	public $routeVar='_r';
	public $sessVar='_s';
	public $responseType='responsetype';
	private $_urlFormat=self::GET_FORMAT;
	private $_baseUrl;
	/**
	 * @var string 在'path'格式下使用的URL后缀。
	 * 例如".html"能够使URL看起来指向一个静态页面。 默认为空。
	 */
	public $urlSuffix='';
	/**
	 * @var boolean 是否将GET参数添加到路径信息。默认为true。
	 * 这个属性只有在{@link urlFormat} 为 'path'的时候有效，并且主要用在创建URL时。
	 * 当为true的时候，GET参数将被添加到路径信息中用'/'分割。
	 * 如果为false，GET参数将使用查询字符串的形式。
	 */
	public $appendParams=true;
	/**
	 * @var boolean 创建URL的时候是否显示入口脚本名。默认为true
	 */
	public $showScriptName=true;
	/**
	 * @var boolean 是否使用参数名还是abc自增
	 */
	private $_useParamName;
	/**
	 * @var boolean 是否需要对请求URL进行兼容性判断
	 */
	public $needCompatibility=false;
	
	function __construct(){	
	}
	
	/* 
	 * 组件初始化
	 * @see \Sky\base\Component::init()
	 */
	public function init(){
		if($this->needCompatibility && !isset($_REQUEST[$this->routeVar])){
			$this->_urlFormat=self::PATH_FORMAT;
			$this->_useParamName=false;
		}
		if (isset($_REQUEST[$this->responseType])) 
		{
			$response=Sky::$app->getResponse();
			$response->format=$_REQUEST[$this->responseType];
			if ($_REQUEST[$this->responseType]===Response::FORMAT_JSONP && isset($_REQUEST['callback'])) 
			{
				$response->jsonpcallback=$_REQUEST['callback'];
			}
		}
	}
	
	/**
	 * 返回URL格式
	 * @return string URL格式。默认是'get'. 
	 */
	public function getUrlFormat(){
		return $this->_urlFormat;
	}
	
	/**
	 * 设置URL格式
	 * @param string $value URL格式。他必须是 'path' 或 'get'.
     * @throws \Exception 如果UrlFormat既不是'path' 也不是 'get'
	 */
    public function setUrlFormat($value){
		if($value===self::PATH_FORMAT || $value===self::GET_FORMAT)
			$this->_urlFormat=$value;
		else
			throw new \Exception('UrlFormat must be either "path" or "get".');
	}
	
	/**
	 * 解析用户请求
	 * @param HttpRequest $request request应用组件
	 * @return string 路由 (controllerID/actionID) 在path模式下还包括参数
	 */
	function parseUrl($request){
		if($this->getUrlFormat()===self::PATH_FORMAT){
			
			$pathInfo=$request->getPathInfo();
			if($this->urlSuffix!=='' && substr($pathInfo,-strlen($this->urlSuffix))===$this->urlSuffix)
				return substr($pathInfo,0,-strlen($this->urlSuffix));
			
			return $pathInfo;
// 			return array($pathInfo, array());
		}elseif(isset($_GET[$this->routeVar])){
			$route=$_GET[$this->routeVar];
// 			unset($_GET[$this->routeVar]);
			return $route;
// 			return array($route,array());
		}elseif(isset($_POST[$this->routeVar])){
			$route=$_POST[$this->routeVar];
// 			unset($_POST[$this->routeVar]);
			return $route;
// 			return array($route,array());
		}else
			return '';
// 			return false;
	}
	
	/**
	 * 创建一个URL。
	 * @param string $route controller和action
	 * @param array $params GET参数列表 (name=>value). name 和 value 都将被URL-encoded.
	 * 如果name是'#'，对应的value将被当作锚添加到URL的末尾。
	 * @param string $ampersand 在URL中分割name-value对的字符。
	 * @return string 创建的URL
	 */
	public function createUrl($route,$params=array(),$ampersand='&'){
		unset($params[$this->routeVar]);
		foreach($params as $i=>$param)
			if($param===null)
				$params[$i]='';
	
		if(isset($params['#'])){
			$anchor='#'.$params['#'];
			unset($params['#']);
		}else
			$anchor='';
		$route=trim($route,'/');

		return $this->createUrlDefault($route,$params,$ampersand).$anchor;
	}
	
	/**
	 * 基于默认设置创建一个URL。
	 * @param string $route controller和action
	 * @param array $params GET参数列表
	 * @param string $ampersand 在URL中分割name-value对的字符。
	 * @return string 创建的URL
	 */
	protected function createUrlDefault($route,$params,$ampersand){
		if($this->getUrlFormat()===self::PATH_FORMAT){
			$url=rtrim($this->getBaseUrl().'/'.$route,'/');
			if($this->appendParams){
				$url=rtrim($url.'/'.$this->createPathInfo($params,'/','/'),'/');
				return $route==='' ? $url : $url.$this->urlSuffix;
			}else{
				if($route!=='')
					$url.=$this->urlSuffix;
				$query=$this->createPathInfo($params,'=',$ampersand);
				return $query==='' ? $url : $url.'?'.$query;
			}
		}else{
			$url=$this->getBaseUrl();
			if(!$this->showScriptName)
				$url.='/';
			if($route!==''){
				$url.='?'.$this->routeVar.'='.$route;
				if(($query=$this->createPathInfo($params,'=',$ampersand))!=='')
					$url.=$ampersand.$query;
			}elseif(($query=$this->createPathInfo($params,'=',$ampersand))!=='')
				$url.='?'.$query;
			return $url;
		}
	}
	
	/**
	 * 基于给定的参数创建路径信息
	 * @param array $params GET参数列表
	 * @param string $equal 参数名和参数值之间的分隔符
	 * @param string $ampersand 分割name-value对的字符。
     * @return array
	 */
	public function createPathInfo($params,$equal,$ampersand){
		$pairs = array();
		foreach($params as $k => $v){	
			$pairs[]=urlencode($k).$equal.urlencode($v);
		}
		return implode($ampersand,$pairs);
	}
	
	/**
	 * 返回应用的base URL。
	 * @return string 应用的base URL。 (在主机名之后查询字符串之前的部分).
	 * If {@link showScriptName} is true, it will include the script name part.
	 * Otherwise, it will not, and the ending slashes are stripped off.
	 */
	public function getBaseUrl(){
		if($this->_baseUrl!==null)
			return $this->_baseUrl;
		else{
			if($this->showScriptName)
				$this->_baseUrl=Sky::$app->getRequest()->getScriptUrl();
			else
				$this->_baseUrl=Sky::$app->getRequest()->getBaseUrl();
			return $this->_baseUrl;
		}
	}
	
	/**
	 * @param boolean $useParamName
	 */
	function setUseParamName($useParamName){
		$this->_useParamName=$useParamName;
	}
	
	/**
	 * @return boolean
	 */
	function getUseParamName(){
		return $this->_useParamName;
	}
	
	/**
	 * 将URL中的参数信息保存到$_GET和$_REQUEST中
	 * @param string $pathInfo 路径信息
	 */
	public function parsePathInfo($pathInfo){
		if($pathInfo==='')
			return;
		$segs=explode('/',$pathInfo.'/');
		$n=count($segs);
		for($i=0;$i<$n-1;$i+=2){
			$key=$segs[$i];
			if($key==='') continue;
			$value=$segs[$i+1];
			$_REQUEST[$key]=$_GET[$key]=$value;
		}
	}
	
// 	public function parsePathInfoAuto($pathInfo){
// 		if($pathInfo==='')
// 			return;
// 		$segs=explode('/',$pathInfo.'/');
// 		$n=count($segs);
// 		for($i=0;$i<$n-1;$i+=1){
// 			$key=chr($i+97);
// 			$value=$segs[$i];
// 			$_REQUEST[$key]=$_GET[$key]=$value;
// 		}
// 	}
}