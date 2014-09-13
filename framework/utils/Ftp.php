<?php
namespace Sky\utils;
/**
 * OOP Ftp library
 *
 * Usage 
 *
 * The following code is the component registration in the config file:
 *
 * 'components'=>array(
 * 'ftp'=>array(
 *         'class'=>'Sky\utils\Ftp',
 *         'host'=>'127.0.0.1',
 *         'port'=>21,
 *         'username'=>'yourusername',
 *         'password'=>'yourpassword',
 *         'ssl'=>false,
 *         'timeout'=>90,
 *         'autoConnect'=>true,
 *   ),...
 * )
 * See the following code example:
 *
 * $ftp = Sky::$app->ftp;
 * $ftp->put('remote.txt', 'D:\local.txt');
 * $ftp->rmdir('exampledir');
 * $ftp->chdir('aaa');
 * $ftp->currentDir();
 * $ftp->delete('remote.txt');
 * @author		Miles <cuiming2355_cn@hotmail.com>
 * modified by jiangyumeng
 * 
 */
class Ftp extends \Sky\base\Component
{
    /**
     * @var string the host for establishing FTP connection. Defaults to null.
     */
    public $host=null;

    /**
     * @var string the port for establishing FTP connection. Defaults to 21.
     */
    public $port = 21;

    /**
	 * @var string the username for establishing FTP connection. Defaults to null.
	 */
    public $username = null;

    /**
	 * @var string the password for establishing FTP connection. Defaults to null.
	 */
    public $password = null;

    /**
     * @var boolean
     */
    public $ssl = false;

    /**
     * @var string the timeout for establishing FTP connection. Defaults to 90.
     */
    public $timeout = 90;

    /**
	 * @var boolean whether the ftp connection should be automatically established
	 * the component is being initialized. Defaults to false. Note, this property is only
	 * effective when the EFtpComponent object is used as an application component.
	 */
	public $autoConnect = true;

    private $_active = false;
	private $_errors = null;
	private $_connection = null;
	
	/**
	 * @param	varchar	$host
	 * @param	varchar	$username
	 * @param	varchar	$password
	 * @param	boolean	$ssl
	 * @param	integer	$port
	 * @param	integer	$timeout
	 */
	public function __construct($host=null, $username=null, $password=null, $ssl=false, $port=21, $timeout=90)
	{
		$this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->ssl = $ssl;
        $this->port = $port;
        $this->timeout = $timeout;
	}

    /**
	 * Initializes the component.
	 * This method is required by {@link IApplicationComponent} and is invoked by application
	 * when the EFtpComponent is used as an application component.
	 * If you override this method, make sure to call the parent implementation
	 * so that the component can be marked as initialized.
	 */
	public function init()
	{
		parent::init();
		if($this->autoConnect)
			$this->setActive(true);
	}

	/**
	 * @return boolean whether the FTP connection is established
	 */
	public function getActive()
	{
		return $this->_active;
	}

	/**
	 * Open or close the FTP connection.
	 * @param boolean whether to open or close FTP connection
	 * @throws \Exception if connection fails
	 */
	public function setActive($value)
	{
		if($value!=$this->_active)
		{
			if($value)
				$this->connect();
			else
				$this->close();
		}
	}

	/**
	 * Connect to FTP if it is currently not
	 * @throws \Exception if connection fails
	 */
    public function connect()
    {
        if($this->_connection === null){
            // Connect - SSL?
            $this->_connection	= $this->ssl ? ftp_ssl_connect($this->host, $this->port, $this->timeout) : ftp_connect($this->host, $this->port, $this->timeout);

            // Check connection
            if(!$this->_connection)
            	throw new \Exception('FTP Library Error: Connection failed!');
            
            // Connection anonymous?
            if(!empty($this->username) AND !empty($this->password))
            {
                $login_result = ftp_login($this->_connection, $this->username, $this->password);
            } else {
                $login_result = true;
            }

            // Check login
            if((empty($this->username) AND empty($this->password)) AND !$login_result)
                throw new \Exception('FTP Library Error: Login failed!');

            $this->_active=true;
        }
    }

    /**
	 * Closes the current FTP connection.
	 *
	 * @return	boolean
	 */
	public function close()
	{
        if ($this->getActive()){
            // Close the connection
            if(ftp_close($this->_connection))
            {
                return true;
            } else {
                return false;
            }

            $this->_active = false;
            $this->_connection = null;
            $this->_errors = null;
        }
//         else{
//             throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
//         }
	}
	
	/**
	 * Passed an array of constants => values they will be set as FTP options.
	 * 
	 * @param	array	$config
	 * @return	object (chainable)
	 */
	public function setOptions($config)
	{
        if($this->getActive()){
            if(!is_array($config))
                throw new \Exception('EFtpComponent Error: The config parameter must be passed an array!');

            // Loop through configuration array
            foreach($config as $key => $value)
            {
                // Set the options and test to see if they did so successfully - throw an exception if it failed
                if(!ftp_set_option($this->_connection, $key, $value))
                    throw new \Exception('EFtpComponent Error: The system failed to set the FTP option: "'.$key.'" with the value: "'.$value.'"');
            }

            return $this;
        }
        else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }
	}
	
	/**
	 * Execute a remote command on the FTP server.
	 * 
	 * @see		http://us2.php.net/manual/en/function.ftp-exec.php
	 * @param	string remote command
	 * @return	boolean
	 */
	public function execute($command)
	{
        if($this->getActive()){
            // Execute command
            if(ftp_exec($this->_connection, $command))
            {
                return true;
            } else {
                return false;
            }
        }
        else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }
	}
	
	/**
	 * Download a file from FTP server.
	 * 
	 * @param	string local file
	 * @param	string remote file The remote file path.
	 * @param	const  mode The transfer mode. Must be either <strong>FTP_ASCII</strong> or <strong>FTP_BINARY</strong>.
	 * @param boolean $asynchronous  Flag indicating if file transfert should block php application or not.
	 * @return	boolean
	 */
	public function get($remote, $local=null,$mode=FTP_BINARY,$asynchronous = false)
	{
        if($this->getActive()){
        	if (!isset($local) || $local == null || !is_string($local) || trim($local) == '') {
        		$local = getcwd() . DIRECTORY_SEPARATOR . basename($remote);
        	}
            // Get the requested file
            if($asynchronous===false){
            	if(ftp_get($this->_connection, $local, $remote, $mode))
            	{
            		// If successful, return the path to the downloaded file...
            		return $remote;
            	} else {
            		return false;
            	}
            }else{
            	$ret = ftp_nb_get($this->_connection, $local, $remote, $mode);
            	
            	while ($ret == FTP_MOREDATA){
            		// continue downloading
            		$ret = ftp_nb_continue($this->_connection);
            	}
            	if ($ret == FTP_FAILED){
            		return false;
            	} else{
            		return $remote;
            	}
            }
        }
        else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }
	}
	
	/**
	 * Upload a file to the FTP server.
	 *
	 * @param	string local file
	 * @param	string remote file
	 * @param	const  mode
	 * @return	boolean
	 */
	public function put($local, $remote=null,$mode=FTP_BINARY,$asynchronous = false)
	{
        if($this->getActive()){
        	if (!isset($remote) || $remote == null || !is_string($remote) || trim($remote) == '') {
        		$remote = basename($local);       	
        		try {
        			$remote = $this->currentDir().'/'.$remote;
        		}catch(\Exception $e) {
        		}
        	}
        	
        	if($asynchronous===false){
        		// Upload the local file to the remote location specified
        		if(ftp_put($this->_connection, $remote, $local, $mode))
        		{
        			return true;
        		} else {
        			return false;
        		}
        	}else{
        		$ret = ftp_nb_put($this->_connection, $remote, $local, $mode);
        		
        		while($ret == FTP_MOREDATA){
        			$ret = ftp_nb_continue($this->_connection);
        		}
        		
        		if($ret !== FTP_FINISHED){
					return false;
        		}else{
        			return true;
        		}
        	}
        }
        else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }
	}
	
	/**
	 * 上传文件夹
	 * @param string $local 本地目录
	 * @param string $remote 远程目录，必须要存在
	 * @param boolean $overwrite 是否覆盖源文件
	 * @param string $mode
	 * @throws \Exception
	 * @return boolean
	 */
	public function putAll($local, $remote=null,$overwrite=true,$mode=FTP_BINARY){
		if($this->getActive()){
			if (!isset($remote) || $remote == null || !is_string($remote) || trim($remote) == '') {
				$remote = basename($remote);
				try {
					$remote = $this->currentDir().'/'.$remote;
				}catch(\Exception $e) {
				}
			}
			$this->chdir($remote);
			$dir= @opendir($local);
			$listArray=$this->listFiles();

			while (($name = readdir($dir)) !== false){
				if ($name!=='.' && $name!=='..') {
					$file=$local.DIRECTORY_SEPARATOR.$name;
				
					if (is_file($file)) {
						if (is_array($listArray) && in_array($name, $listArray)) {
							if ($overwrite) {
								$this->delete($name);
								$this->put($file,$name);
							}
						}else{
							$this->put($file,$name);
						}
					}elseif (is_dir($file)) {
						if (is_array($listArray) && in_array($name, $listArray)) {
							if ($overwrite) {
								$this->putAll($file,$name);
							}else 
								$this->putAll($file,$name,false);
						}else{
							$this->mkdir($name);
							$this->putAll($file,$name,$overwrite);
						}
					}
				}
			}
			$this->chdir('../');
			closedir($dir);
			return true;
		}else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }
	}
	
	/**
	 *  返回当前 FTP 被动模式是否打开
	 * @param boolean $pasv 如果参数为 TRUE，打开被动模式传输 (PASV MODE) ，否则则关闭被动传输模式。
	 * @throws \Exception
	 * @return boolean 成功时返回 TRUE， 或者在失败时返回 FALSE。
	 */
	public function pasv($pasv){
		if ($this->getActive()) {
			return ftp_pasv($this->_connection, $pasv);
		}else 
			throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
	}
	
	/**
	 * Rename executes a rename command on the remote FTP server.
	 *
	 * @param	string old filename
	 * @param	string new filename
	 * @return	boolean
	 */
	public function rename($old, $new)
	{
        if($this->getActive()){
            // Rename the file
            if(ftp_rename($this->_connection, $old, $new))
            {
                return true;
            } else {
                return false;
            }
        }
        else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }
	}
	
	/**
	 * Rmdir executes an rmdir (remove directory) command on the remote FTP server.
	 *
	 * @param	string remote directory
	 * @return	boolean
	 */
	public function rmdir($dir)
	{
        if($this->getActive()){
            // Remove the directory
            if(ftp_rmdir($this->_connection, $dir))
            {
                return true;
            } else {
                return false;
            }
        }
        else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }
	}

    /**
	 * Mkdir executes an mkdir (create directory) command on the remote FTP server.
	 *
	 * @param	string remote directory
	 * @return	boolean
	 */
    public function mkdir($dir)
    {
        if($this->getActive()){
            // create directory
            if(ftp_mkdir($this->_connection, $dir))
            {
                return true;
            } else {
                return false;
            }
        }
        else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }
    }

    /**
	 * Returns the last modified time of the given file
     * Note: Not all servers support this feature!
     * Note: mdtm method does not work with directories.
	 *
	 * @param	string remote file
	 * @return	mixed Returns the last modified time as a Unix timestamp on success, or false on error.
	 */
    public function mdtm($file)
    {
        if($this->getActive()){
            // get the last modified time
            $buff = ftp_mdtm($this->_connection, $file);
            if($buff != -1)
            {
                return $buff;
            } else {
                return false;
            }
        }
        else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }
    }

    /**
	 * Returns the size of the given file
     * Note: Not all servers support this feature!
	 *
	 * @param	string remote file
	 * @return	mixed Returns the file size on success, or false on error.
	 */
    public function size($file)
    {
        if($this->getActive()){
            // get the size of $file
            $buff = ftp_size($this->_connection, $file);
            if($buff != -1)
            {
                return $buff;
            } else {
                return false;
            }
        }
        else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }
    }
	
	/**
	 * Remove executes a delete command on the remote FTP server.
	 *
	 * @param	string remote file
	 * @return	boolean
	 */
	public function delete($file)
	{
        if($this->getActive()){
            // Delete the specified file
            if(ftp_delete($this->_connection, $file))
            {
                return true;
            } else {
                return false;
            }
        }
        else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }
	}
	
	/**
	 * Change the current working directory on the remote FTP server.
	 *
	 * @param	string remote directory
	 * @return	boolean
	 */
	public function chdir($dir)
	{
        if($this->getActive()){
            // Change directory
            if(ftp_chdir($this->_connection, $dir))
            {
                return true;
            } else {
                return false;
            }
        }
        else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }
	}
	
	/**
	 * Changes to the parent directory on the remote FTP server.
	 *
	 * @return	boolean
	 */
	public function parentDir()
	{
        if($this->getActive()){
            // Move up!
            if(ftp_cdup($this->_connection))
            {
                return true;
            } else {
                return false;
            }
        }
        else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }
	}
	
	/**
	 * Returns the name of the current working directory.
	 *
	 * @return	string The current directory name.
	 */
	public function currentDir()
	{
        if($this->getActive()){
            return ftp_pwd($this->_connection);
        }
        else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }
	}
	
	/**
	 * Permissions executes a chmod command on the remote FTP server.
	 *
	 * @param	string remote file
	 * @param	mixed  mode The new permissions, given as an <strong>octal</strong> value.
	 * @return	boolean 
	 */
	public function chmod($file, $mode)
	{
        if($this->getActive()){
        	if (substr($mode, 0, 1) != '0') {
        		$mode = octdec ( str_pad ( $mode, 4, '0', STR_PAD_LEFT ) );
        		$mode = (int) $mode;
        	}
            // Change the desired file's permissions
            if(ftp_chmod($this->_connection, $mode, $file)){
                return true;
            }else{
                return false;
            }
        }
        else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }
	}
	
	/**
	 * ListFiles executes a nlist command on the remote FTP server, returns an array of file names, false on failure.
	 *
	 * @param	string $directory remote directory
	 * @param boolean $full          List full dir description.
	 * @param boolean $recursive     Recursively list folder content
	 * @return	mixed 如果成功则返回给定目录下的文件名组成的数组，否则返回 FALSE。
	 */
	public function listFiles($directory='.', $full = false, $recursive = false)
	{
        if($this->getActive()){
        	if($full){
        		return ftp_rawlist($this->_connection, $directory, $recursive);
        	}else{
        		$opts = $recursive ? '-R ' : '';
        		return ftp_nlist($this->_connection, $opts.$directory);
        	}
            
        }
        else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }
	}
	
	/**
	 * 判断目录是否存在
	 * @param string $dir
	 * @return boolean
	 */
	public function isDir($dir){
		if ($this->getActive()) {
				// get current directory
			$original_directory = ftp_pwd($this->_connection);
			// test if you can change directory to $dir
			// suppress errors in case $dir is not a file or not a directory
			if ( @ftp_chdir( $this->_connection, $dir ) ) {
				// If it is a directory, then change the directory back to the original directory
				ftp_chdir( $this->_connection, $original_directory );
				return true;
			} else {
				return false;
			}
		}else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }

	}
	
	/**
	 * 递归创建目录
	 * @param string $dir
	 * @return boolean|string
	 */
	public function mkrdir($dir){
		if ($this->getActive()) {
			// if directory already exists or can be immediately created return true
			if ($this->isDir($dir) || @ftp_mkdir($this->_connection, $dir))
				return true;
			// otherwise recursively try to make the directory
			if (!$this->mkrdir(dirname($dir)))
				return false;
			// final step to create the directory
			return ftp_mkdir($this->_connection, $dir);
		}else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }

	}
	
	/**
	 * 递归删除目录
	 * @param string $dir 要删除的目录
	 * @throws \Exception
	 */
	public function rdelete($dir){
		if ($this->getActive()) {
			ftp_chdir($this->_connection, $dir);
			$files=$this->listFiles('.',true);
			foreach ($files as $file){
				$filename=substr($file, strrpos($file, ' ')+1);
				if ($file[0]==='d') {
					$this->rdelete($filename);
				}else{
					ftp_delete($this->_connection, $filename);
				}
			}
			ftp_cdup($this->_connection);
			ftp_rmdir($this->_connection, $dir);

			return true;
		}else{
            throw new \Exception('EFtpComponent is inactive and cannot perform any FTP operations.');
        }
	}
	
	
	/**
	 * Close the FTP connection if the object is destroyed.
	 *
	 * @return	boolean
	 */
	public function __destruct()
	{
		return $this->close();
	}
}