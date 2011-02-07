<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004, 2011 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette;

use Nette;



/**
 * Simple parser & generator for Nette Object Notation.
 *
 * @author     David Grudl
 */
class Neon extends Object
{
	const BLOCK = 1;

	/** @var array */
	private static $patterns = array(
		'\'[^\'\n]*\'|"(?:\\\\.|[^"\\\\\n])*"', // string
		'@[a-zA-Z_0-9\\\\]+', // object
		'[:-](?=\s|$)|[,=[\]{}()]', // symbol
		'?:#.*', // comment
		'\n *', // indent
		'[^#"\',:=@[\]{}()<>\s](?:[^#,:=\]})>\n]+|:(?!\s)|(?<!\s)#)*(?<!\s)', // literal / boolean / integer / float
		'?: +', // whitespace
	);

	/** @var Tokenizer */
	private static $tokenizer;

	private static $brackets = array(
		'[' => ']',
		'{' => '}',
		'(' => ')',
	);

	/** @var int */
	private $n = 0;


	/**
	 * Returns the NEON representation of a value.
	 * @param  mixed
	 * @param  int
	 * @return string
	 */
	public static function encode($var, $options = NULL)
	{
		if (is_object($var)) {
			$obj = $var; $var = array();
			foreach ($obj as $k => $v) {
				$var[$k] = $v;
			}
		}
		if (is_array($var)) {
			$isArray = array_keys($var) === range(0, count($var) - 1);
			$s = '';
			if ($options & self::BLOCK) {
				foreach ($var as $k => $v) {
					$s .= $isArray ? '- ' : self::encode($k) . ':';
					if (is_object($v) || is_array($v)) {
						$c = 0;
						foreach ($v as $k2 => $v2) {
							if (is_array($v2) || is_object($v2) || (string) $k2 !== (string) $c++) {
								$s .= "\n\t" . str_replace("\n", "\n\t", self::encode($v, self::BLOCK)) . "\n";
								continue 2;
							}
						}
					}
					$s .= (!$isArray && $v === NULL) ? "\n" : ' ' . self::encode($v) . "\n";
				}
				return $s;

			} else {
				foreach ($var as $k => $v) {
					$s .= ($isArray ? '' : self::encode($k) . ': ') . self::encode($v) . ', ';
				}
				return ($isArray ? '[' : '{') . substr($s, 0, -2) . ($isArray ? ']' : '}');
			}

		} elseif (is_string($var) && !is_numeric($var) && !preg_match('~[\x00-\x1F]|^(true|false|yes|no|null)$~i', $var) && preg_match('~^' . self::$patterns[5] . '$~', $var)) {
			return $var;

		} else {
			return json_encode($var);
		}
	}



	/**
	 * Decodes a NEON string.
	 * @param  string
	 * @return mixed
	 */
	public static function decode($input)
	{
		if (!is_string($input)) {
			throw new \InvalidArgumentException("Argument must be a string, " . gettype($input) . " given.");
		}
		if (!self::$tokenizer) {
			self::$tokenizer = new Tokenizer(self::$patterns, 'mi');
		}

		$input = str_replace("\r", '', $input);
		$input = strtr($input, "\t", ' ');
		$input = "\n" . $input . "\n"; // first \n is required by "Indent"
		self::$tokenizer->tokenize($input);

		$parser = new self;
		$res = $parser->parse();

		while (isset(self::$tokenizer->tokens[$parser->n])) {
			if (self::$tokenizer->tokens[$parser->n][0] === "\n") {
				$parser->n++;
			} else {
				$parser->error();
			}
		}
		return $res;
	}



	/**
	 * @param  int  indentation (for block-parser)
	 * @param  string  end char (for inline-hash/array parser)
	 * @return array
	 */
	private function parse($indent = NULL, $endBracket = NULL)
	{
		$inlineParser = $endBracket !== NULL; // block or inline parser?

		$result = $inlineParser || $indent ? array() : NULL;
		$value = $key = $object = NULL;
		$hasValue = $hasKey = FALSE;
		$tokens = self::$tokenizer->tokens;
		$n = & $this->n;
		$count = count($tokens);

		for (; $n < $count; $n++) {
			$t = $tokens[$n];

			if ($t === ',') { // ArrayEntry separator
				if (!$hasValue || !$inlineParser) {
					$this->error();
				}
				if ($hasKey) $result[$key] = $value; else $result[] = $value;
				$hasKey = $hasValue = FALSE;

			} elseif ($t === ':' || $t === '=') { // KeyValuePair separator
				if ($hasKey || !$hasValue) {
					$this->error();
				}
				$key = (string) $value;
				$hasKey = TRUE;
				$hasValue = FALSE;

			} elseif ($t === '-') { // BlockArray bullet
				if ($hasKey || $hasValue || $inlineParser) {
					$this->error();
				}
				$key = NULL;
				$hasKey = TRUE;

			} elseif (isset(self::$brackets[$t])) { // Opening bracket [ ( {
				if ($hasValue) {
					$this->error();
				}
				$hasValue = TRUE;
				$value = $this->parse(NULL, self::$brackets[$tokens[$n++]]);

			} elseif ($t === ']' || $t === '}' || $t === ')') { // Closing bracket ] ) }
				if ($t !== $endBracket) { // unexpected type of bracket or block-parser
					$this->error();
				}
				if ($hasValue) {
					if ($hasKey) $result[$key] = $value; else $result[] = $value;
				} elseif ($hasKey) {
					$this->error();
				}
				return $result; // inline parser exit point

			} elseif ($t[0] === '@') { // Object
				$object = $t; // TODO

			} elseif ($t[0] === "\n") { // Indent
				if ($inlineParser) {
					if ($hasValue) {
						if ($hasKey) $result[$key] = $value; else $result[] = $value;
						$hasKey = $hasValue = FALSE;
					}

				} else {
					while (isset($tokens[$n+1]) && $tokens[$n+1][0] === "\n") $n++; // skip to last indent

					$newIndent = strlen($tokens[$n]) - 1;
					if ($indent === NULL) { // first iteration
						$indent = $newIndent;
					}

					if ($newIndent > $indent) { // open new block-array or hash
						if ($hasValue || !$hasKey) {
							$this->error();
						} elseif ($key === NULL) {
							$result[] = $this->parse($newIndent);
						} else {
							$result[$key] = $this->parse($newIndent);
						}
						$newIndent = strlen($tokens[$n]) - 1;
						$hasKey = FALSE;

					} else {
						if ($hasValue && !$hasKey) { // block items must have "key"; NULL key means list item
							if ($result === NULL) return $value;  // simple value parser exit point
							$this->error();

						} elseif ($hasKey) {
							$value = $hasValue ? $value : NULL;
							if ($key === NULL) $result[] = $value; else $result[$key] = $value;
							$hasKey = $hasValue = FALSE;
						}
					}

					if ($newIndent < $indent || !isset($tokens[$n+1])) { // close block
						return $result; // block parser exit point
					}
				}

			} else { // Value
				if ($hasValue) {
					$this->error();
				}
				if ($t[0] === '"') {
					$value = json_decode($t);
					if ($value === NULL) {
						$this->error('Invalid string %s');
					}
				} elseif ($t[0] === "'") {
					$value = substr($t, 1, -1);
				} elseif ($t === 'true' || $t === 'yes' || $t === 'TRUE' || $t === 'YES') {
					$value = TRUE;
				} elseif ($t === 'false' || $t === 'no' || $t === 'FALSE' || $t === 'NO') {
					$value = FALSE;
				} elseif ($t === 'null' || $t === 'NULL') {
					$value = NULL;
				} elseif (is_numeric($t)) {
					$value = $t * 1;
				} else { // literal
					$value = $t;
				}
				$hasValue = TRUE;
			}
		}

		throw new NeonException('Unexpected end of file.');
	}



	private function error($message = "Unexpected '%s'")
	{
		list(, $line, $col) = self::$tokenizer->getOffset($this->n);
		$token = str_replace("\n", '\n', Nette\String::truncate(self::$tokenizer->tokens[$this->n], 40));
		throw new NeonException(str_replace('%s', $token, $message) . "' on line " . ($line - 1) . ", column $col.");
	}

}
