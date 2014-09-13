<?php
namespace Sky\logging;
use Sky\utils\VarDump;

use \Sky\utils;

class LogRouter extends \Sky\base\Component{
	private $_routes=array();
	
	/**
	 * 初始化应用组件
	 */
	public function init(){
// 		parent::init();
		foreach($this->_routes as $name=>$route){
			$route=\Sky\Sky::createComponent($route);
			$route->init();
			$this->_routes[$name]=$route;
		}
// 		VarDump::dump($this->_routes);
		\Sky\Sky::getLogger()->attachEventHandler('onFlush',array($this,'collectLogs'));
		\Sky\Sky::$app->attachEventHandler('onEndRequest',array($this,'processLogs'));
	}
	
	public function setRoutes($config){
		foreach($config as $name=>$route){
			$this->_routes[$name]=$route;
		}
// 		\Sky\utils\VarDump::dump($this->_routes);
	}
	
	/**
	 * Collects log messages from a logger.
	 * This method is an event handler to the {@link Logger::onFlush} event.
	 * @param \Sky\base\Event $event 事件参数
	 */
	public function collectLogs($event)
	{
		$logger=\Sky\Sky::getLogger();
		$dumpLogs=isset($event->params['dumpLogs']) && $event->params['dumpLogs'];
		foreach($this->_routes as $route)
		{
			if($route->enabled)
				$route->collectLogs($logger,$dumpLogs);
		}
	}
	
	/**
	 * Collects and processes log messages from a logger.
	 * This method is an event handler to the {@link \Sky\base\Application::onEndRequest} event.
	 * @param \Sky\base\Event $event 事件参数
	 */
	public function processLogs($event){
		$logger=\Sky\Sky::getLogger();
		foreach($this->_routes as $route){
			if($route->enabled)
				$route->collectLogs($logger,true);
		}
	}
}