<?php
namespace Sky\cyy;

use Sky\base\WebModule;
use Sky\Sky;
class CyyModule extends WebModule{
	/**
	 * Initializes the gii module.
	 */
	public function init(){
		
		Sky::$app->setComponents(array(
			'errorHandler'=>array(
				'class'=>'Sky\base\ErrorHandler',
				'errorAction'=>'cyy/default/error',
			),
		),false);
	}
	
	public function getName(){
		return 'Sky\cyy';
	}
}