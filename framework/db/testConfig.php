<?php
$cfg=array(
	"product"=>"sqlite",
	"option"=>array()
);

$dsn="sqlite:".__DIR__."/blog.db";

Sky\db\ConnectionPool::loadDsn("TVOS_MASTER", array($dsn));
Sky\db\ConnectionPool::loadDsn("TVOS_SLAVE", array($dsn));