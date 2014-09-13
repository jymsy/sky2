<?php
namespace Sky\test;

/**
 * WebTestCase is the base class for Web-based functional test case classes.
 * @author Jiangyumeng
 *
 */
abstract class WebTestCase extends \PHPUnit_Framework_TestCase{
	private $_url;
	private $_content;
	private $_statusCode;
	private $_totalTime;
	
	protected function setUp(){
		parent::setUp();
	}
	
	public function setBrowserUrl($url){
		$this->_url=$url;
	}
	
	/**
	 * 向指定的地址发送http请求
	 * @param string $route 路由信息。
	 * @param int $header 是否需要保存响应的头部信息；0不需要，1需要。
	 */
	public function open($route,$method='get',$parmArray=array(),$header=0){
		$inUrl=$this->_url.$route;
		$curl=\Sky\Sky::$app->curl;
// 		$curl = curl_init();
// 		// 设置你需要抓取的URL
// 		curl_setopt($curl, CURLOPT_URL, $inUrl);
// 		curl_setopt($curl, CURLOPT_HEADER, $header);
// 		curl_setopt($curl, CURLOPT_ENCODING,'');
// 		curl_setopt($curl, CURLOPT_TIMEOUT,10);
// 		// 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
// 		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		
// 		if ($method=='post') {
// 			$parmStr='';
// 			foreach ($parmArray as $parm=>$value){
// 				$parmStr.=$parm.'='.urlencode($value).'&';
// 			}
// 			rtrim($parmStr,'&');
// 			curl_setopt($curl, CURLOPT_POST,1) ;
// 			curl_setopt($curl, CURLOPT_POSTFIELDS,$parmStr) ;
// 		}
		
// 		$this->_content = curl_exec($curl);
// 		$this->_statusCode=curl_getinfo($curl, CURLINFO_HTTP_CODE);
// 		$this->_totalTime=curl_getinfo($curl,CURLINFO_TOTAL_TIME);
// 		curl_close($curl);
		if($method=='get')
			$this->_content=$curl->get($inUrl,'path',array(),$header);
		else {
			$this->_content=$curl->post($inUrl,$parmArray,$header);
		}
		$this->_statusCode=$curl->getRequestInfo(CURLINFO_HTTP_CODE);
		$this->_totalTime=$curl->getRequestInfo(CURLINFO_TOTAL_TIME);
	}
	
	/**
	 * @return string 响应内容
	 */
	public function getContent(){
		return $this->_content;
	}
	
	/**
	 * @return int http响应码
	 */
	public function getStatusCode(){
		return $this->_statusCode;
	}
	
	/**
	 * @return float 请求的执行时间（秒）
	 */
	public function getTotalTime(){
		return $this->_totalTime;
	}
}
