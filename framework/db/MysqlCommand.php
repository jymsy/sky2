<?php
class Test{
	private $a="a";
	private static $b="static b";
}

$f=function ($x){
	return $x.$this->a;
};

$e=$f->bindTo($t=new Test(),"Test");

$c=Closure::bind($f, $t,"Test");
var_dump($e("abc"));
var_dump($c("123"));

abstract class XTest{
	protected function abc(){
		echo get_class($this);
	}
}

class AT extends XTest{
	
}

AT::abc();