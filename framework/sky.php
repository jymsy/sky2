<?php
namespace Sky;
/**
 * Sky bootstrap file.
 */
require(__DIR__.'/SkyBase.php');
/**
 * sky是一个服务于整个框架辅助类。 
 * 它封装了SkyBase由SkyBase提供具体功能实现。 
 * 你可以通过改写它定制SkyBase的一些功能。
 * @author Jiangyumeng
 *
 */
class Sky extends \Sky\SkyBase
{
    /**
     * @var \base\components\TianciApplication
     */
    public static $app;
}
spl_autoload_register(array('\Sky\Sky','autoload'));
require(SKY_PATH.'/base/Interfaces.php');