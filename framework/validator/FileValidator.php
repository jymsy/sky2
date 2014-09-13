<?php
namespace Sky\validator;

use Sky\web\UploadFile;
class FileValidator extends Validator{
	public $allowEmpty=false;
	public $types;
	public $mimeTypes;
	public $minSize;
	public $maxSize;
	public $tooLarge;
	public $tooSmall;
	public $wrongType;
	public $wrongMimeType;
	public $safe=false;
	
	
	public function validateAttribute($object, $attribute)
	{
		{
			$file = $object->$attribute;
			if(!$file instanceof UploadFile)
			{
				$file = UploadFile::getInstance($object, $attribute);
				if(null===$file)
					return $this->emptyAttribute($object, $attribute);
			}
			$this->validateFile($object, $attribute, $file);
		}
	}
	
	protected function emptyAttribute($object, $attribute)
	{
		if(!$this->allowEmpty)
		{
			$message=$this->message!==null?$this->message : '{attribute} cannot be blank.';
			$this->addError($object,$attribute,$message);
		}
	}
	
	protected function validateFile($object, $attribute, $file)
	{
		if(null===$file || ($error=$file->getError())==UPLOAD_ERR_NO_FILE)
			return $this->emptyAttribute($object, $attribute);
		elseif($error==UPLOAD_ERR_INI_SIZE || $error==UPLOAD_ERR_FORM_SIZE || $this->maxSize!==null && $file->getSize()>$this->maxSize)
		{
			$message=$this->tooLarge!==null?$this->tooLarge : 'The file "{file}" is too large. Its size cannot exceed {limit} bytes.';
			$this->addError($object,$attribute,$message,array('{file}'=>$file->getName(), '{limit}'=>$this->getSizeLimit()));
		}
		elseif($error==UPLOAD_ERR_PARTIAL)
			throw new \Exception('The file "'.$file->getName().'" was only partially uploaded.');
		elseif($error==UPLOAD_ERR_NO_TMP_DIR)
			throw new \Exception('Missing the temporary folder to store the uploaded file "'.$file->getName().'".');
		elseif($error==UPLOAD_ERR_CANT_WRITE)
			throw new \Exception('Failed to write the uploaded file "'.$file->getName().'" to disk.');
		elseif(defined('UPLOAD_ERR_EXTENSION') && $error==UPLOAD_ERR_EXTENSION)  // available for PHP 5.2.0 or above
			throw new \Exception('A PHP extension stopped the file upload.');
		
		if($this->minSize!==null && $file->getSize()<$this->minSize)
		{
			$message=$this->tooSmall!==null?$this->tooSmall : 'The file "{file}" is too small. Its size cannot be smaller than {limit} bytes.';
			$this->addError($object,$attribute,$message,array('{file}'=>$file->getName(), '{limit}'=>$this->minSize));
		}
		
		if($this->types!==null)
		{
			if(is_string($this->types))
				$types=preg_split('/[\s,]+/',strtolower($this->types),-1,PREG_SPLIT_NO_EMPTY);
			else
				$types=$this->types;
			if(!in_array(strtolower($file->getExtensionName()),$types))
			{
				$message=$this->wrongType!==null?$this->wrongType : 'The file "{file}" cannot be uploaded. Only files with these extensions are allowed: {extensions}.';
				$this->addError($object,$attribute,$message,array('{file}'=>$file->getName(), '{extensions}'=>implode(', ',$types)));
			}
		}
		
		if($this->mimeTypes!==null)
		{
			$mimeType=false;
			if($info=finfo_open(defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME))
				$mimeType=finfo_file($info,$file->getTempName());
		
			if(is_string($this->mimeTypes))
				$mimeTypes=preg_split('/[\s,]+/',strtolower($this->mimeTypes),-1,PREG_SPLIT_NO_EMPTY);
			else
				$mimeTypes=$this->mimeTypes;
		
			if($mimeType===false || !in_array(strtolower($mimeType),$mimeTypes))
			{
				$message=$this->wrongMimeType!==null?$this->wrongMimeType : 'The file "{file}" cannot be uploaded. Only files of these MIME-types are allowed: {mimeTypes}.';
				$this->addError($object,$attribute,$message,array('{file}'=>$file->getName(), '{mimeTypes}'=>implode(', ',$mimeTypes)));
			}
		}
	}
	
	protected function getSizeLimit()
	{
		$limit=ini_get('upload_max_filesize');
		$limit=$this->sizeToBytes($limit);
		if($this->maxSize!==null && $limit>0 && $this->maxSize<$limit)
			$limit=$this->maxSize;
		if(isset($_POST['MAX_FILE_SIZE']) && $_POST['MAX_FILE_SIZE']>0 && $_POST['MAX_FILE_SIZE']<$limit)
			$limit=$_POST['MAX_FILE_SIZE'];
		return $limit;
	}
	
	public function sizeToBytes($sizeStr)
	{
		// get the latest character
		switch (strtolower(substr($sizeStr, -1)))
		{
			case 'm': return (int)$sizeStr * 1048576; // 1024 * 1024
			case 'k': return (int)$sizeStr * 1024; // 1024
			case 'g': return (int)$sizeStr * 1073741824; // 1024 * 1024 * 1024
			default: return (int)$sizeStr; // do nothing
		}
	}
}