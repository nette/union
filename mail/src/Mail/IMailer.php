<?php

/**
 * Nette Framework
 *
 * Copyright (c) 2004, 2009 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://nettephp.com
 *
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @category   Nette
 * @package    Nette\Mail
 * @version    $Id$
 */

/*namespace Nette\Mail;*/



/**
 * Mailer interface.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Mail
 */
interface IMailer
{

	/**
	 * Sends e-mail.
	 * @param  Mail
	 * @return void
	 */
	function send(Mail $mail);

}
