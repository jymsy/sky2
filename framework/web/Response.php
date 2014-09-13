<?php
namespace Sky\web;

use Sky\base\Component;
use Sky\Sky;
use Sky\help\JSON;
use Sky\base\Event;
/**
 * 返回HTTP响应的web 响应类。
 * 
 * Response 被{@link Sky\web\WebApplication}配置为默认组件。
 * 你可以通过'Sky::$app->response'访问。
 * 
 * 你也可以在应用的配置文件中修改它的配置：
 * ~~~
 * 'components'=>array(
 * 		 'response' => array(
 *     		'format' => Sky\web\Response::FORMAT_JSON,
 *     		'charset' => 'UTF-8',
 *     		// ...
 * 		)
 * 	)
 * ~~~
 * @author Jiangyumeng
 *
 */
class Response extends Component{
	const FORMAT_RAW = 'raw';
	const FORMAT_HTML = 'html';
	const FORMAT_JSON = 'json';
	const FORMAT_JSONP = 'jsonp';
	const FORMAT_XML = 'xml';
	
	/**
	 * @var string 响应的格式。
	 * 你可以通过配置{formatter}来支持自定义的格式
	 * @see formatters
	 */
	public $format = self::FORMAT_HTML;
	/**
	 * @var array 
	 */
	public $formatters;
	/**
	 * @var mixed 原始的响应数据。
	 * 如果不是null的话，将会根据{format}转换为指定的格式到{content}。
	 * @see content
	 */
	public $data;
	/**
	 * @var string 响应的内容。
	 */
	public $content;
	/**
	 * @var string 字符响应的编码。
	 * 如果没设置的话将会使用{Application::chartset}。
	 */
	public $charset;
	/**
	 * @var string 使用的HTTP协议版本。如果没设置的话使用`$_SERVER['SERVER_PROTOCOL']`,
	 * 或1.1，如果上面的不可用。
	 */
	public $version;
	/**
	 * @var array
	 */
	private $_headers;
	/**
	 * @var string
	 */
	public $jsonpcallback;
	/**
	 * @var string
	 */
	const EVENT_AFTER_PREPARE = 'afterPrepare';
	
	public function init()
	{
		if ($this->version === null) {
			if (isset($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] === '1.0') {
				$this->version = '1.0';
			} else {
				$this->version = '1.1';
			}
		}
		if ($this->charset === null) {
			$this->charset = Sky::$app->charset;
		}
	}
	
	/**
	 * 向客户端发送响应。
	 */
	public function send()
	{
		$this->prepare();
		if(SKY_DEBUG)
			$this->setHeader('Content-Type', 'text/html; charset=' . $this->charset);
		$this->raiseEvent(self::EVENT_AFTER_PREPARE, new Event($this));
		$this->sendHeaders();
		$this->sendContent();
		if (!SKY_DEBUG) 
		{
			if(function_exists('fastcgi_finish_request'))
				fastcgi_finish_request();
		}
	}
	
	/**
	 * 准备要发送的响应。
	 * @throws \Exception 如果格式不支持的话
	 */
	protected function prepare()
	{
		if ($this->data === null) {
			return;
		}
		
		if (isset($this->formatters[$this->format])) 
		{
			
		}else{
			switch ($this->format) {
				case self::FORMAT_HTML:
					$this->setHeader('Content-Type', 'text/html; charset=' . $this->charset);
					$this->content = $this->data;
					break;
				case self::FORMAT_RAW:
					$this->content = $this->data;
					break;
				case self::FORMAT_JSON:
					$this->setHeader('Content-Type', 'application/json');
					$this->content = JSON::encode($this->data);
					break;
				case self::FORMAT_JSONP:
					$this->setHeader('Content-Type', 'text/javascript; charset=' . $this->charset);
// 					if (is_array($this->data) && isset($this->data['data'], $this->data['callback'])) {
// 						$this->content = sprintf('%s(%s);', $this->data['callback'], JSON::encode($this->data['data']));
// 					} else {
					if (isset($this->jsonpcallback)) {
						$this->content = sprintf('%s(%s);', $this->jsonpcallback, JSON::encode($this->data));
					} else {
						$this->content = '';
					}
					break;
				case self::FORMAT_XML:
					Sky::createComponent(array('class'=>'Sky\web\XmlResponse'))->format($this);
					break;
				default:
					throw new \Exception("Unsupported response format: {$this->format}");
			}
		}
	}
	
	/**
	 * 向客户端发送请求头。
	 */
	protected function sendHeaders()
	{
		if (headers_sent()) {
			return;
		}
		header("HTTP/{$this->version} 200 OK");
		if ($this->_headers) 
		{
			foreach ($this->_headers as $name => $value)
			{
				header("$name: $value");
			}
		}
	}
	
	/**
	 * 向客户端发送响应内容。
	 */
	protected function sendContent()
	{
		echo $this->content;
	}
	
	public function setHeader($name, $value)
	{
		$this->_headers[$name] = $value;
	}
}