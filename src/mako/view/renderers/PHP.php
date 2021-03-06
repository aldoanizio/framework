<?php

/**
 * @copyright  Frederic G. Østby
 * @license    http://www.makoframework.com/license
 */

namespace mako\view\renderers;

/**
 * Plain PHP view renderer.
 *
 * @author  Frederic G. Østby
 */

class PHP extends \mako\view\renderers\Renderer implements \mako\view\renderers\RendererInterface
{
	/**
	 * Returns the rendered view.
	 * 
	 * @access  public
	 * @return  string
	 */

	public function render()
	{
		extract($this->variables, EXTR_REFS);
		
		ob_start();

		include($this->view);

		return ob_get_clean();
	}
}