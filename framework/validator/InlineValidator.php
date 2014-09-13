<?php
namespace Sky\validator;

/**
 * @author Jiangyumeng
 *
 */
class InlineValidator extends Validator{
	/**
	 * @var string 验证方法名。
	 */
	public $method;
	/**
	 * @var array 传给验证方法的额外参数。
	 */
	public $params;
	
	/**
	 * 验证对象的属性。
	 * @param \Sky\base\Model $object 要验证的对象。
	 * @param string $attribute 要验证的属性。
	 */
	public function validateAttribute($object, $attribute)
	{
		$method=$this->method;
		$object->$method($attribute,$this->params);
	}
}