<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Latte\Compiler;

use Latte\Strict;


class TextNode implements Node
{
	use Strict;

	/** @var string */
	public $content;

	/** @var int|null  position in source template */
	public $startLine;


	public function __construct(string $content, int $startLine = null)
	{
		$this->content = $content;
		$this->startLine = $startLine;
	}


	public function render(&$output): void
	{
		$s = substr(str_replace('<?', '<<?php ?>?', $this->content . '?'), 0, -1);
		$output .= $s;
	}
}
