<?php

/**
 * @copyright  Frederic G. Østby
 * @license    http://www.makoframework.com/license
 */

namespace mako\cache\stores;

/**
 * WinCache store.
 *
 * @author  Frederic G. Østby
 */

class WinCache implements \mako\cache\stores\StoreInterface
{
	/**
	 * Store data in the cache.
	 *
	 * @access  public
	 * @param   string   $key    Cache key
	 * @param   mixed    $data   The data to store
	 * @param   int      $ttl    (optional) Time to live
	 * @return  boolean
	 */

	public function put($key, $data, $ttl = 0)
	{
		return wincache_ucache_set($key, $data, $ttl);
	}

	/**
	 * Returns TRUE if the cache key exists and FALSE if not.
	 * 
	 * @access  public
	 * @param   string   $key  Cache key
	 * @return  boolean
	 */

	public function has($key)
	{
		return wincache_ucache_exists($key);
	}

	/**
	 * Fetch data from the cache.
	 *
	 * @access  public
	 * @param   string  $key  Cache key
	 * @return  mixed
	 */

	public function get($key)
	{
		$cache = wincache_ucache_get($key, $success);
		
		if($success === true)
		{
			return $cache;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Delete data from the cache.
	 *
	 * @access  public
	 * @param   string   $key  Cache key
	 * @return  boolean
	 */

	public function remove($key)
	{
		return wincache_ucache_delete($key);
	}

	/**
	 * Clears the user cache.
	 *
	 * @access  public
	 * @return  boolean
	 */

	public function clear()
	{
		return wincache_ucache_clear();
	}
}