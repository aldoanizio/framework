<?php

/**
 * @copyright  Frederic G. Østby
 * @license    http://www.makoframework.com/license
 */

namespace mako\security;

use \Closure;

use \mako\security\Comparer;
use \mako\utility\Str;

/**
 * Secure password hashing and validation.
 *
 * @author  Frederic G. Østby
 */

class Password
{
	/**
	 * Default computing cost.
	 *
	 * @var int
	 */

	const COST = 10;

	/**
	 * Protected constructor since this is a static class.
	 *
	 * @access  protected
	 */

	protected function __construct()
	{
		// Nothing here
	}

	/**
	 * Checks if a hash is generated using something other than bcrypt.
	 *
	 * @access  public
	 * @param   string   $hash  Hash to check
	 * @return  boolean
	 */

	public static function isLegacyHash($hash)
	{
		return stripos($hash, '$2y$') !== 0;
	}

	/**
	 * Returns a bcrypt hash of the password.
	 *
	 * @access  public
	 * @param   string  $password  Password
	 * @param   int     $cost      (optional) Computing cost
	 * @return  string
	 */

	public static function hash($password, $cost = Password::COST)
	{
		// Set cost

		if($cost < 4 || $cost > 31)
		{
			$cost = static::COST;
		}

		$cost = str_pad($cost, 2, '0', STR_PAD_LEFT);

		// Generate random salt

		if(function_exists('mcrypt_create_iv'))
		{
			$salt = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
		}
		elseif(function_exists('openssl_random_pseudo_bytes'))
		{
			$salt = openssl_random_pseudo_bytes(16);
		}
		else
		{
			$salt = Str::random();
		}

		$salt = substr(str_replace('+', '.', base64_encode($salt)), 0, 22);

		// Return hash

		return crypt($password, '$2y$' . $cost . '$' . $salt);
	}

	/**
	 * Validates a password hash.
	 *
	 * @access  public
	 * @param   string    $password     Password
	 * @param   string    $hash         Password hash
	 * @param   \Closure  $legacyCheck  (optional) Legacy check
	 * @return  boolean
	 */

	public static function validate($password, $hash, Closure $legacyCheck = null)
	{
		if($legacyCheck !== null && static::isLegacyHash($hash))
		{
			return $legacyCheck($password, $hash);
		}

		return Comparer::compare(crypt($password, $hash), $hash);
	}
}