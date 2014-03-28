<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Nette\Bridges\Framework;

use Nette;


interface ILatteFactory
{

	/**
	 * @return Nette\Latte\Engine
	 */
	function create();

}
