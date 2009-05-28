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
 * @package    Nette\Application
 * @version    $Id$
 */

/*namespace Nette\Application;*/



require_once dirname(__FILE__) . '/../Forms/Form.php';

require_once dirname(__FILE__) . '/../Application/ISignalReceiver.php';



/**
 * Web form as presenter component.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Application
 *
 * @property-read Presenter $presenter
 */
class AppForm extends /*Nette\Forms\*/Form implements ISignalReceiver
{

	/**
	 * Application form constructor.
	 */
	public function __construct(/*Nette\*/IComponentContainer $parent = NULL, $name = NULL)
	{
		$this->monitor('Nette\Application\Presenter');
		parent::__construct($name, $parent);
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
	 * This method will be called when the component (or component's parent)
	 * becomes attached to a monitored object. Do not call this method yourself.
	 * @param  IComponent
	 * @return void
	 */
	protected function attached($presenter)
	{
		if ($presenter instanceof Presenter) {
			$this->setAction(new Link(
				$presenter,
				$this->lookupPath('Nette\Application\Presenter') . self::NAME_SEPARATOR . 'submit!',
				array()
			));
		}
	}



	/**
	 * Detects form submission and loads PresenterRequest values.
	 * @return void
	 */
	public function processHttpRequest($foo = NULL)
	{
		$presenter = $this->getPresenter();

		$this->submittedBy = FALSE;
		if (!$presenter->isSignalReceiver($this, 'submit')) return;

		$isPost = strcasecmp($this->getMethod(), 'post') === 0;
		$request = $presenter->getRequest();
		if ($request->isMethod('forward') || $request->isMethod('post') !== $isPost) return;

		$this->submittedBy = TRUE;
		if ($isPost) {
			$this->loadHttpData(/*Nette\*/Tools::arrayMergeTree($request->getPost(), $request->getFiles()));

		} else {
			$this->loadHttpData($request->getParams());
		}
	}



	/********************* interface ISignalReceiver ****************d*g**/



	/**
	 * This method is called by presenter.
	 * @param  string
	 * @return void
	 */
	public function signalReceived($signal)
	{
		if ($signal === 'submit') {
			$this->submit();

		} else {
			throw new BadSignalException("There is no handler for signal '$signal' in '{$this->getClass()}'.");
		}
	}

}
