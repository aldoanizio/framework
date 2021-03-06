<?php

/**
 * @copyright  Frederic G. Østby
 * @license    http://www.makoframework.com/license
 */

namespace mako\security\crypto\encrypters;

/**
 * OpenSSL encrypter.
 *
 * @author  Frederic G. Østby
 */

class OpenSSL extends \mako\security\crypto\encrypters\Encrypter implements \mako\security\crypto\encrypters\EncrypterInterface
{
	/**
	 * Key used to encrypt/decrypt string.
	 *
	 * @var string
	 */
	
	protected $key;
	
	/**
	 * The cipher method to use for encryption.
	 *
	 * @var string
	 */
	
	protected $cipher;

	/**
	 * Initialization vector size.
	 *
	 * @var string
	 */
	
	protected $ivSize;

	/**
	 * Constructor.
	 * 
	 * @access  public
	 * @param   string  $key     Encryption key
	 * @param   int     $cipher  (optional) Cipher
	 */

	public function __construct($key, $cipher = null)
	{
		$this->key = $key;

		$this->cipher = $cipher ?: 'AES-256-OFB';

		$this->ivSize = openssl_cipher_iv_length($this->cipher);
	}

	/**
	 * Encrypts string.
	 *
	 * @access  public
	 * @param   string  $string  String to encrypt
	 * @return  string
	 */
	
	public function encrypt($string)
	{
		$iv = openssl_random_pseudo_bytes($this->ivSize);

		$key = $this->deriveKey($this->key, $iv, 32);

		return base64_encode($iv . openssl_encrypt($string, $this->cipher, $key, 0, $iv));
	}

	/**
	 * Decrypts string.
	 *
	 * @access  public
	 * @param   string          $string  String to decrypt
	 * @return  string|boolean
	 */
	
	public function decrypt($string)
	{
		$string = base64_decode($string, true);
		
		if($string === false)
		{
			return false;
		}

		$iv = substr($string, 0, $this->ivSize);
		
		$string = substr($string, $this->ivSize);

		$key = $this->deriveKey($this->key, $iv, 32);

		return openssl_decrypt($string, $this->cipher, $key, 0, $iv);
	}
}