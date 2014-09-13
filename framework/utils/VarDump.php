<?php
namespace Sky\utils;

/**
 * VarDumper旨在替换简陋的PHP函数var_dump和print_r。 
 * 它可以在复杂的对象结构中正确地识别递归对象。
 * 它同时具备递归深度控制，以避免一些古怪的变量导致地无限递归显示的问题。 
 *
 * VarDumper可以像下面这样使用：
 * <pre>
 * \Sky\utils\VarDump::dump($var);
 * </pre>
 *
 */
class VarDump{
	private static $_objects;
	private static $_output;
	private static $_depth;
	
	/**
	 * 显示一个变量。 
	 * 此方法完成的功能与var_dump和print_r类似
	 * @param mixed $var 需要显示的变量
	 * @param integer $depth 解析器处理一个变量的最大深度。默认值是10。
	 * @param boolean $highlight 结果是否进行高亮格式化
	 */
	public static function dump($var,$depth=10,$highlight=false){
		echo self::dumpAsString($var,$depth,$highlight);
	}
	
	/**
	 * 将一个变量显示的结果存储在字符串中返回
	 * @param mixed $var  需要显示的变量
	 * @param integer $depth 解析器处理一个变量的最大深度。默认值是10。
	 * @param boolean $highlight 结果是否进行高亮格式化
	 * @return string 存储了变量的显示结果的字符串
	 */
	public static function dumpAsString($var,$depth=10,$highlight=false){
		self::$_output='';
		self::$_objects=array();
		self::$_depth=$depth;
		self::dumpInternal($var,0);
		if($highlight){
			$result=highlight_string("<?php\n".self::$_output,true);
			self::$_output=preg_replace('/&lt;\\?php<br \\/>/','',$result,1);
		}
		return self::$_output;
	}
	
	/*
	* @param mixed $var 需要显示的变量
	* @param integer $level depth level
	*/
	private static function dumpInternal($var,$level){
		switch(gettype($var)){
			case 'boolean':
				self::$_output.=$var?'true':'false';
				break;
			case 'integer':
				self::$_output.="$var";
				break;
			case 'double':
				self::$_output.="$var";
				break;
			case 'string':
				self::$_output.="'".addslashes($var)."'";
				break;
			case 'resource':
				self::$_output.='{resource}';
				break;
			case 'NULL':
				self::$_output.="null";
				break;
			case 'unknown type':
				self::$_output.='{unknown}';
				break;
			case 'array':
				if(self::$_depth<=$level)
					self::$_output.='array(...)';
				elseif(empty($var))
				self::$_output.='array()';
				else{
					$keys=array_keys($var);
					$spaces=str_repeat(' ',$level*4);
					self::$_output.="array\n".$spaces.'(';
					foreach($keys as $key){
						self::$_output.="\n".$spaces.'    ';
						self::dumpInternal($key,0);
						self::$_output.=' => ';
						self::dumpInternal($var[$key],$level+1);
					}
					self::$_output.="\n".$spaces.')';
				}
				break;
			case 'object':
				if(($id=array_search($var,self::$_objects,true))!==false)
					self::$_output.=get_class($var).'#'.($id+1).'(...)';
				elseif(self::$_depth<=$level)
				self::$_output.=get_class($var).'(...)';
				else{
					$id=array_push(self::$_objects,$var);
					$className=get_class($var);
					$members=(array)$var;
					$spaces=str_repeat(' ',$level*4);
					self::$_output.="$className#$id\n".$spaces.'(';
					foreach($members as $key=>$value){
						$keyDisplay=strtr(trim($key),array("\0"=>':'));
						self::$_output.="\n".$spaces."    [$keyDisplay] => ";
						self::$_output.=self::dumpInternal($value,$level+1);
					}
					self::$_output.="\n".$spaces.')';
				}
				break;
		}
	}
}