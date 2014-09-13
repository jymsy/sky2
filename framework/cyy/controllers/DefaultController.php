<?php
namespace Sky\cyy\controllers;

use Sky\base\Controller;
class DefaultController extends Controller{
	public function actionIndex(){
		$this->render('index.php');
	}
}