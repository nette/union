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



require_once dirname(__FILE__) . '/../Mail/MailMimePart.php';



/**
 * Mail provides functionality to compose and send both text and MIME-compliant multipart e-mail messages.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Mail
 *
 * @property   string $from
 * @property   string $subject
 * @property   string $returnPath
 * @property   int $priority
 * @property   string $htmlBody
 */
class Mail extends MailMimePart
{
	/**#@+ Priority */
	const HIGH = 1;
	const NORMAL = 3;
	const LOW = 5;
	/**#@-*/

	/** @var IMailer */
	public static $defaultMailer = 'Nette\Mail\SendmailMailer';

	/** @var array */
	public static $defaultHeaders = array(
		'MIME-Version' => '1.0',
		'X-Mailer' => 'Nette Framework',
	);

	/** @var string */
	private $charset = 'UTF-8';

	/** @var array */
	private $attachments = array();

	/** @var array */
	private $inlines = array();

	/** @var mixed */
	private $html;

	/** @var string */
	private $basePath;



	public function __construct()
	{
		foreach (self::$defaultHeaders as $name => $value) {
			$this->setHeader($name, $value);
		}
		$this->setHeader('Date', date('r'));
	}



	/**
	 * Sets the sender of the message.
	 * @param  string  e-mail or format "John Doe" <doe@example.com>
	 * @param  string
	 * @return Mail  provides a fluent interface
	 */
	public function setFrom($email, $name = NULL)
	{
		$this->setHeader('From', $this->formatEmail($email, $name));
		return $this;
	}



	/**
	 * Returns the sender of the message.
	 * @return array
	 */
	public function getFrom()
	{
		return $this->getHeader('From');
	}



	/**
	 * Adds the reply-to address.
	 * @param  string  e-mail or format "John Doe" <doe@example.com>
	 * @param  string
	 * @return Mail  provides a fluent interface
	 */
	public function addReplyTo($email, $name = NULL)
	{
		$this->setHeader('Reply-To', $this->formatEmail($email, $name), TRUE);
		return $this;
	}



	/**
	 * Sets the subject of the message.
	 * @param  string
	 * @return Mail  provides a fluent interface
	 */
	public function setSubject($subject)
	{
		$this->setHeader('Subject', $subject);
		return $this;
	}



	/**
	 * Returns the subject of the message.
	 * @return string
	 */
	public function getSubject()
	{
		return $this->getHeader('Subject');
	}



	/**
	 * Adds email recipient.
	 * @param  string  e-mail or format "John Doe" <doe@example.com>
	 * @param  string
	 * @return Mail  provides a fluent interface
	 */
	public function addTo($email, $name = NULL) // addRecipient()
	{
		$this->setHeader('To', $this->formatEmail($email, $name), TRUE);
		return $this;
	}



	/**
	 * Adds carbon copy email recipient.
	 * @param  string  e-mail or format "John Doe" <doe@example.com>
	 * @param  string
	 * @return Mail  provides a fluent interface
	 */
	public function addCc($email, $name = NULL)
	{
		$this->setHeader('Cc', $this->formatEmail($email, $name), TRUE);
		return $this;
	}



	/**
	 * Adds blind carbon copy email recipient.
	 * @param  string  e-mail or format "John Doe" <doe@example.com>
	 * @param  string
	 * @return Mail  provides a fluent interface
	 */
	public function addBcc($email, $name = NULL)
	{
		$this->setHeader('Bcc', $this->formatEmail($email, $name), TRUE);
		return $this;
	}



	/**
	 * Formats recipient e-mail.
	 * @param  string
	 * @param  string
	 * @return array
	 */
	private function formatEmail($email, $name)
	{
		if (!$name && preg_match('#^(.+) +<(.*)>$#', $email, $matches)) {
			return array($matches[2] => $matches[1]);
		} else {
			return array($email => $name);
		}
	}



	/**
	 * Sets the Return-Path header of the message.
	 * @param  string  e-mail
	 * @return Mail  provides a fluent interface
	 */
	public function setReturnPath($email)
	{
		$this->setHeader('Return-Path', $email);
		return $this;
	}



	/**
	 * Returns the Return-Path header.
	 * @return string
	 */
	public function getReturnPath()
	{
		return $this->getHeader('From');
	}



	/**
	 * Sets email priority.
	 * @param  int
	 * @return Mail  provides a fluent interface
	 */
	public function setPriority($priority)
	{
		$this->setHeader('X-Priority', (int) $priority);
		return $this;
	}



	/**
	 * Returns email priority.
	 * @return int
	 */
	public function getPriority()
	{
		return $this->getHeader('X-Priority');
	}



	/**
	 * Sets HTML body.
	 * @param  string|Nette\Templates\ITemplate
	 * @param  mixed base-path or FALSE to disable parsing
	 * @return Mail  provides a fluent interface
	 */
	public function setHtmlBody($html, $basePath = NULL)
	{
		$this->html = $html;
		$this->basePath = $basePath;
		return $this;
	}



	/**
	 * Gets HTML body.
	 * @return mixed
	 */
	public function getHtmlBody()
	{
		return $this->html;
	}



	/**
	 * Adds embedded file.
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return MailMimePart
	 */
	public function addEmbeddedFile($file, $contentType = NULL, $encoding = self::ENCODING_BASE64)
	{
		$part = $this->createFilePart($file, $contentType, $encoding);
		$part->setHeader('Content-Disposition', 'inline; filename="' . basename($file) . '"');
		$part->setHeader('Content-ID', '<' . md5(uniqid('', TRUE)) . '>');
		return $this->inlines[$file] = $part;
	}



	/**
	 * Adds attachment.
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return MailMimePart
	 */
	public function addAttachment($file, $contentType = NULL, $encoding = self::ENCODING_BASE64)
	{
		$part = $this->createFilePart($file, $contentType, $encoding);
		$part->setHeader('Content-Disposition', 'attachment; filename="' . basename($file) . '"');
		return $this->attachments[] = $part;
	}



	/**
	 * Creates file MIME part.
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return MailMimePart
	 */
	public function createFilePart($file, $contentType, $encoding)
	{
		if (!is_file($file)) {
			throw new /*\*/FileNotFoundException("File '$file' not found.");
		}
		if (!$contentType) {
			$info = getimagesize($file);
			$contentType = $info ? image_type_to_mime_type($info[2]) : 'application/octet-stream';
		}
		$part = new MailMimePart;
		$part->setContentType($contentType);
		$part->setEncoding($encoding);
		$part->setBody(file_get_contents($file));
		return $part;
	}



	/********************* building and sending ****************d*g**/



	/**
	 * Sends e-mail.
	 * @param  IMailer
	 * @return bool
	 */
	public function send(IMailer $mailer = NULL)
	{
		if ($mailer === NULL) {
			/**/fixNamespace(self::$defaultMailer);/**/
			$mailer = is_object(self::$defaultMailer) ? self::$defaultMailer : new self::$defaultMailer;
		}
		return $mailer->send($this->build());
	}



	/**
	 * Builds e-mail.
	 * @return void
	 */
	protected function build()
	{
		$mail = clone $this;
		$hostname = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
		$mail->setHeader('Message-ID', '<' . md5(uniqid('', TRUE)) . "@$hostname>");

		$mail->buildHtml();
		$mail->buildText();

		$cursor = $mail;
		if ($mail->attachments) {
			$tmp = $cursor->setContentType('multipart/mixed');
			$cursor = $cursor->addPart();
			foreach ($mail->attachments as $value) {
				$tmp->addPart($value);
			}
		}

		if ($mail->html != NULL) { // intentionally ==
			$tmp = $cursor->setContentType('multipart/alternative');
			$cursor = $cursor->addPart();
			$alt = $tmp->addPart();
			if ($mail->inlines) {
				$tmp = $alt->setContentType('multipart/related');
				$alt = $alt->addPart();
				foreach ($mail->inlines as $name => $value) {
					$tmp->addPart($value);
				}
			}
			$alt->setContentType('text/html', $mail->charset)
				->setEncoding(preg_match('#[\x80-\xFF]#', $mail->html) ? self::ENCODING_8BIT : self::ENCODING_7BIT)
				->setBody($mail->html);
		}

		$text = $mail->getBody();
		$mail->setBody(NULL);
		$cursor->setContentType('text/plain', $mail->charset)
			->setEncoding(preg_match('#[\x80-\xFF]#', $text) ? self::ENCODING_8BIT : self::ENCODING_7BIT)
			->setBody($text);

		return $mail;
	}



	/**
	 * Builds HTML content.
	 * @return void
	 */
	protected function buildHtml()
	{
		if ($this->html instanceof /*Nette\Templates\*/Template) {
			$this->html->mail = $this;
			if ($this->basePath === NULL) {
				$this->basePath = dirname($this->html->getFile());
			}
			$this->html = $this->html->__toString(TRUE);
		}

		if ($this->basePath !== FALSE) {
			$cids = array();
			preg_match_all('#(src\s*=\s*|background\s*=\s*|url\()(["\'])(?![a-z]+:|[/\\#])(.+)\\2#i', $this->html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
			foreach (array_reverse($matches) as $m)	{
				$file = rtrim($this->basePath, '/\\') . '/' . $m[3][0];
				$cid = isset($cids[$file]) ? $cids[$file] : $cids[$file] = substr($this->addEmbeddedFile($file)->getHeader("Content-ID"), 1, -1);
				$this->html = substr_replace($this->html, "{$m[1][0]}{$m[2][0]}cid:$cid{$m[2][0]}", $m[0][1], strlen($m[0][0]));
			}
		}

		if (!$this->getSubject() && preg_match('#<title>(.+)</title>#i', $this->html, $matches)) {
			$this->setSubject(html_entity_decode($matches[1], ENT_QUOTES, $this->charset));
		}
	}



	/**
	 * Builds text content.
	 * @return void
	 */
	protected function buildText()
	{
		$text = $this->getBody();
		if ($text instanceof /*Nette\Templates\*/Template) {
			$text->mail = $this;
			$this->setBody($text->__toString(TRUE));

		} elseif ($text == NULL && $this->html != NULL) { // intentionally ==
			$text = preg_replace('#<(style|script|head).*</\\1>#Uis', '', $this->html);
			$text = preg_replace('#\s+#', ' ', $text);
			$text = preg_replace('#<(/?p|/?h\d|li|br|/tr)[ >]#i', "\n$0", $text);
			$text = html_entity_decode(strip_tags($text), ENT_QUOTES, $this->charset);
			$this->setBody(trim($text));
		}
	}

}
