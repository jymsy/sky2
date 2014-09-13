<?php
namespace Sky\web\filters;

use Sky\base\Component;
class Filter extends Component{
	
	/**
	 * @param FilterChain $filterChain
	 */
	public function filter($filterChain)
	{
		$filterChain->run();
	}
}