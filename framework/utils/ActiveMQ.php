<?php
namespace Sky\utils;

use Sky\base\Component;
/**
 * ActiveMQ 操作类
 * Usage:
 * 'activemq'=>array(
 *						'class'=>'Sky\utils\ActiveMQ',
 *						'brokerUri'=>'tcp://localhost:61612',
 *				),
 * 
 * Producer:
 * 		$stomp = Sky::$app->activemq;
 *		$stomp->send("/queue/test", "test1");
 *
 * Consumer:
 * 		$stomp = Sky::$app->activemq;
 *		$stomp->recvMsg("/queue/test",array($this, 'stompCallback'));
 *
 * function stompCallback($msg, $queue)
 *	{
 *		if ( $msg != null) {
 *			echo "Received message with body '$msg->body'\n";
 *			$queue->ack($msg);
 *		} else {
 *			echo "Failed to receive a message\n";
 *		}
 *		if($this->counter++ > $this->maxExecCount)
 *			return FALSE;  //处理5个消息后退出
 *	}
 *
 * @author Jiangyumeng
 *
 */
class ActiveMQ extends Component{
	/**
	 * @var object Stomp 实例
	 */
	private $_conn;
	/**
	 * @var string 连接的uri
	 */
	public $brokerUri='';
	public $username='';
	public $password='';
	public $brokerHeader=array();
	public $sendHeader= array();
	public $subHeader = array();
	/**
	 * @var callable 接收消息的回调函数
	 */
	private $_callback;
	
	/* 
	 * 初始化
	 * @see \Sky\base\Component::init()
	 */
	public function init()
	{
		$this->_conn = new \Stomp($this->brokerUri, $this->username, $this->password, $this->brokerHeader);
	}
	
	/**
	 * 发送消息
	 * @param string $destination 目标位置
	 * @param string $msg 消息内容
	 */
	public function send($destination, $msg)
	{
		return $this->_conn->send($destination, $msg, $this->sendHeader);
	}
	
	/**
	 * 设置发送消息头
	 * @param string $key
	 * @param string $value
	 */
	public function setSendHeader($key, $value)
	{
		$this->sendHeader[$key] = $value;
	}
	
	/**
	 * 设置接收消息头
	 * @param string $key
	 * @param string $value
	 */
	public function setSubHeader($key, $value)
	{
		$this->subHeader[$key] = $value;
	}
	
	/**
	 * 接收消息
	 * 如果再回调函数重返回FALSE，则终止接收消息。
	 * @param string $destination 接收目标
	 * @param callable $callback 接收回调函数
	 * @throws \Exception 如果回调函数不能调用
	 */
	public function recvMsg($destination, $callback)
	{
		$this->_conn->subscribe($destination, $this->subHeader);
		
		if (is_callable($callback, false, $name)) {
			$continue = TRUE;
			while($continue!==FALSE)
			{
					if ($this->_conn->hasFrame()) {
						$continue = call_user_func($callback,$this->_conn->readFrame(), $this->_conn);
					}
			}
		}else{
			throw new \Exception("function $name is not callable");
		}
	}
	
	public function __destruct()
	{
		if ($this->_conn) {
			unset($this->_conn);
		}
	}
}