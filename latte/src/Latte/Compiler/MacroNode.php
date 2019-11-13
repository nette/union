<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Latte\Compiler;

use Latte\CompileException;
use Latte\Strict;


/**
 * Macro element node.
 */
class MacroNode implements Node
{
	use Strict;

	public const
		PREFIX_INNER = 'inner',
		PREFIX_TAG = 'tag',
		PREFIX_NONE = 'none';

	/** @var Macro */
	public $macro;

	/** @var string */
	public $name;

	/** @var bool */
	public $empty = false;

	/** @var string  raw arguments */
	public $args;

	/** @var Node[] */
	public $children = [];

	/** @var string  raw modifier */
	public $modifiers;

	/** @var bool */
	public $closing = false;

	/** @var bool  has output? */
	public $replaced;

	/** @var MacroTokens */
	public $tokenizer;

	/** @var Node|null */
	public $parentNode;

	/** @var string */
	public $openingCode;

	/** @var string */
	public $closingCode;

	/** @var string */
	public $attrCode;

	/** @var string */
	public $content;

	/** @var string */
	public $innerContent;

	/** @var \stdClass  user data */
	public $data;

	/** @var HtmlNode|null  closest HTML node */
	public $htmlNode;

	/** @var array{string, mixed} [contentType, context] */
	public $context;

	/** @var string|null  indicates n:attribute macro and type of prefix (PREFIX_INNER, PREFIX_TAG, PREFIX_NONE) */
	public $prefix;

	/** @var int  position of start tag in source template */
	public $startLine;

	/** @var int  position of end tag in source template */
	public $endLine;

	/** @var bool */
	public $isRightmostBegin;

	/** @var bool */
	public $isRightmostEnd;


	public function __construct(
		Macro $macro,
		string $name,
		string $args = '',
		string $modifiers = '',
		Node $parentNode = null,
		HtmlNode $htmlNode = null,
		string $prefix = null
	) {
		$this->macro = $macro;
		$this->name = $name;
		$this->modifiers = $modifiers;
		$this->parentNode = $parentNode;
		$this->htmlNode = $htmlNode;
		$this->prefix = $prefix;
		$this->data = new \stdClass;
		$this->setArgs($args);
	}


	public function render(&$output): void
	{
		if ($this->empty) {
			$this->writeCode($output, (string) $this->openingCode, $this->isRightmostBegin);
			if ($this->prefix && $this->prefix !== self::PREFIX_TAG) {
				$this->htmlNode->attrCode .= $this->attrCode;
			}
			return;
		}


		$this->content = '';
		foreach ($this->children as $node) {
			$node->render($this->content);
		}

		if ($this->prefix === self::PREFIX_NONE) {
			$parts = explode($this->htmlNode->innerMarker, $this->content);
			if (count($parts) === 3) { // markers may be destroyed by inner macro
				$this->innerContent = $parts[1];
			}
		}

		$this->closing = true;
		$this->macro->nodeClosed($this);

		if (isset($parts[1]) && $this->innerContent !== $parts[1]) {
			$this->content = implode($this->htmlNode->innerMarker, [$parts[0], $this->innerContent, $parts[2]]);
		}

		if ($this->prefix && $this->prefix !== self::PREFIX_TAG) {
			$this->htmlNode->attrCode .= $this->attrCode;
		}

		$this->writeCode($output, (string) $this->openingCode, $this->isRightmostBegin);
		$output .= $this->content;
		$this->writeCode($output, (string) $this->closingCode, $this->isRightmostEnd);
	}


	private function writeCode(string &$output, string $code, ?bool $isRightmost): void
	{
		if ($isRightmost) {
			$leftOfs = ($tmp = strrpos($output, "\n")) === false ? 0 : $tmp + 1;
			$isLeftmost = trim(substr($output, $leftOfs)) === '';
			$replaced = $this->replaced ?? preg_match('#<\?php.*\secho\s#As', $code);
			if ($isLeftmost && !$replaced) {
				$output = substr($output, 0, $leftOfs); // alone macro without output -> remove indentation
				if (substr($code, -2) !== '?>') {
					$code .= '<?php ?>'; // consume new line
				}
			} elseif (substr($code, -2) === '?>') {
				$code .= "\n"; // double newline to avoid newline eating by PHP
			}
		}
		$output .= $code;
	}


	public function getParentMacroNode(): ?self
	{
		return $this->parentNode instanceof self
			? $this->parentNode
			: null;
	}


	public function setArgs(string $args): void
	{
		$this->args = $args;
		$this->tokenizer = new MacroTokens($args);
	}


	public function getNotation(): string
	{
		return $this->prefix
			? Parser::N_PREFIX . ($this->prefix === self::PREFIX_NONE ? '' : $this->prefix . '-') . $this->name
			: '{' . $this->name . '}';
	}


	/**
	 * @param  string[]  $names
	 */
	public function closest(array $names, callable $condition = null): ?self
	{
		$node = $this->getParentMacroNode();
		while ($node && (
			!in_array($node->name, $names, true)
			|| ($condition && !$condition($node))
		)) {
			$node = $node->getParentMacroNode();
		}
		return $node;
	}


	/**
	 * @param  string|bool|null  $arguments
	 * @param  string[]  $parents
	 * @throws CompileException
	 */
	public function validate($arguments, array $parents = [], bool $modifiers = false): void
	{
		if ($parents && (!$this->getParentMacroNode() || !in_array($this->parentNode->name, $parents, true))) {
			throw new CompileException('Tag ' . $this->getNotation() . ' is unexpected here.');

		} elseif ($this->modifiers !== '' && !$modifiers) {
			throw new CompileException('Filters are not allowed in ' . $this->getNotation());

		} elseif ($arguments && $this->args === '') {
			$label = is_string($arguments) ? $arguments : 'arguments';
			throw new CompileException('Missing ' . $label . ' in ' . $this->getNotation());

		} elseif ($arguments === false && $this->args !== '') {
			throw new CompileException('Arguments are not allowed in ' . $this->getNotation());
		}
	}
}
