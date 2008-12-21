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



require_once dirname(__FILE__) . '/../ComponentContainer.php';

require_once dirname(__FILE__) . '/../Application/ISignalReceiver.php';

require_once dirname(__FILE__) . '/../Application/IStatePersistent.php';



/**
 * PresenterComponent is the base class for all presenters components.
 *
 * Components are persistent objects located on a presenter. They have ability to own
 * other child components, and interact with user. Components have properties
 * for storing their status, and responds to user command.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2008 David Grudl
 * @package    Nette\Application
 */
abstract class PresenterComponent extends /*Nette\*/ComponentContainer implements ISignalReceiver, IStatePersistent
{
	/** @var array */
	protected $params = array();



	/**
	 */
	public function __construct(/*Nette\*/IComponentContainer $parent = NULL, $name = NULL)
	{
		$this->monitor('Nette\Application\Presenter');
		parent::__construct($parent, $name);
	}



	/**
	 * Returns the presenter where this component belongs to.
	 * @param  bool   throw exception if presenter doesn't exist?
	 * @return Presenter|NULL
	 */
	public function getPresenter($need = TRUE)
	{
		return $this->lookup('Nette\Application\Presenter', $need);
	}



	/**
	 * Returns a fully-qualified name that uniquely identifies the component
	 * within the presenter hierarchy.
	 * @return string
	 */
	public function getUniqueId()
	{
		return $this->lookupPath('Nette\Application\Presenter', TRUE);
	}



	/**
	 * This method will be called when the component (or component's parent)
	 * becomes attached to a monitored object. Do not call this method yourself.
	 * @param  IComponent
	 * @return void
	 */
	protected function attached($presenter)
	{
		if ($presenter instanceof Presenter) {
			$this->loadState($presenter->popGlobalParams($this->getUniqueId()));
		}
	}



	/**
	 * Calls public method if exists.
	 * @param  string
	 * @param  array
	 * @return bool  does method exist?
	 */
	protected function tryCall($method, array $params)
	{
		$class = $this->getClass();
		if (PresenterHelpers::isMethodCallable($class, $method)) {
			$args = PresenterHelpers::paramsToArgs($class, $method, $params);
			call_user_func_array(array($this, $method), $args);
			return TRUE;
		}
		return FALSE;
	}



	/********************* interface IStatePersistent ****************d*g**/



	/**
	 * Loads state informations.
	 * @param  array
	 * @return void
	 */
	public function loadState(array $params)
	{
		$this->params = $params;
		foreach (PresenterHelpers::getPersistentParams($this->getClass()) as $nm => $l)
		{
			if (!isset($params[$nm])) continue; // ignore NULL values
			if ($l['type']) settype($params[$nm], $l['type']);
			$this->$nm = & $params[$nm];
		}
	}



	/**
	 * Saves state informations for next request.
	 * @param  array
	 * @param  portion specified by class name (used by Presenter)
	 * @return void
	 */
	public function saveState(array & $params, $forClass = NULL)
	{
		foreach (PresenterHelpers::getPersistentParams($forClass === NULL ? $this->getClass() : $forClass) as $nm => $l)
		{
			if (isset($params[$nm])) {
				$val = $params[$nm]; // injected value

			} elseif (array_key_exists($nm, $params)) { // $params[$nm] === NULL
				continue; // means skip

			} elseif (!isset($l['since']) || $this instanceof $l['since']) {
				$val = $this->$nm; // object property value

			} else {
				continue; // ignored parameter
			}

			if (is_object($val)) {
				throw new InvalidStateException("Persistent parameter must be scalar or array, '$this->class::\$$nm' is " . gettype($val));

			} else {
				if ($l['type'] === NULL) {
					if ((string) $val === '') $val = NULL;
				} else {
					settype($val, $l['type']);
					if ($val === $l['def']) $val = NULL;
				}
				$params[$nm] = $val;
			}
		}
	}



	/**
	 * Returns component param.
	 * If no key is passed, returns the entire array.
	 * @param  string key
	 * @param  mixed  default value
	 * @return mixed
	 */
	final public function getParam($key = NULL, $default = NULL)
	{
		if (func_num_args() === 0) {
			return $this->params;

		} elseif (isset($this->params[$key])) {
			return $this->params[$key];

		} else {
			return $default;
		}
	}



	/**
	 * Returns a fully-qualified name that uniquely identifies the parameter.
	 * @return string
	 */
	final public function getParamId($key)
	{
		$uid = $this->getUniqueId();
		return $uid === '' ? $key : $uid . self::NAME_SEPARATOR . $key;
	}



	/********************* interface ISignalReceiver ****************d*g**/



	/**
	 * Calls signal handler method.
	 * @param  string
	 * @return void
	 * @throws BadSignalException if there is not handler method
	 */
	public function signalReceived($signal)
	{
		if (!$this->tryCall($this->formatSignalMethod($signal), $this->params)) {
			throw new BadSignalException("There is no handler for signal '$signal' in '{$this->getClass()}' class.");
		}
	}



	/**
	 * Formats signal handler method name -> case sensitivity doesn't matter.
	 * @param  string
	 * @return string
	 */
	protected function formatSignalMethod($signal)
	{
		return $signal == NULL ? NULL : 'handle' . $signal; // intentionally ==
	}



	/********************* navigation ****************d*g**/



	/**
	 * Generates URL to presenter, view or signal.
	 * @param  string   destination in format "[[module:]presenter:]view" or "signal!"
	 * @param  array|mixed
	 * @return string
	 * @throws InvalidLinkException
	 */
	public function link($destination, $args = array())
	{
		if (!is_array($args)) {
			$args = func_get_args();
			array_shift($args);
		}

		try {
			return $this->getPresenter()->createRequest($this, $destination, $args, 'link');

		} catch (InvalidLinkException $e) {
			return $this->getPresenter()->handleInvalidLink($e);
		}
	}



	public function lazyLink($destination, $args = array())
	{
		if (!is_array($args)) {
			$args = func_get_args();
			array_shift($args);
		}

		return new Link($this, $destination, $args);
	}



	public function ajaxLink($destination, $args = array())
	{
		return $this->getPresenter()->getAjaxDriver()->link($destination === NULL ? NULL : $this->link($destination, $args));
	}



	/**
	 * Redirect to another presenter, view or signal.
	 * @param  string
	 * @param  array
	 * @param  int HTTP error code
	 * @return void
	 * @throws RedirectingException
	 */
	public function redirect($destination, $args = NULL, $code = /*Nette\Web\*/IHttpResponse::S303_POST_GET)
	{
		if (!is_array($args)) {
			$args = (array) $args;
		}
		$presenter = $this->getPresenter();
		$presenter->redirectUri($presenter->createRequest($this, $destination, $args, 'redirect'), $code);
	}

}
