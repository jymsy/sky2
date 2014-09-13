<?php
namespace Sky\help;

use Sky\base\Controller;
use Sky\Sky;
/**
 * Html是一个提供了一系列创建HTML视图帮助方法的静态类。
 * @author Jiangyumeng
 *
 */
class Html{
	const ID_PREFIX='st';
	public static $count=0;
	/**
	 * @var boolean 是否生成特殊属性值。默认为true。HTML5的话可设置为false.
	 */
	public static $renderSpecialAttributesValue=true;
	/**
	 * @var boolean 是否关闭单一标签。默认为true。HTML5的话可设置为false.
	 */
	public static $closeSingleTags=true;
	/**
	 * @var string 显示错误信息的css类名 (参见 {@link error}).
	 */
	public static $errorMessageCss='errorMessage';
	/**
	 * @var string 高亮错误输入的css类. 
	 */
	public static $errorCss='error';
	/**
	 * @var string 错误container的标签名. 默认为 'div'.
	 */
	public static $errorContainerTag='div';
	public static $requiredCss='required';
	/**
	 * @var string 添加到required label前面的HTML代码.
	 * @see label
	 */
	public static $beforeRequiredLabel='';
	/**
	 * @var string 添加到required label后面的HTML代码.
	 * @see label
	 */
	public static $afterRequiredLabel=' <span class="required">*</span>';
	
	public static function activePasswordField($model,$attribute,$htmlOptions=array())
	{
		self::resolveNameID($model,$attribute,$htmlOptions);
		self::clientChange('change',$htmlOptions);
		return self::activeInputField('password',$model,$attribute,$htmlOptions);
	}
	
	protected static function activeInputField($type,$model,$attribute,$htmlOptions)
	{
		$htmlOptions['type']=$type;
		if($type==='text' || $type==='password')
		{
			if(!isset($htmlOptions['maxlength']))
			{
				foreach($model->getValidators($attribute) as $validator)
				{
// 					if($validator instanceof CStringValidator && $validator->max!==null)
// 					{
// 						$htmlOptions['maxlength']=$validator->max;
// 						break;
// 					}
				}
			}
			elseif($htmlOptions['maxlength']===false)
				unset($htmlOptions['maxlength']);
		}
	
		if($type==='file')
			unset($htmlOptions['value']);
		elseif(!isset($htmlOptions['value']))
			$htmlOptions['value']=self::resolveValue($model,$attribute);
		if($model->hasErrors($attribute))
			self::addErrorCss($htmlOptions);
		return self::tag('input',$htmlOptions);
	}
	
	public static function activeTextField($model,$attribute,$htmlOptions=array())
	{
		self::resolveNameID($model,$attribute,$htmlOptions);
		self::clientChange('change',$htmlOptions);
		return self::activeInputField('text',$model,$attribute,$htmlOptions);
	}
	
	public static function activeFileField($model,$attribute,$htmlOptions=array())
	{
		self::resolveNameID($model,$attribute,$htmlOptions);
		// add a hidden field so that if a model only has a file field, we can
		// still use isset($_POST[$modelClass]) to detect if the input is submitted
		$hiddenOptions=isset($htmlOptions['id']) ? array('id'=>self::ID_PREFIX.$htmlOptions['id']) : array('id'=>false);
		return self::hiddenField($htmlOptions['name'],'',$hiddenOptions)
		. self::activeInputField('file',$model,$attribute,$htmlOptions);
	}
	
	public static function activeLabelEx($model,$attribute,$htmlOptions=array())
	{
		$realAttribute=$attribute;
		self::resolveName($model,$attribute);
		$htmlOptions['required']=$model->isAttributeRequired($attribute);
		return self::activeLabel($model,$realAttribute,$htmlOptions);
	}
	
	public static function activeLabel($model,$attribute,$htmlOptions=array())
	{
		$inputName=self::resolveName($model,$attribute);
		if(isset($htmlOptions['for']))
		{
			$for=$htmlOptions['for'];
			unset($htmlOptions['for']);
		}
		else
			$for=self::getIdByName($inputName);
		if(isset($htmlOptions['label']))
		{
			if(($label=$htmlOptions['label'])===false)
				return '';
			unset($htmlOptions['label']);
		}
		else
			$label=$model->getAttributeLabel($attribute);
		if($model->hasErrors($attribute))
			self::addErrorCss($htmlOptions);
		return self::label($label,$for,$htmlOptions);
	}
	
	public static function activeDropDownList($model,$attribute,$data,$htmlOptions=array())
	{
		self::resolveNameID($model,$attribute,$htmlOptions);
		$selection=self::resolveValue($model,$attribute);
		$options="\n".self::listOptions($selection,$data,$htmlOptions);
		self::clientChange('change',$htmlOptions);
	
		if($model->hasErrors($attribute))
			self::addErrorCss($htmlOptions);
	
		$hidden='';
// 		if(!empty($htmlOptions['multiple']))
// 		{
// 			if(substr($htmlOptions['name'],-2)!=='[]')
// 				$htmlOptions['name'].='[]';
	
// 			if(isset($htmlOptions['unselectValue']))
// 			{
// 				$hiddenOptions=isset($htmlOptions['id']) ? array('id'=>self::ID_PREFIX.$htmlOptions['id']) : array('id'=>false);
// 				$hidden=self::hiddenField(substr($htmlOptions['name'],0,-2),$htmlOptions['unselectValue'],$hiddenOptions);
// 				unset($htmlOptions['unselectValue']);
// 			}
// 		}
		return $hidden . self::tag('select',$htmlOptions,$options);
	}
	
	public static function listOptions($selection,$listData,&$htmlOptions)
	{
		$raw=isset($htmlOptions['encode']) && !$htmlOptions['encode'];
		$content='';
		if(isset($htmlOptions['prompt']))
		{
			$content.='<option value="">'.strtr($htmlOptions['prompt'],array('<'=>'&lt;','>'=>'&gt;'))."</option>\n";
			unset($htmlOptions['prompt']);
		}
		if(isset($htmlOptions['empty']))
		{
			if(!is_array($htmlOptions['empty']))
				$htmlOptions['empty']=array(''=>$htmlOptions['empty']);
			foreach($htmlOptions['empty'] as $value=>$label)
				$content.='<option value="'.self::encode($value).'">'.strtr($label,array('<'=>'&lt;','>'=>'&gt;'))."</option>\n";
			unset($htmlOptions['empty']);
		}
	
		if(isset($htmlOptions['options']))
		{
			$options=$htmlOptions['options'];
			unset($htmlOptions['options']);
		}
		else
			$options=array();
	
		$key=isset($htmlOptions['key']) ? $htmlOptions['key'] : 'primaryKey';
		if(is_array($selection))
		{
			foreach($selection as $i=>$item)
			{
				if(is_object($item))
					$selection[$i]=$item->$key;
			}
		}
		elseif(is_object($selection))
		$selection=$selection->$key;
	
		foreach($listData as $key=>$value)
		{
			if(is_array($value))
			{
				$content.='<optgroup label="'.($raw?$key : self::encode($key))."\">\n";
				$dummy=array('options'=>$options);
				if(isset($htmlOptions['encode']))
					$dummy['encode']=$htmlOptions['encode'];
				$content.=self::listOptions($selection,$value,$dummy);
				$content.='</optgroup>'."\n";
			}
			else
			{
				$attributes=array('value'=>(string)$key,'encode'=>!$raw);
				if(!is_array($selection) && !strcmp($key,$selection) || is_array($selection) && in_array($key,$selection))
					$attributes['selected']='selected';
				if(isset($options[$key]))
					$attributes=array_merge($attributes,$options[$key]);
				$content.=self::tag('option',$attributes,$raw?(string)$value : self::encode((string)$value))."\n";
			}
		}
	
		unset($htmlOptions['key']);
	
		return $content;
	}
	

	public static function textArea($name,$value='',$htmlOptions=array())
	{
		$htmlOptions['name']=$name;
		if(!isset($htmlOptions['id']))
			$htmlOptions['id']=self::getIdByName($name);
		elseif($htmlOptions['id']===false)
		unset($htmlOptions['id']);
		self::clientChange('change',$htmlOptions);
		return self::tag('textarea',$htmlOptions,isset($htmlOptions['encode']) && !$htmlOptions['encode'] ? $value : self::encode($value));
	}
	
	public static function resolveValue($model,$attribute)
	{
		if(($pos=strpos($attribute,'['))!==false)
		{
			if($pos===0) // [a]name[b][c], should ignore [a]
			{
				if(preg_match('/\](\w+(\[.+)?)/',$attribute,$matches))
					$attribute=$matches[1]; // we get: name[b][c]
				if(($pos=strpos($attribute,'['))===false)
					return $model->$attribute;
			}
			$name=substr($attribute,0,$pos);
			$value=$model->$name;
			foreach(explode('][',rtrim(substr($attribute,$pos+1),']')) as $id)
			{
				if((is_array($value) || $value instanceof ArrayAccess) && isset($value[$id]))
					$value=$value[$id];
				else
					return null;
			}
			return $value;
		}
		else
			return $model->$attribute;
	}
	
	public static function label($label,$for,$htmlOptions=array())
	{
		if($for===false)
			unset($htmlOptions['for']);
		else
			$htmlOptions['for']=$for;
		if(isset($htmlOptions['required']))
		{
			if($htmlOptions['required'])
			{
				if(isset($htmlOptions['class']))
					$htmlOptions['class'].=' '.self::$requiredCss;
				else
					$htmlOptions['class']=self::$requiredCss;
				$label=self::$beforeRequiredLabel.$label.self::$afterRequiredLabel;
			}
			unset($htmlOptions['required']);
		}
		return self::tag('label',$htmlOptions,$label);
	}
	
	/**
	 * 添加{@link errorCss}到'class'属性.
	 * @param array $htmlOptions  要修改的HTML选项
	 */
	protected static function addErrorCss(&$htmlOptions)
	{
		if(empty(self::$errorCss))
			return;
	
		if(isset($htmlOptions['class']))
			$htmlOptions['class'].=' '.self::$errorCss;
		else
			$htmlOptions['class']=self::$errorCss;
	}
	
	protected static function clientChange($event,&$htmlOptions)
	{
		
	}
	
	/**
	 * 显示一个model属性的第一个验证错误。
	 * @param Model $model 数据model
	 * @param string $attribute 属性名
	 * @param array $htmlOptions 要在container标签中显示的额外HTML属性。
	 * @return string 要显示的错误. 如果没有错误的话为空。
	 * @see Model::getErrors
	 * @see errorMessageCss
	 * @see $errorContainerTag
	 */
	public static function error($model,$attribute,$htmlOptions=array())
	{
		self::resolveName($model,$attribute); // turn [a][b]attr into attr
		$error=$model->getError($attribute);
		if($error!='')
		{
			if(!isset($htmlOptions['class']))
				$htmlOptions['class']=self::$errorMessageCss;
			return self::tag(self::$errorContainerTag,$htmlOptions,$error);
		}
		else
			return '';
	}
	
	public static function resolveNameID($model,&$attribute,&$htmlOptions)
	{
		if(!isset($htmlOptions['name']))
			$htmlOptions['name']=self::resolveName($model,$attribute);
		if(!isset($htmlOptions['id']))
			$htmlOptions['id']=self::getIdByName($htmlOptions['name']);
		elseif($htmlOptions['id']===false)
			unset($htmlOptions['id']);
	}
	
	public static function resolveName($model,&$attribute)
	{
		$modelName=self::modelName($model);
	
		if(($pos=strpos($attribute,'['))!==false)
		{
			if($pos!==0)  // e.g. name[a][b]
				return $modelName.'['.substr($attribute,0,$pos).']'.substr($attribute,$pos);
			if(($pos=strrpos($attribute,']'))!==false && $pos!==strlen($attribute)-1)  // e.g. [a][b]name
			{
				$sub=substr($attribute,0,$pos+1);
				$attribute=substr($attribute,$pos+1);
				return $modelName.$sub.'['.$attribute.']';
			}
			if(preg_match('/\](\w+\[.*)$/',$attribute,$matches))
			{
				$name=$modelName.'['.str_replace(']','][',trim(strtr($attribute,array(']['=>']','['=>']')),']')).']';
				$attribute=$matches[1];
				return $name;
			}
		}
		return $modelName.'['.$attribute.']';
	}
	
	public static function modelName($model)
	{
// 		if(is_callable(self::$_modelNameConverter))
// 			return call_user_func(self::$_modelNameConverter,$model);
	
		$className=is_object($model) ? get_class($model) : (string)$model;
		return trim(str_replace('\\','_',$className),'_');
	}
	
	/**
	 * Generates a submit button.
	 * @param string $label the button label
	 * @param array $htmlOptions additional HTML attributes. Besides normal HTML attributes, a few special
	 * attributes are also recognized (see {@link clientChange} and {@link tag} for more details.)
	 * @return string the generated button tag
	 * @see clientChange
	 */
	public static function submitButton($label='submit',$htmlOptions=array())
	{
		$htmlOptions['type']='submit';
		return self::button($label,$htmlOptions);
	}
	
	/**
	 * Generates a button.
	 * @param string $label the button label
	 * @param array $htmlOptions additional HTML attributes. Besides normal HTML attributes, a few special
	 * attributes are also recognized (see {@link clientChange} and {@link tag} for more details.)
	 * @return string the generated button tag
	 * @see clientChange
	 */
	public static function button($label='button',$htmlOptions=array())
	{
		if(!isset($htmlOptions['name']))
		{
			if(!array_key_exists('name',$htmlOptions))
				$htmlOptions['name']=self::ID_PREFIX.self::$count++;
		}
		if(!isset($htmlOptions['type']))
			$htmlOptions['type']='button';
		if(!isset($htmlOptions['value']) && $htmlOptions['type']!='image')
			$htmlOptions['value']=$label;
		self::clientChange('click',$htmlOptions);
		return self::tag('input',$htmlOptions);
	}
	
	/**
	 * Generates an opening form tag.
	 * Note, only the open tag is generated. A close tag should be placed manually
	 * at the end of the form.
	 * @param mixed $action the form action URL (see {@link normalizeUrl} for details about this parameter.)
	 * @param string $method form method (e.g. post, get)
	 * @param array $htmlOptions additional HTML attributes (see {@link tag}).
	 * @return string the generated form tag.
	 * @see endForm
	 */
	public static function beginForm($action='',$method='post',$htmlOptions=array())
	{
		$htmlOptions['action']=$url=self::normalizeUrl($action);
		$htmlOptions['method']=$method;
		$form=self::tag('form',$htmlOptions,false,false);
		$hiddens=array();
		if(!strcasecmp($method,'get') && ($pos=strpos($url,'?'))!==false)
		{
			foreach(explode('&',substr($url,$pos+1)) as $pair)
			{
				if(($pos=strpos($pair,'='))!==false)
					$hiddens[]=self::hiddenField(urldecode(substr($pair,0,$pos)),urldecode(substr($pair,$pos+1)),array('id'=>false));
				else
					$hiddens[]=self::hiddenField(urldecode($pair),'',array('id'=>false));
			}
		}
		if($hiddens!==array())
			$form.="\n".self::tag('div',array('style'=>'display:none'),implode("\n",$hiddens));
		return $form;
	}
	
	/**
	 * Generates a closing form tag.
	 * @return string the generated tag
	 * @see beginForm
	 */
	public static function endForm()
	{
		return '</form>';
	}
	
	public static function normalizeUrl($url)
	{
		if(is_array($url))
		{
			if(isset($url[0]))
			{
				if(($c=Sky::$app->getController())!==null)
					$url=$c->createUrl($url[0],array_splice($url,1));
				else
					$url=Sky::$app->createUrl($url[0],array_splice($url,1));
			}
			else
				$url='';
		}
		return $url==='' ? Sky::$app->getRequest()->getUrl() : $url;
	}
	
	/**
	 * 将指定字符串编码为HTML实体。
	 * @param string $text 要编码的数据
	 * @return string 编码后的数据
	 * @see http://www.php.net/manual/en/function.htmlspecialchars.php
	 */
	public static function encode($text){
		return htmlspecialchars($text,ENT_QUOTES,\Sky\Sky::$app->charset);
	}
	
	/**
	 * 生成一个打开的form标签。
	 * @param mixed $action form的action URL (see {@link normalizeUrl})
	 * @param string $method form的提交方法 (e.g. post, get)
	 * @param array $htmlOptions additional HTML attributes (参见 {@link tag}).
	 * @return string 生成的form标签。
	 */
	public static function form($action='',$method='post',$htmlOptions=array())
	{
		return self::beginForm($action,$method,$htmlOptions);
	}
	
	/**
	 * Generates a valid HTML ID based on name.
	 * @param string $name name from which to generate HTML ID
	 * @return string the ID generated based on name.
	 */
	public static function getIdByName($name)
	{
		return str_replace(array('[]','][','[',']',' '),array('','_','_','','_'),$name);
	}
	
	/**
	 * Generates a hidden input.
	 * @param string $name the input name
	 * @param string $value the input value
	 * @param array $htmlOptions additional HTML attributes (see {@link tag}).
	 * @return string the generated input field
	 * @see inputField
	 */
	public static function hiddenField($name,$value='',$htmlOptions=array())
	{
		return self::inputField('hidden',$name,$value,$htmlOptions);
	}
	
	/**
	 * Generates an input HTML tag.
	 * This method generates an input HTML tag based on the given input name and value.
	 * @param string $type the input type (e.g. 'text', 'radio')
	 * @param string $name the input name
	 * @param string $value the input value
	 * @param array $htmlOptions additional HTML attributes for the HTML tag (see {@link tag}).
	 * @return string the generated input tag
	 */
	protected static function inputField($type,$name,$value,$htmlOptions)
	{
		$htmlOptions['type']=$type;
		$htmlOptions['value']=$value;
		$htmlOptions['name']=$name;
		if(!isset($htmlOptions['id']))
			$htmlOptions['id']=self::getIdByName($name);
		elseif($htmlOptions['id']===false)
			unset($htmlOptions['id']);
		return self::tag('input',$htmlOptions);
	}
	
	/**
	 * 创建image标签。
	 * @param string $src 图片URL
	 * @param string $alt 要显示的替代文字
	 * @param array $htmlOptions additional HTML attributes (see {@link tag}).
	 * @return string 生成的image标签
	 */
	public static function image($src,$alt='',$htmlOptions=array()){
		$htmlOptions['src']=$src;
		$htmlOptions['alt']=$alt;
		return self::tag('img',$htmlOptions);
	}
	
	/**
	 * Generates a hidden field for storing persistent page states.
	 * This method is internally used by {@link statefulForm}.
	 * @param string $value the persistent page states in serialized format
	 * @return string the generated hidden field
	 */
	public static function pageStateField($value)
	{
		return '<input type="hidden" name="'.Controller::STATE_INPUT_NAME.'" value="'.$value.'" />';
	}
	
	/**
	 * 生成有状态的form标签。
	 * A stateful form tag is similar to {@link form} except that it renders an additional
	 * hidden field for storing persistent page states. You should use this method to generate
	 * a form tag if you want to access persistent page states when the form is submitted.
	 * @param mixed $action form的action URL (see {@link normalizeUrl} for details about this parameter.)
	 * @param string $method form的提交方法 (e.g. post, get)
	 * @param array $htmlOptions HTML 属性 (参见 {@link tag}).
	 * @return string 生成的form标签
	 */
	public static function statefulForm($action='',$method='post',$htmlOptions=array())
	{
		return self::form($action,$method,$htmlOptions)."\n".
				self::tag('div',array('style'=>'display:none'),self::pageStateField(''));
	}
	
	/**
	 * Generates an HTML element.创建一个HTML标签。
	 * @param string $tag 标签名
	 * @param array $htmlOptions 元素属性。值将会通过{@link encode()}被HTML-encoded.
	 * 如果'encode'属性被设置，而且值为false，其余的属性将不会被HTML-encoded.
	 * @param mixed $content 标签之间要填充的内容。它将不会 HTML-encoded.
	 * 如果为false，意味着没有内容。
	 * @param boolean $closeTag 是否生成闭合标签。
	 * @return string 生成的HTML标签。
	 */
	public static function tag($tag,$htmlOptions=array(),$content=false,$closeTag=true){
		$html='<' . $tag . self::renderAttributes($htmlOptions);
		if($content===false)
			return $closeTag && self::$closeSingleTags ? $html.' />' : $html.'>';
		else
			return $closeTag ? $html.'>'.$content.'</'.$tag.'>' : $html.'>'.$content;
	}
	
	/**
	 * 生成超链接标签
	 * @param string $text 链接的内容.它不会被 HTML-encoded. 因此你可以传递HTML代码，例如image标签.
	 * @param mixed $url 一个URL或一个action的路由。详细参见 {@link normalizeUrl}
	 * @param array $htmlOptions 额外的HTML属性。Besides normal HTML attributes, a few special
	 * attributes are also recognized (see {@link clientChange} and {@link tag} for more details.)
	 * @return string the 生成的超链接
	 * @see normalizeUrl
	 * @see clientChange
	 */
	public static function link($text,$url='#',$htmlOptions=array())
	{
		if($url!=='')
			$htmlOptions['href']=self::normalizeUrl($url);
		self::clientChange('click',$htmlOptions);
		return self::tag('a',$htmlOptions,$text);
	}
	
	/**
	 * 生成HTML标签属性。
	 * 如果属性值为null将不会生成
	 * 特殊属性，例如 'checked', 'disabled', 'readonly', 会根据它们的boolean值生成。
	 * @param array $htmlOptions 要生成的属性
	 * @return string 生成结果
	 */
	public static function renderAttributes($htmlOptions){
		$specialAttributes=array(
				'async'=>1,
				'autofocus'=>1,
				'autoplay'=>1,
				'checked'=>1,
				'controls'=>1,
				'declare'=>1,
				'default'=>1,
				'defer'=>1,
				'disabled'=>1,
				'formnovalidate'=>1,
				'hidden'=>1,
				'ismap'=>1,
				'loop'=>1,
				'multiple'=>1,
				'muted'=>1,
				'nohref'=>1,
				'noresize'=>1,
				'novalidate'=>1,
				'open'=>1,
				'readonly'=>1,
				'required'=>1,
				'reversed'=>1,
				'scoped'=>1,
				'seamless'=>1,
				'selected'=>1,
				'typemustmatch'=>1,
		);
	
		if($htmlOptions===array())
			return '';
	
		$html='';
		if(isset($htmlOptions['encode'])){
			$raw=!$htmlOptions['encode'];
			unset($htmlOptions['encode']);
		}else
			$raw=false;
	
		foreach($htmlOptions as $name=>$value){
			if(isset($specialAttributes[$name])){
				if($value){
					$html .= ' ' . $name;
					if(self::$renderSpecialAttributesValue)
						$html .= '="' . $name . '"';
				}
			}elseif($value!==null)
				$html .= ' ' . $name . '="' . ($raw ? $value : self::encode($value)) . '"';
		}
	
		return $html;
	}
}