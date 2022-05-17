<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Latte\Compiler;

use Latte;
use Latte\CompileException;
use Latte\ContentType;
use Latte\RegexpException;


final class TemplateLexer
{
	use Latte\Strict;

	/** regular expression for single & double quoted PHP string */
	public const ReString = '\'(?:\\\\.|[^\'\\\\])*+\'|"(?:\\\\.|[^"\\\\])*+"';

	/** HTML tag name for Latte needs (actually is [a-zA-Z][^\s/>]*) */
	public const ReTagName = '[a-zA-Z][a-zA-Z0-9:_.-]*';

	/** special HTML attribute prefix */
	public const NPrefix = 'n:';
	private const StateEnd = 'end';

	/** @var array<string, array{string, string}> */
	public array $syntaxes = [
		'latte' => ['\{(?![\s\'"{}])', '\}'], // {...}
		'double' => ['\{\{(?![\s\'"{}])', '\}\}'], // {{...}}
		'off' => ['\{(?=/syntax\})', '\}'], // {/syntax}
	];

	/** @var string[] */
	private array $delimiters;

	/** @var array<array{name: string, args: mixed[]}> */
	private array $states;
	private string $input;
	private int $offset;
	private Position $position;
	private bool $xmlMode;


	/** @return \Generator<Token> */
	public function tokenize(string $template, string $contentType = ContentType::Html): \Generator
	{
		$this->position = new Position(1, 1, 0);
		$this->input = $this->normalize($template);
		$this->offset = 0;
		$this->states = [];
		$this->setContentType($contentType);
		$this->setSyntax(null);

		do {
			$state = $this->states[0];
			yield from $this->{$state['name']}(...$state['args']);
		} while ($this->states[0]['name'] !== self::StateEnd);

		if ($this->offset < strlen($this->input)) {
			yield $this->addToken(new Token(Token::TEXT, substr($this->input, $this->offset)));
		}

		yield $this->addToken(new Token(Token::END, ''));
	}


	private function statePlain(): \Generator
	{
		$m = yield from $this->match('~
			(?P<indent>(?<=\n|^)[ \t]*)?(?P<macro>' . $this->delimiters[0] . ')
		~xsi');

		if (!empty($m['macro'])) {
			$this->pushState('stateLatte', $m['macro'], $m['indent']);

		} else {
			$this->setState(self::StateEnd);
		}
	}


	private function stateLatte(string $delimiter, ?string $indent = null): \Generator
	{
		$this->popState();
		$m = yield from $this->match('~
			(?P<comment>\*.*?\*' . $this->delimiters[1] . ')(?P<newline>\n{0,2})|
			(?P<macro>(?>
				' . self::ReString . '|
				\{(?>' . self::ReString . '|[^\'"{}])*+\}|
				[^\'"{}]+
			)++)
			' . $this->delimiters[1] . '
			(?P<rmargin>[ \t]*\n)?
		~xsiA');

		if (!empty($m['macro'])) {
			$token = new Token(Token::MACRO_TAG, $indent . $delimiter . $m[0]);
			[$token->name, $token->value, $token->empty, $token->closing] = $this->parseMacroTag($m['macro']);
			$token->indentation = $indent;
			$token->newline = isset($m['rmargin']);
			yield $this->addToken($token);

		} elseif (!empty($m['comment'])) {
			$token = new Token(Token::COMMENT, $indent . $delimiter . $m[0]);
			$token->indentation = $indent;
			$token->newline = (bool) $m['newline'];
			yield $this->addToken($token);

		} else {
			throw new CompileException('Malformed tag contents.', $this->position);
		}
	}


	private function stateHtmlText(): \Generator
	{
		$m = yield from $this->match('~
			(?:(?<=\n|^)[ \t]*)?<(?P<closing>/?)(?P<tag>' . self::ReTagName . ')|  ##  begin of HTML tag <tag </tag - ignores <!DOCTYPE
			<(?P<htmlcomment>!(?:--(?!>))?|\?)|     ##  begin of <!, <!--, <!DOCTYPE, <?
			(?P<indent>(?<=\n|^)[ \t]*)?(?P<macro>' . $this->delimiters[0] . ')
		~xsi');

		if (!empty($m['htmlcomment'])) { // <! <?
			yield $this->addToken(new Token(Token::HTML_TAG_BEGIN, $m[0]));
			$ending = $m['htmlcomment'] === '!--'
				? '--'
				: ($m['htmlcomment'] === '?' && $this->xmlMode ? '\?' : '');
			$this->setState('stateHtmlComment', $ending);

		} elseif (!empty($m['tag'])) { // <tag or </tag
			$token = new Token(Token::HTML_TAG_BEGIN, $m[0]);
			$token->name = $m['tag'];
			$token->closing = (bool) $m['closing'];
			yield $this->addToken($token);
			$this->setState('stateHtmlTag', $m['closing'] . strtolower($m['tag']));

		} elseif (!empty($m['macro'])) {
			$this->pushState('stateLatte', $m['macro'], $m['indent']);

		} else {
			$this->setState(self::StateEnd);
		}
	}


	private function stateHtmlTag(?string $tagName = null): \Generator
	{
		$m = yield from $this->match('~
			(?P<end>/?>)([ \t]*\n)?|  ##  end of HTML tag
			(?P<macro>' . $this->delimiters[0] . ')|
			\s*(?P<attr>[^\s"\'>/={]+)(?:\s*=\s*(?P<value>["\']|[^\s"\'=<>`{]+))? ## beginning of HTML attribute
		~xsi');

		if (!empty($m[self::StateEnd])) { // end of HTML tag />
			$token = new Token(Token::HTML_TAG_END, $m[0]);
			$empty = str_contains($m[0], '/');
			yield $this->addToken($token);
			if (!$this->xmlMode && !$empty && in_array($tagName, ['script', 'style'], true)) {
				$this->setState('stateHtmlRCData', $tagName);
			} else {
				$this->setState('stateHtmlText');
			}

		} elseif (isset($m['attr']) && $m['attr'] !== '') { // HTML attribute
			$token = new Token(Token::HTML_ATTRIBUTE_BEGIN, $m[0]);
			$token->name = $m['attr'];
			$token->value = $m['value'] ?? '';

			if ($token->value === '"' || $token->value === "'") { // attribute = "'
				if (str_starts_with($token->name, self::NPrefix)) {
					$token->value = '';
					if ($m2 = yield from $this->match('~(.*?)' . $m['value'] . '~xsi')) {
						$token->value = $m2[1];
						$token->text .= $m2[0];
					}
				} else {
					yield $this->addToken($token);
					$this->pushState('stateHtmlAttribute', $m['value']);
					return;
				}
			}
			yield $this->addToken($token);
			$this->setState('stateHtmlTag');

		} elseif (!empty($m['macro'])) {
			$this->pushState('stateLatte', $m['macro']);

		} else {
			$this->setState(self::StateEnd);
		}
	}


	private function stateHtmlAttribute(string $quote): \Generator
	{
		$m = yield from $this->match('~
			(?P<quote>' . $quote . ')|  ##  end of HTML attribute
			(?P<macro>' . $this->delimiters[0] . ')
		~xsi');

		if (!empty($m['quote'])) {
			yield $this->addToken(new Token(Token::HTML_ATTRIBUTE_END, $m[0]));
			$this->popState();

		} elseif (!empty($m['macro'])) {
			$this->pushState('stateLatte', $m['macro']);
		}
	}


	private function stateHtmlRCData(string $tagName): \Generator
	{
		$m = yield from $this->match('~
			</(?P<tag>' . $tagName . ')(?=[\s/>])| ##  end HTML tag </tag
			(?P<indent>(?<=\n|^)[ \t]*)?(?P<macro>' . $this->delimiters[0] . ')
		~xsi');

		if (!empty($m['tag'])) { // </tag
			$token = new Token(Token::HTML_TAG_BEGIN, $m[0]);
			$token->name = $m['tag'];
			$token->closing = true;
			yield $this->addToken($token);
			$this->setState('stateHtmlTag');

		} elseif (!empty($m['macro'])) {
			$this->pushState('stateLatte', $m['macro'], $m['indent']);

		} else {
			$this->setState(self::StateEnd);
		}
	}


	private function stateHtmlComment(string $ending): \Generator
	{
		$m = yield from $this->match('~
			(?P<htmlcomment>' . $ending . '>)|   ##  end of HTML comment
			(?P<indent>(?<=\n|^)[ \t]*)?(?P<macro>' . $this->delimiters[0] . ')
		~xsi');

		if (!empty($m['htmlcomment'])) { // -->
			yield $this->addToken(new Token(Token::HTML_TAG_END, $m[0]));
			$this->setState('stateHtmlText');

		} elseif (!empty($m['macro'])) {
			$this->pushState('stateLatte', $m['macro'], $m['indent']);

		} else {
			$this->setState(self::StateEnd);
		}
	}


	/**
	 * Matches next token.
	 */
	private function match(string $re): \Generator
	{
		if (!preg_match($re, $this->input, $matches, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL, $this->offset)) {
			if (preg_last_error()) {
				throw new RegexpException;
			}

			return [];
		}

		$value = substr($this->input, $this->offset, $matches[0][1] - $this->offset);
		if ($value !== '') {
			yield $this->addToken(new Token(Token::TEXT, $value));
		}

		$this->offset = $matches[0][1] + strlen($matches[0][0]);
		foreach ($matches as $k => $v) {
			$matches[$k] = $v[0];
		}

		return $matches;
	}


	public function setContentType(string $type): static
	{
		if ($type === ContentType::Html || $type === ContentType::Xml) {
			$this->setState('stateHtmlText');
			$this->xmlMode = $type === ContentType::Xml;
		} else {
			$this->setState('statePlain');
		}

		return $this;
	}


	private function setState(string $state, ...$args)
	{
		$this->states[0] = ['name' => $state, 'args' => $args];
	}


	private function pushState(string $state, ...$args): void
	{
		array_unshift($this->states, null);
		$this->setState($state, ...$args);
	}


	private function popState(): void
	{
		array_shift($this->states);
	}


	/**
	 * Changes macro tag delimiters.
	 */
	public function setSyntax(?string $type): static
	{
		$type ??= 'latte';
		if (!isset($this->syntaxes[$type])) {
			throw new \InvalidArgumentException("Unknown syntax '$type'");
		}

		$this->setDelimiters($this->syntaxes[$type][0], $this->syntaxes[$type][1]);
		return $this;
	}


	/**
	 * Changes macro tag delimiters (as regular expression).
	 */
	public function setDelimiters(string $left, string $right): static
	{
		$this->delimiters = [$left, $right];
		return $this;
	}


	/**
	 * Parses macro tag to name, arguments a modifiers parts.
	 * @return array{string, string, bool, bool}|null
	 * @internal
	 */
	public function parseMacroTag(string $tag): ?array
	{
		if (!preg_match('~^
			(?P<closing>/?)
			(?P<name>=|_(?!_)|[a-z]\w*+(?:[.:-]\w+)*+(?!::|\(|\\\\)|)   ## name, /name, but not function( or class:: or namespace\
			(?P<args>(?:' . self::ReString . '|[^\'"])*?)
			(?P<empty>/?$)
		()$~Disx', $tag, $match)) {
			if (preg_last_error()) {
				throw new RegexpException;
			}

			return null;
		}

		if ($match['name'] === '') {
			$match['name'] = $match['closing'] ? '' : '=';
		}

		return [$match['name'], trim($match['args']), (bool) $match['empty'], (bool) $match['closing']];
	}


	private function addToken(Token $token): Token
	{
		$token->position = $this->position;
		$this->position = $this->position->advance($token->text);
		return $token;
	}


	private function normalize(string $str): string
	{
		if (str_starts_with($str, "\u{FEFF}")) { // BOM
			$str = substr($str, 3);
		}

		$str = str_replace("\r\n", "\n", $str);

		if (!preg_match('##u', $str)) {
			preg_match('#(?:[\x00-\x7F]|[\xC0-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2}|[\xF0-\xF7][\x80-\xBF]{3})*+#A', $str, $m);
			throw new CompileException('Template is not valid UTF-8 stream.', $this->position->advance($m[0]));

		} elseif (preg_match('#(.*?)([\x00-\x08\x0B\x0C\x0E-\x1F\x7F])#s', $str, $m)) {
			throw new CompileException('Template contains control character \x' . dechex(ord($m[2])), $this->position->advance($m[1]));
		}
		return $str;
	}
}
