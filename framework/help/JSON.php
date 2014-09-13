<?php
namespace Sky\help;

class JSON{
	/**
	* 将变量编码为JSON格式。
	*
	* @param mixed $var 要被编码的变量。
	* 如果变量为string，它将被先转为UTF-8格式。
	* @return string JSON字符串
	*/
	public static function encode($var)
	{
		switch (gettype($var))
		{
			case 'boolean':
				return $var ? 'true' : 'false';
			
			case 'NULL':
				return 'null';
			
			case 'integer':
				return (int) $var;
			
			case 'double':
			case 'float':
				return str_replace(',','.',(float)$var); // locale-independent representation
			case 'string':
				if (($enc=strtoupper(\Sky\Sky::$app->charset))!=='UTF-8')
					$var=iconv($enc, 'UTF-8', $var);
		
				return json_encode($var);
			default:
				return json_encode($var);
		}
	}
	
	/**
	 * 将JSON字符串解码为变量。
	 *
	 * @param string $str  JSON格式的字符串。
	 * @param boolean $useArray 是否用数组代替对象
	 * @return mixed   number, boolean, string, array, 或object
	 */
	public static function decode($str, $useArray=true)
	{
		$json = json_decode($str,$useArray);
	
		// based on investigation, native fails sometimes returning null.
		// see: http://gggeek.altervista.org/sw/article_20070425.html
		// As of PHP 5.3.6 it still fails on some valid JSON strings
		if(!is_null($json))
			return $json;
		else
		{
			//need to fix
			return $json;
		}
	}
}