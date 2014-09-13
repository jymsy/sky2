<?php
namespace Sky\validator;

/**
 * RequiredValidator 验证特定的属性不是null或空值
 * @author Jiangyumeng
 *
 */
class RequiredValidator extends Validator{
	/**
	 * @var mixed 期望属性要有的值
	 * 如果为null，验证器将会验证指定的属性不是null或空值。
	 * 如果不是null，验证器将会验证属性值是否和它相等。
	 * 默认为null。
	 */
	public $requiredValue;
	/**
	 * @var boolean 是否严格的比较 {@link requiredValue}.
	 * 当为true的时候，意味着类型和值都要和{@link requiredValue}相等.
	 * 默认为false，意味着只比较值
	 * 该属性只在 {@link requiredValue} 为null的时候使用。
	 */
	public $strict=false;
	/**
	 * @var boolean 在比较的时候是否对字符串调用trim()方法。
	 */
	public $trim=true;
	
	public function validateAttribute($object,$attribute)
	{
		$value=$object->$attribute;
		if($this->requiredValue!==null)
		{
			if(!$this->strict && $value!=$this->requiredValue || $this->strict && $value!==$this->requiredValue)
			{
				$message=$this->message!==null?$this->message:'{attribute} must be '.$this->requiredValue;
				$this->addError($object,$attribute,$message);
			}
		}
		elseif($this->isEmpty($value,$this->trim))
		{
			$message=$this->message!==null?$this->message:'{attribute} cannot be blank.';
			$this->addError($object,$attribute,$message);
		}
	}
}