<?php
namespace Sky\web\auth;

use Sky\base\Component;
use Sky\base\IUserIdentity;

class UserIdentity extends Component implements IUserIdentity{
	
	const ERROR_NONE=0;
	const ERROR_USERNAME_INVALID=1;
	const ERROR_PASSWORD_INVALID=2;
	const ERROR_UNKNOWN_IDENTITY=100;
	
	public $errorCode=self::ERROR_UNKNOWN_IDENTITY;
	
	/**
	 * @var string username
	 */
	public $username;
	/**
	 * @var string password
	 */
	public $password;
	
	/**
	 * Constructor.
	 * @param string $username username
	 * @param string $password password
	 */
	public function __construct($username,$password)
	{
		$this->username=$username;
		$this->password=$password;
	}

	public function authenticate()
	{
		throw new \Exception(get_class($this).'::authenticate() must be implemented.');
	}
	
	public function getId()
	{
		
	}
	
	public function getName()
	{
		
	}
}