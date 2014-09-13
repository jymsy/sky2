<?php 
namespace Sky\validator;

class RegularExpressionValidator extends Validator{
	public $pattern;
	public $allowEmpty=true;
	public $not=false;
	

	public function validateAttribute($object,$attribute)
	{
		$value=$object->$attribute;
		if($this->allowEmpty && $this->isEmpty($value))
			return;
		if($this->pattern===null)
			throw new \Exception('The "pattern" property must be specified with a valid regular expression.');
		if(is_array($value) ||
		(!$this->not && !preg_match($this->pattern,$value)) ||
		($this->not && preg_match($this->pattern,$value)))
		{
			$message=$this->message!==null?$this->message:'{attribute} is invalid.';
			$this->addError($object,$attribute,$message);
		}
	}
}