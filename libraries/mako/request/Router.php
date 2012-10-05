<?php

namespace mako\request;

use \mako\Config;
use \mako\Request;
use \mako\Package;
use \mako\ClassLoader;

/**
 * Request router.
 *
 * @author     Frederic G. Østby
 * @copyright  (c) 2008-2012 Frederic G. Østby
 * @license    http://www.makoframework.com/license
 */

class Router
{
	//---------------------------------------------
	// Class properties
	//---------------------------------------------

	/**
	 * Request that instantiated the router.
	 * 
	 * @var mako\Request
	 */

	protected $request;

	/**
	 * Request route.
	 * 
	 * @var string
	 */

	protected $route;

	/**
	 * Package name
	 * 
	 * @var string
	 */

	protected $package;

	/**
	 * Route configuration.
	 * 
	 * @var array
	 */

	protected $config;

	/**
	 * Base routes of the routable packages.
	 *
	 * @var arary
	 */

	protected static $packageBaseRoutes;

	//---------------------------------------------
	// Class constructor, destructor etc ...
	//---------------------------------------------

	/**
	 * Constructor.
	 * 
	 * @access  public
	 * @param   mako\Request  $request  Request that instantiated the router
	 * @param   string        $route    Request route
	 * @param   string        $package  (optional) Package name
	 */

	public function __construct(Request $request, $route, $package = null)
	{
		$this->request = $request;
		$this->route   = $route;
		$this->package = $package;
		$this->config  = Config::get(($package !== null ? $package . '::' : '') . 'routes');

		if($this->request->isMain() && $package === null)
		{
			static::$packageBaseRoutes = array_flip($this->config['packages']);
		}

	}

	//---------------------------------------------
	// Class methods
	//---------------------------------------------

	/**
	 * Returns the base route for a package.
	 * 
	 * @access  public
	 * @param   string  $package  Package name
	 * @return  string
	 */

	public static function packageBaseRoute($package)
	{
		return isset(static::$packageBaseRoutes[$package]) ? static::$packageBaseRoutes[$package] : '';
	}

	/**
	 * Routes the request. Returns TRUE if a controller is found and FALSE if not.
	 * 
	 * @access  public
	 * @return  boolean
	 */

	public function route()
	{
		if($this->route === '')
		{
			$route = trim($this->config['default_route'], '/');
		}
		else
		{
			$route = trim($this->route, '/');
		}

		// Remap custom routes

		if(!empty($this->config['custom_routes']))
		{
			foreach($this->config['custom_routes'] as $pattern => $realRoute)
			{		
				if(preg_match('#^' . $pattern . '$#iu', $route) === 1)
				{
					if(strpos($realRoute, '$') !== false)
					{
						$realRoute = preg_replace('#^' . $pattern . '$#iu', $realRoute, $route);
					}

					$route = trim($realRoute, '/');

					break;
				}
			}
		}

		// Is the route pointing to a package?

		if($this->package === null)
		{
			if(!empty($this->config['packages']))
			{
				foreach($this->config['packages'] as $base => $package)
				{
					if(strpos($route, $base) === 0)
					{
						ClassLoader::registerNamespace($package . '\controllers', MAKO_PACKAGES_PATH . '/' . $package . '/controllers');
						
						Package::init($package);

						$router = new static($this->request, mb_substr($route, mb_strlen($base)), $package);

						return $router->route();
					}
				}
			}
		}

		// Route the request

		$namespace        = $this->package === null ? '\app\controllers\\' : '\\' . $this->package . '\controllers\\';
		$controller       = '';
		$action           = '';
		$actionParameters = array();
		$controllerPath   = $this->package === null ? MAKO_APPLICATION_PATH . '/controllers/' : MAKO_PACKAGES_PATH . '/' . $this->package . '/controllers/';

		// Get the URL segments

		$segments = explode('/', $route, 100);

		foreach($segments as $segment)
		{
			$path = $controllerPath . $segment;

			if(is_dir($path))
			{
				// Just a directory - Jump to next iteration

				$controllerPath .= $segment . '/';
				$namespace      .= $segment . '\\';

				array_shift($segments);

				continue;
			}
			else if(is_file($path . '.php'))
			{
				// We have found our controller - Exit loop

				$controller = $segment;

				array_shift($segments);

				break;
			}
			else
			{
				// No directory or controller - Stop routing

				return false;
			}
		}
		
		if(empty($controller))
		{
			$controller = 'index'; // default controller
		}

		// Get the action we want to execute

		$action = array_shift($segments);

		if($action === null)
		{
			$action = 'index';
		}

		// Remaining segments are passed as parameters to the action

		$actionParameters = $segments;

		// Check if file exists

		if(!file_exists($controllerPath . $controller . '.php'))
		{
			return false;
		}
		else
		{
			$this->request->setNamespace($namespace);
			$this->request->setController($controller);
			$this->request->setAction($action);
			$this->request->setActionParameters($actionParameters);

			return true;
		}
	}
}

/** -------------------- End of file --------------------**/