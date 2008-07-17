<?php

/**
 * Nette Framework
 *
 * Copyright (c) 2004, 2008 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://nettephp.com
 *
 * @copyright  Copyright (c) 2004, 2008 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @category   Nette
 * @package    Nette::Web
 * @version    $Id$
 */

/*namespace Nette::Web;*/


require_once dirname(__FILE__) . '/../Object.php';

require_once dirname(__FILE__) . '/../Web/IHttpResponse.php';



/**
 * HttpResponse class.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2008 David Grudl
 * @package    Nette::Web
 */
final class HttpResponse extends /*Nette::*/Object implements IHttpResponse
{
	/** cookie expirations */
	const PERMANENT = 2116333333; // 23.1.2037
	const WINDOW    = 0;   // end of session, when the browser closes

	/** @var bool */
	private static $xhtml = TRUE;

	/** @var string The domain in which the cookie will be available */
	public $cookieDomain = '';

	/** @var string The path in which the cookie will be available */
	public $cookiePath = '';

	/** @var string The path in which the cookie will be available */
	public $cookieSecure = FALSE;

	private $code = self::S200_OK;



	/**
	 * Sets HTTP response code.
	 * @param  int
	 * @return void
	 * @thorws ::InvalidArgumentException, ::InvalidStateException
	 */
	public function setCode($code)
	{
		$code = (int) $code;

		static $allowed = array(
			200=>1, 201=>1, 202=>1, 203=>1, 204=>1, 205=>1, 206=>1,
			300=>1, 301=>1, 302=>1, 303=>1, 304=>1, 307=>1,
			400=>1, 401=>1, 403=>1, 404=>1, 406=>1, 408=>1, 410=>1, 412=>1, 415=>1, 416=>1,
			500=>1, 501=>1, 503=>1, 505=>1
		);

		if (!isset($allowed[$code])) {
			throw new /*::*/InvalidArgumentException("Bad HTTP response '$code'.");
		}

		if (headers_sent($file, $line)) {
			throw new /*::*/InvalidStateException("Cannot modify header information - headers already sent (output started at $file:$line).");
		}

		$this->code = $code;
		header('HTTP/1.1 ' . $code, TRUE, $code);
	}



	/**
	 * Returns HTTP response code.
	 * @return int
	 */
	public function getCode()
	{
		return $this->code;
	}



	/**
	 * Sends a raw HTTP header.
	 * @param  string  header
	 * @param  bool    replace?
	 * @return bool
	 */
	public function setHeader($header, $replace = FALSE)
	{
		if (headers_sent()) {
			//TODO: return FALSE or throw exception?
			//throw new /*::*/InvalidStateException("Cannot modify header information - headers already sent.");
			return FALSE;
		}
		// prevent header injection
		$header = str_replace(array("\n", "\r"), '', $header);
		header($header, $replace);
		return TRUE;
	}



	/**
	 * Returns a list of headers to sent.
	 * @return array
	 */
	public function getHeaders()
	{
		return headers_list();
	}



	/**
	 * Sends a Content-type HTTP header.
	 * @param  string  mime-type
	 * @param  string  charset
	 * @return void
	 */
	public function setContentType($type = 'text/html', $charset = 'ISO-8859-1')
	{
		$this->setHeader('Content-type: ' . $type . ($charset ? '; charset=' . $charset : ''), TRUE);
	}



	/**
	 * Returns HTTP valid date format.
	 * @param  int  timestamp
	 * @return string
	 */
	public static function date($time = NULL)
	{
		return gmdate('D, d M Y H:i:s \G\M\T', $time === NULL ? time() : $time);
	}



	public function expire($expire)
	{
		if ($expire > 0) {
			if ($expire > self::EXPIRATION_DELTA_LIMIT) {
				$expire -= time();
			}
			header('Cache-Control: max-age=' . (int) $expire . ',must-revalidate', TRUE);
			header('Expires: ' . self::date(time() + $expire), TRUE);
		} else {
			// no cache
			header('Cache-Control: s-maxage=0, max-age=0, must-revalidate', TRUE);
			header('Expires: Mon, 23 Jan 1978 10:00:00 GMT', TRUE);
		}
	}


	/*
	public function enableCompression()
	{
		$ok = ob_gzhandler('', PHP_OUTPUT_HANDLER_START);
		if ($ok === FALSE) {
			return FALSE; // not allowed
		}

		if (function_exists('ini_set')) {
			ini_set('zlib.output_compression', 'Off');
			ini_set('zlib.output_compression_level', '6');
		}
		ob_start('ob_gzhandler');
		return TRUE;
	}
	*/



	/**
	 * Sends garbage for IE 6.
	 */
	public function fixIE()
	{
		$ie = isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.0');
		$codes = array(400, 403, 404, 405, 406, 408, 409, 410, 500, 501, 505);
		if ($ie && in_array($this->code, $codes)) {
			$s = " \t\r\n";
			for ($i = 2e3; $i; $i--) echo $s{rand(0, 3)};
		}
	}



	/**
	 * Defines a new cookie.
	 * @param  string name of the cookie.
	 * @param  string value
	 * @param  int expiration as unix timestamp
	 * @param  string
	 * @param  string
	 * @param  bool
	 * @return void
	 */
	public function setCookie($name, $value, $expire, $path = NULL, $domain = NULL, $secure = NULL)
	{
		if ($expire > 0 && $expire <= self::EXPIRATION_DELTA_LIMIT) {
			$expire += time();
		}
		setcookie(
			$name,
			$value,
			$expire,
			$path === NULL ? $this->cookiePath : (string) $path,
			$domain === NULL ? $this->cookieDomain : (string) $domain, //  . '; httponly'
			$secure === NULL ? $this->cookieSecure : (string) $secure,
			TRUE // added in PHP 5.2.0.
		);
	}



	/**
	 * Deletes a cookie.
	 * @param  string name of the cookie.
	 * @param  string
	 * @param  string
	 * @param  bool
	 * @return void
	 */
	public function deleteCookie($name, $path = NULL, $domain = NULL, $secure = NULL)
	{
		setcookie(
			$name,
			FALSE,
			254400000,
			$path === NULL ? $this->cookiePath : (string) $path,
			$domain === NULL ? $this->cookieDomain : (string) $domain, //  . '; httponly'
			$secure === NULL ? $this->cookieSecure : (string) $secure,
			TRUE // added in PHP 5.2.0.
		);
	}

}
