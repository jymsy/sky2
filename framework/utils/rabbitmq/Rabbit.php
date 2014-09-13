<?php
namespace Sky\utils\rabbitmq;

use Sky\base\Component;
/**
 * RabbitMQ wrapper
 * 
 * 
 * RabbitMQ 官方主页：http://www.rabbitmq.com
 * @author Jiangyumeng
 *
 */
class Rabbit extends Component{
	/**
	 * @var string rabbitmq 地址
	 */
	public $host='';
	/**
	 * @var integer 端口
	 */
	public $port=5672;
	/**
	 * @var string 登陆用户名，默认guest
	 */
	public $login='guest';
	/**
	 * @var string 登陆密码，默认guest
	 */
	public $password='guest';
	/**
	 * @var string 虚拟主机路径
	 * 其实是一个虚拟概念，类似于权限控制组。
	 * 一个Virtual Host里面可以有若干个Exchange和Queue，
	 * 但是权限控制的最小粒度是Virtual Host。
	 */
	public $vhost='/';
	
	private $server = array();
	/**
	 * @var \AMQPConnection 连接实例
	 */
	private $connection;
	/**
	 * @var \AMQPChannel 频道实例
	 */
	private $channel;
	/**
	 * @var Exchange 
	 */
	public $exchange;
	/**
	 * @var Queue
	 */
	public $queue;
	
	/* 
	 * 初始化
	 * @see \Sky\base\Component::init()
	 */
	public function init()
	{
		$this->server['host']=$this->host;
		$this->server['port']=$this->port;
		$this->server['login']=$this->login;
		$this->server['password']=$this->password;
		$this->server['vhost']=$this->vhost;
	}
	
	/**
	 * 创建连接到rabbitmq的实例
	 * @throws \Exception 连接失败
	 */
	public function createConnection()
	{
		$this->connection = new \AMQPConnection($this->server);
		if(!$this->connection->connect())
		{
			throw new \Exception('create RabbitMQ connection error');
		}
		$this->channel = new \AMQPChannel($this->connection);
		$this->exchange = new Exchange($this->channel);
		$this->queue = new Queue($this->channel);
	}
	
	/**
	 * 	设置超时时间
	 * 例如设置超时500ms，传0.5
	 * @param float $timeout 超时时间
	 * @param string $type 类型write或read
	 */
	public function setTimeOut($timeout, $type)
	{
		if ($type === 'read') {
			$this->connection->setReadTimeout($timeout);
		}else if($type === 'write'){
			$this->connection->setWriteTimeout($timeout);
		}
	}
	
	/**
	 * 检测当前连接是否可用
	 * @return boolean
	 */
	public function isConnected()
	{
		return $this->connection->isConnected();
	}
	
	/**
	 * 析构
	 */
	public function __destruct()
	{
		if($this->connection)
			$this->close();
	}
	
	/**
	 * 关闭连接
	 */
	public function close()
	{
		$this->connection->disconnect();
	}
}