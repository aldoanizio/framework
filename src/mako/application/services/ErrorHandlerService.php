<?php

/**
 * @copyright  Frederic G. Østby
 * @license    http://www.makoframework.com/license
 */

namespace mako\application\services;

use \mako\error\ErrorHandler;

/**
 * Error handler service.
 *
 * @author  Frederic G. Østby
 */

class ErrorHandlerService extends \mako\application\services\Service
{
	/**
	 * Registers the service.
	 * 
	 * @access  public
	 */

	public function register()
	{
		$this->container->registerInstance(['mako\error\ErrorHandler', 'errorHandler'], new ErrorHandler());
	}
}