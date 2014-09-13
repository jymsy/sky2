<?php
use Sky\Sky;
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));

// defined('SKY_DEBUG') or define('SKY_DEBUG',true);

require_once(__DIR__.'/sky.php');
if(isset($config)){
	$app=Sky::createConsoleApplication($config);
}else
	$app=Sky::createConsoleApplication(array('basePath'=>__DIR__.'/cli','name'=>'Command App'));

$env=@getenv('SKY_CONSOLE_COMMANDS');
if(!empty($env))
	$app->commandRunner->addCommands($env);

$app->run();