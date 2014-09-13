<?php
namespace Sky\utils;

class Gearman extends \Sky\base\Component{

	public $servers;
	protected $client;
	protected $worker;
	private $_serverArr;

	public function init(){
		$tempArr=explode(',', $this->servers);
		foreach ($tempArr as $temp){
			if (($pos=strpos($temp, ':'))!==false) {
				$host=substr($temp, 0,$pos);
				$port=substr($temp, $pos+1);
			}else{
				$host='127.0.0.1';
				$port=4370;
			}
			$this->_serverArr[]=array('host'=>$host,'port'=>$port);
		}
// 		parent::init();
	}

	protected function setServers($instance){
		foreach ($this->_serverArr as $s){
			$instance->addServer($s['host'], $s['port']);
		}
// 		$instance->addServers($this->servers);

		return $instance;
	}

	public function client(){
		if (!$this->client){
			$this->client = $this->setServers(new \GearmanClient());
		}

		return $this->client;
	}

	public function worker(){
		if (!$this->worker){
			$this->worker = $this->setServers(new \GearmanWorker());
		}

		return $this->worker;
	}

}