<?php
namespace Sky\web\widgets;

use Sky\Sky;
/**
 * @author Jiangyumeng
 */
class ContentDecorator extends Widget{
	/**
	 * @var string the view file that will be used to decorate the content enclosed by this widget.
	 * This can be specified as either the view file path or path alias.
	 */
	public $view;
	/**
	 * @var array the parameters (name => value) to be extracted and made available in the decorative view.
	 */
	public $data = array();
	
	public function init()
	{
		if ($this->view === null) {
			throw new \Exception('ContentDecorator::view must be set.');
		}
		ob_start();
		ob_implicit_flush(false);
	}
	
	public function run()
	{
		$owner=$this->getOwner();
		if($this->view===null)
			$viewFile=Sky::$app->getController()->getLayoutFile(null);
		else
			$viewFile=$owner->getViewFile($this->view);
		// render under the existing context
		if($viewFile!==false)
		{
			$params = $this->data;
			$params['content'] = ob_get_clean();
			echo $owner->renderFile($viewFile, $params);
		}else 
			throw new \Exception("viewFile $viewFile doesn't exist.");
	}
}