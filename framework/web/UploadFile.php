<?php
namespace Sky\web;

use Sky\base\Component;
use Sky\help\Html;
/**
 * 上传文件（单）类
 * 
 * 调用{@link getInstanceByName} 来获得uploadfile
 * 的实例，然后调用{@link saveAs}将文件保存到服务器上。
 * 你也可以通过该实例查看文件的其他信息，包括{@link name},
 * {@link tempName}, {@link type}, {@link size} and {@link error}.
 * 
 * @property string $name 被上传文件的原始名字。
 * @property string $tempName 上传到服务器上的文件路径，要注意的是
 * 这是一个临时文件，在当前请求结束的时候将会被PHP自动删除。
 * @property string $type 被上传文件的MIME类型 (例如"image/gif")。
 * 由于这个MIME类型并不是在服务器端被校验，因此不要对该值报有信心。
 * @property integer $size 被上传文件的大小（字节）
 * @property integer $error 错误码
 * @property boolean $hasError 被上传的文件是否有错误。
 * 查看 {@link error} 来获取详细的错误信息
 * @property string $extensionName {@link name}的文件扩展名.扩展名不包括"."，
 * 如果{@link name}没有扩展名的话返回空字符。
 *
 * $file = UploadFile::getInstanceByName("filename");
 * $file->saveAs("/data/www/ttt.jpg");
 * 
 * @author Jiangyumeng
 *
 */
class UploadFile extends Component{
	static private $_files;
	
	private $_name;
	private $_tempName;
	private $_type;
	private $_size;
	private $_error;
	
	/**
	 * 构造函数
	 * 调用{@link getInstanceByName} 来获得uploadfile 的实例
	 * @param string $name 被上传文件的原始名字。
	 * @param string $tempName 上传到服务器上的文件路径
	 * @param string $type 被上传文件的MIME类型 (例如"image/gif")。
	 * @param integer $size 被上传文件的大小（字节）
	 * @param integer $error 错误码
	 */
	public function __construct($name,$tempName,$type,$size,$error){
		$this->_name=$name;
		$this->_tempName=$tempName;
		$this->_type=$type;
		$this->_size=$size;
		$this->_error=$error;
	}
	
	/**
	 * PHP魔术方法，当在直接打印对象的时候调用。
	 * @return string
	 */
	public function __toString(){
		return $this->_name;
	}
	
	public static function getInstance($model, $attribute)
	{
		return self::getInstanceByName(Html::resolveName($model, $attribute));
	}
	
	/**
	 * 返回uploadfile 的实例
	 * @param string $name 在input中的文件名。
	 * @return UploadedFile 被上传文件的实例
	 * 如果没有该文件名的文件被上传返回null
	 */
	public static function getInstanceByName($name){
		if(null===self::$_files)
			self::prefetchFiles();
	
		return isset(self::$_files[$name]) && self::$_files[$name]->getError()!=UPLOAD_ERR_NO_FILE ? self::$_files[$name] : null;
	}
	
	/**
	 * Returns an array of instances starting with specified array name.
	 *
	 * If multiple files were uploaded and saved as 'Files[0]', 'Files[1]',
	 * 'Files[n]'..., you can have them all by passing 'Files' as array name.
	 * @param string $name the name of the array of files
	 * @return array the array of UploadedFile objects. Empty array is returned
	 * if no adequate upload was found. Please note that this array will contain
	 * all files from all subarrays regardless how deeply nested they are.
	 */
	public static function getInstancesByName($name){
		if(null===self::$_files)
			self::prefetchFiles();
	
		$len=strlen($name);
		$results=array();
		foreach(array_keys(self::$_files) as $key)
			if(0===strncmp($key, $name.'[', $len+1) && self::$_files[$key]->getError()!=UPLOAD_ERR_NO_FILE)
				$results[] = self::$_files[$key];
		return $results;
	}
	
	/**
	 * 保存被上传的文件
	 * @param string $file 用来保存被上传文件的路径
	 * @param boolean $deleteTempFile 是否删除临时文件
	 * @return boolean 是否文件被成功保存
	 */
	public function saveAs($file,$deleteTempFile=true){
		if($this->_error==UPLOAD_ERR_OK){
			if($deleteTempFile)
				return move_uploaded_file($this->_tempName,$file);
			elseif(is_uploaded_file($this->_tempName))
				return copy($this->_tempName, $file);
			else
				return false;
		}
		else
			return false;
	}
	
	/**
	 * 初始化$_FILES变量
	 */
	protected static function prefetchFiles(){
		self::$_files = array();
		if(!isset($_FILES) || !is_array($_FILES))
			return;
	
		foreach($_FILES as $class=>$info)
			self::collectFilesRecursive($class, $info['name'], $info['tmp_name'], $info['type'], $info['size'], $info['error']);
	}
	
	/**
	 * Processes incoming files for {@link getInstanceByName}.
	 * @param string $key 
	 * @param mixed $names 
	 * @param mixed $tmp_names 
	 * @param mixed $types 
	 * @param mixed $sizes 
	 * @param mixed $errors
	 */
	protected static function collectFilesRecursive($key, $names, $tmp_names, $types, $sizes, $errors){
		if(is_array($names)){
			foreach($names as $item=>$name)
				self::collectFilesRecursive($key.'['.$item.']', $names[$item], $tmp_names[$item], $types[$item], $sizes[$item], $errors[$item]);
		}else
			self::$_files[$key] = new UploadFile($names, $tmp_names, $types, $sizes, $errors);
	}
	
	/**
	 * @return integer 错误码
	 * @see http://www.php.net/manual/en/features.file-upload.errors.php
	 */
	public function getError(){
		return $this->_error;
	}
	
	/**
	 * @return string 被上传文件的原始名字。
	 */
	public function getName(){
		return $this->_name;
	}
	
	/**
	 * 获取没有扩展名的文件名
	 * 如果没有扩展名的话返回完整文件名
	 * @return string
	 */
	public function getRawName()
	{
		if(($pos=strrpos($this->_name,'.'))!==false)
			return (string)substr($this->_name,0,$pos);
		else
			return $this->_name;
	}
	
	/**
	 * @return string 上传到服务器上的文件路径，要注意的是
 	 * 这是一个临时文件，在当前请求结束的时候将会被PHP自动删除。
	 */
	public function getTempName(){
		return $this->_tempName;
	}
	
	/**
	 * @return string 被上传文件的MIME类型 (例如"image/gif")。
     * 由于这个MIME类型并不是在服务器端被校验，因此不要对该值报有信心。
	 */
	public function getType(){
		return $this->_type;
	}
	
	/**
	 * @return integer 被上传文件的大小（字节）
	 */
	public function getSize(){
		return $this->_size;
	}
	
	/**
	 * @return boolean 被上传的文件是否有错误。
	 */
	public function getHasError(){
		return $this->_error!=UPLOAD_ERR_OK;
	}
	
	/**
	 * @return string {@link name}的文件扩展名.
	 * 扩展名不包括"."，如果{@link name}没有扩展名的话返回空字符。
	 */
	public function getExtensionName(){
		if(($pos=strrpos($this->_name,'.'))!==false)
			return (string)substr($this->_name,$pos+1);
		else
			return '';
	}
}