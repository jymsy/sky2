<?php
namespace Sky\base;

/**
 * 这个文件包含了skyframework的核心接口
 *
 * @author Jiangyumeng
 */

interface ICache{
	public function get($id);
	
	public function mget($ids);
	
	public function set($id,$value,$expire=0);
	
	public function add($id,$value,$expire=0);
	
	public function delete($id);
	
	public function flush();
}

interface IWidgetFactory{
	public function createWidget($owner,$className,$properties=array());
}

interface IResponseFormatter
{
	/**
	 * Formats the specified response.
	 * @param Response $response the response to be formatted.
	 */
	public function format($response);
}

interface IUserIdentity
{
	/**
	 * Authenticates the user.
	 * The information needed to authenticate the user
	 * are usually provided in the constructor.
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate();
	/**
	 * Returns a value indicating whether the identity is authenticated.
	 * @return boolean whether the identity is valid.
	*/
// 	public function getIsAuthenticated();
	/**
	 * Returns a value that uniquely represents the identity.
	 * @return mixed a value that uniquely represents the identity (e.g. primary key value).
	*/
	public function getId();
	/**
	 * Returns the display name for the identity (e.g. username).
	 * @return string the display name for the identity.
	*/
	public function getName();
// 	/**
// 	 * Returns the additional identity information that needs to be persistent during the user session.
// 	 * @return array additional identity information that needs to be persistent during the user session (excluding {@link id}).
// 	*/
// 	public function getPersistentStates();
}