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
 * @package    Nette\Application
 * @version    $Id$
 */

/*namespace Nette\Application;*/



require_once dirname(__FILE__) . '/../Application/AbortException.php';



/**
 * Abort presenter and redirects to new request.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2008 David Grudl
 * @package    Nette\Application
 */
class RedirectingException extends AbortException
{
	/** @var string */
	private $uri;



	public function __construct($uri, $code)
	{
		parent::__construct();
		$this->code = (int) $code;
		$this->uri = (string) $uri;
	}



	/**
	 * @return string
	 */
	final public function getUri()
	{
		return $this->uri;
	}

}
