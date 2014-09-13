<?php
namespace Sky\web;

/**
 * WsdlGenerator 为一个给定的类生成WSDL
 * 
 * WSDL的生成是基于服务类文件中的文档注释。
 *  需要注意的是，它只认识‘action’开头的方法（除了actions），
 *  而且该方法必须是public方法。
 *   
 * 在文档注释中，类型和名称包括每一个输入的参数
 * 和返回值的类型都应使用标准的phpdoc格式。 
 * 
 * 目前客户端识别的参数和返回类型为（区分大小写）：
 * string , int, float, boolean, array , object, mixed
 * 
 * 下面是声明一个远程调用方法的示例：
 * <pre>
 * 	/ **
 *	  * 获取指定分类的应用列表。
 * 	  * @param int  分类id
 *	  * @param int  每页显示数目
 *	  * @param int  第几页
 *	  * @return array 应用列表
 *	  * /
 *	 public function actionGetApp($category,$page_size,$page_index){...}
 *  <pre>
 * 
 * @author Jiangyumeng
 *
 */
class WsdlGenerator extends \Sky\base\Component{
	
	public $className;
	
	public $namespace;
	protected $messages;
	protected $operations;
	protected $types;
	
	protected static $typeMap=array(
			'object'=>'object:',
			'array'=>'array:',
			);
	
	/**
	 * 为指定的类生成WSDL
	 * @param string $className 类名
	 * @param string $namespace 类的namespace
	 * @param string $encoding WSDL的编码。默认是 'UTF-8'
	 * @return string 生成的WSDL
	 */
	public function generateWsdl($className, $namespace, $encoding='UTF-8'){
		$this->operations=array();
		$this->types=array();
		$this->messages=array();

		if($this->className===null)
			$this->className=$className;
		if($this->namespace===null)
			$this->namespace=$namespace;

		$reflection=new \ReflectionClass($className);
		foreach($reflection->getMethods() as $method){
			if($method->isPublic())
				$this->processMethod($method);
		}
		$className=substr($className, strrpos($className, '\\')+1);
		$this->className=substr($className,0,strripos($className, 'Controller'));
		
		return $this->buildDOM($encoding)->saveXML();
	}
	
	/**
	 * @param ReflectionMethod $method method
	 */
	protected function processMethod($method){
		$methodName=$method->getName();

		if(strpos($methodName,'action')===false)
			return;
		if(!strcasecmp($methodName, 'actions'))
			return;
		$comment=$method->getDocComment();
		$comment=strtr($comment,array("\r\n"=>"\n","\r"=>"\n")); // make line endings consistent: win -> unix, mac -> unix
		
		$comment=preg_replace('/^\s*\**(\s*?$|\s*)/m','',$comment);
		
		$params=$method->getParameters();
		$message=array();
		$n=preg_match_all('/^@param\s+([\w\.]+(\[\s*\])?)\s*?(.*)$/im',$comment,$matches);
		if($n>count($params))
			$n=count($params);
		for($i=0;$i<$n;++$i)
			$message[$params[$i]->getName()]=array($matches[1][$i], trim($matches[3][$i])); // name => type, doc
	
		$this->messages[$methodName]=$message;
		$returnStr='';
		if(preg_match('/^@return\s+([\w\.|]+)\s*?(.*)$/im',$comment,$matches)){
			if(strpos($matches[1],'|')!==false){
				$retArr=explode('|', $matches[1]);
				foreach ($retArr as $ret){
					$returnStr.=$this->processType($ret).'|';
				}
				$returnStr=rtrim($returnStr,'|');
			}else 
				$returnStr=$this->processType($matches[1]);
			$return=array($returnStr,trim($matches[2])); // type, doc
		}else
			$return=null;
		$this->messages[$methodName]['return']=$return;
	
		if(preg_match('/^\/\*+\s*([^@]*?)\n@/s',$comment,$matches))
			$doc=trim($matches[1]);
		else
			$doc='';
		$this->operations[$methodName]=$doc;
	}
	
	/**
	 * 解析返回值类型
	 * @param string $returnType
	 * @return string 返回字符串
	 */
	protected function processType($returnType){
		if(($pos=strpos($returnType, '_'))!==false){
			$type=substr($returnType,0,$pos);
			$object=substr($returnType,$pos+1);
			if(isset($this->types[$object])){
				return $type.':'.$object;
			}

			$this->types[$object]=array();
			$classDir=substr($this->className, 0,strrpos($this->className,'\\'));
			$reflection=new \ReflectionClass($classDir.'\\wsdlObject\\'.$object);
			
			foreach ($reflection->getProperties() as $prop){
				$comment=$prop->getDocComment();
				if($prop->isPublic()){
					if(preg_match('/@var\s+([\w\.]+(\[\s*\])?)\s*?(.*)$/mi',$comment,$matches)){
							
							$this->types[$object][$prop->getName()]=array($matches[1],trim($matches[3]));// type, doc
// 							var_dump($this->types);
					}
				}
			}
			if(isset(self::$typeMap[$type])){
				return self::$typeMap[$type].$object;
			}
		}else{
			return $returnType;
		}
	}
	
	/**
	 * @param string $encoding WSDL的编码。默认是 'UTF-8'
	 */
	protected function buildDOM($encoding)
	{
		$xml="<?xml version=\"1.0\" encoding=\"$encoding\"?>
		<wsdl><top><namespace value=\"{$this->namespace}\"/>
		<classname value=\"{$this->className}\"/></top></wsdl>";
	
		$dom=new \DOMDocument();
		$dom->formatOutput=true;
		$dom->loadXml($xml);
		
		$this->addMessages($dom);
		$this->addTypes($dom);
		return $dom;
	}
	
	/**
	 * @param \DOMDocument $dom
	 */
	protected function addTypes($dom){
		if($this->types===array())
			return;
		$types=$dom->createElement('types');
		foreach ($this->types as $name=>$properties){
			$typeElement=$dom->createElement('object');
			$typeElement->setAttribute('name',$name);
			foreach($properties as $propertyName=>$propValue){
				$proElement=$dom->createElement('property');
				$proElement->setAttribute('name',$propertyName);
				$proElement->setAttribute('type',$propValue[0]);
				$proElement->setAttribute('desc',$propValue[1]);
				$typeElement->appendChild($proElement);
			}
			$types->appendChild($typeElement);
		}
		$dom->documentElement->appendChild($types);
	}
	
	/**
	 * @param \DOMDocument $dom
	 */
	protected function addMessages($dom){
		foreach($this->messages as $name=>$message){

			$element=$dom->createElement('function');
			$element->setAttribute('name',str_replace('action', '', $name));
			$descElement=$dom->createElement('desc',$this->operations[$name]);
			$element->appendChild($descElement);
			
			if(isset($this->messages[$name]['return']) 
					&& is_array($this->messages[$name]['return'])){
				$retElement=$dom->createElement('return');
				$retElement->setAttribute('type',$this->messages[$name]['return'][0]);
				$retElement->setAttribute('desc',$this->messages[$name]['return'][1]);
				$element->appendChild($retElement);
				unset($this->messages[$name]['return']);
			}
			
			foreach($this->messages[$name] as $partName=>$part)
			{
				if(is_array($part))
				{					
					$partElement=$dom->createElement('param');
					$partElement->setAttribute('name',$partName);
					$partElement->setAttribute('type',$part[0]);
					$partElement->setAttribute('desc',$part[1]);
					$element->appendChild($partElement);
				}
			}
			$dom->documentElement->appendChild($element);
		}
	}
}