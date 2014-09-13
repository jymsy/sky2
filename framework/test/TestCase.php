<?php
namespace Sky\test;
require_once('PHPUnit/Runner/Version.php');
// require_once('PHPUnit/Util/Filesystem.php'); // workaround for PHPUnit <= 3.6.11

spl_autoload_unregister(array('Sky\SkyBase','autoload'));
require_once('PHPUnit/Autoload.php');
spl_autoload_register(array('Sky\SkyBase','autoload')); // put sky's autoloader at the end

/**
 * TestCase 类是所有测试类的基类
 * @author Jiangyumeng
 *
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase{
	
}