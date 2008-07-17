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



/**
 * IHttpRequest provides access scheme for request sent via HTTP.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2008 David Grudl
 * @package    Nette::Web
 */
interface IHttpRequest
{
	/**
	 * Returns HTTP request method (GET, POST, HEAD, PUT, ...).
	 * @return string
	 */
	function getMethod();

	/**
	 * Returns the full URL.
	 * @return UriScript
	 */
	function getUri();

	/**
	 * Returns all variables provided to the script via URL query ($_GET).
	 * @return Nette::Collections::Hashtable
	 */
	function getQuery();

	/**
	 * Returns all variables provided to the script via POST method ($_POST).
	 * @return Nette::Collections::Hashtable
	 */
	function getPost();

	/**
	 * Returns all uploaded files.
	 * @return Nette::Collections::Hashtable
	 */
	function getFiles();

	/**
	 * Returns all HTTP cookies.
	 * @return Nette::Collections::Hashtable
	 */
	function getCookies();

	/**
	 * Returns all HTTP headers.
	 * @return array
	 */
	function getHeaders();

	/**
	 * Is the request is sent via secure channel (https).
	 * @return boolean
	 */
	function isSecured();

	/**
	 * Is Ajax request?
	 * @return boolean
	 */
	function isAjax();
}
