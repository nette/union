<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Utils;


/**
 * Traversing helper.
 */
class TokenIterator
{
	/** @var array */
	public $tokens;

	/** @var int */
	public $position = -1;

	/** @var array */
	public $ignored = array();


	/**
	 * @param array[]
	 */
	public function __construct(array $tokens)
	{
		$this->tokens = $tokens;
	}


	/**
	 * Returns current token.
	 * @return array|null
	 */
	public function currentToken()
	{
		return isset($this->tokens[$this->position])
			? $this->tokens[$this->position]
			: null;
	}


	/**
	 * Returns current token value.
	 * @return string|null
	 */
	public function currentValue()
	{
		return isset($this->tokens[$this->position])
			? $this->tokens[$this->position][Tokenizer::VALUE]
			: null;
	}


	/**
	 * Returns next token.
	 * @param  int|string  (optional) desired token type or value
	 * @return array|null
	 */
	public function nextToken()
	{
		return $this->scan(func_get_args(), true, true); // onlyFirst, advance
	}


	/**
	 * Returns next token value.
	 * @param  int|string  (optional) desired token type or value
	 * @return string|null
	 */
	public function nextValue()
	{
		return $this->scan(func_get_args(), true, true, true); // onlyFirst, advance, strings
	}


	/**
	 * Returns all next tokens.
	 * @param  int|string  (optional) desired token type or value
	 * @return array[]
	 */
	public function nextAll()
	{
		return $this->scan(func_get_args(), false, true); // advance
	}


	/**
	 * Returns all next tokens until it sees a given token type or value.
	 * @param  int|string  token type or value to stop before
	 * @return array[]
	 */
	public function nextUntil($arg)
	{
		return $this->scan(func_get_args(), false, true, false, true); // advance, until
	}


	/**
	 * Returns concatenation of all next token values.
	 * @param  int|string  (optional) token type or value to be joined
	 * @return string
	 */
	public function joinAll()
	{
		return $this->scan(func_get_args(), false, true, true); // advance, strings
	}


	/**
	 * Returns concatenation of all next tokens until it sees a given token type or value.
	 * @param  int|string  token type or value to stop before
	 * @return string
	 */
	public function joinUntil($arg)
	{
		return $this->scan(func_get_args(), false, true, true, true); // advance, strings, until
	}


	/**
	 * Checks the current token.
	 * @param  int|string  token type or value
	 * @return bool
	 */
	public function isCurrent($arg)
	{
		if (!isset($this->tokens[$this->position])) {
			return false;
		}
		$args = func_get_args();
		$token = $this->tokens[$this->position];
		return in_array($token[Tokenizer::VALUE], $args, true)
			|| (isset($token[Tokenizer::TYPE]) && in_array($token[Tokenizer::TYPE], $args, true));
	}


	/**
	 * Checks the next token existence.
	 * @param  int|string  (optional) token type or value
	 * @return bool
	 */
	public function isNext()
	{
		return (bool) $this->scan(func_get_args(), true, false); // onlyFirst
	}


	/**
	 * Checks the previous token existence.
	 * @param  int|string  (optional) token type or value
	 * @return bool
	 */
	public function isPrev()
	{
		return (bool) $this->scan(func_get_args(), true, false, false, false, true); // onlyFirst, prev
	}


	/**
	 * @return static
	 */
	public function reset()
	{
		$this->position = -1;
		return $this;
	}


	/**
	 * Moves cursor to next token.
	 */
	protected function next()
	{
		$this->position++;
	}


	/**
	 * Looks for (first) (not) wanted tokens.
	 * @param  array of desired token types or values
	 * @param  bool
	 * @param  bool
	 * @param  bool
	 * @param  bool
	 * @param  bool
	 * @return mixed
	 */
	protected function scan($wanted, $onlyFirst, $advance, $strings = false, $until = false, $prev = false)
	{
		$res = $onlyFirst ? null : ($strings ? '' : array());
		$pos = $this->position + ($prev ? -1 : 1);
		do {
			if (!isset($this->tokens[$pos])) {
				if (!$wanted && $advance && !$prev && $pos <= count($this->tokens)) {
					$this->next();
				}
				return $res;
			}

			$token = $this->tokens[$pos];
			$type = isset($token[Tokenizer::TYPE]) ? $token[Tokenizer::TYPE] : null;
			if (!$wanted || (in_array($token[Tokenizer::VALUE], $wanted, true) || in_array($type, $wanted, true)) ^ $until) {
				while ($advance && !$prev && $pos > $this->position) {
					$this->next();
				}

				if ($onlyFirst) {
					return $strings ? $token[Tokenizer::VALUE] : $token;
				} elseif ($strings) {
					$res .= $token[Tokenizer::VALUE];
				} else {
					$res[] = $token;
				}

			} elseif ($until || !in_array($type, $this->ignored, true)) {
				return $res;
			}
			$pos += $prev ? -1 : 1;
		} while (true);
	}
}
