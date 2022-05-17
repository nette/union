<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Latte\Compiler;

use Latte;
use Latte\CompileException;
use Latte\Compiler\Nodes\FragmentNode;
use Latte\ContentType;
use Latte\Helpers;
use Latte\Policy;
use Latte\SecurityViolationException;


final class TemplateParser
{
	use Latte\Strict;

	public const
		LocationHead = 1,
		LocationText = 2,
		LocationTag = 3;

	public int $location = self::LocationHead;

	/** @var array<string, callable(Tag, self): (Node|\Generator|void)> */
	private array $tagParsers = [];

	/** @var array<string, \stdClass> */
	private array $attrParsersInfo = [];

	private TemplateParserHtml $html;
	private ?TokenStream $stream = null;
	private ?Policy $policy = null;
	private string $contentType = ContentType::Html;
	private ?Tag $tag = null;
	private $lastResolver;


	/**
	 * Parses tokens to nodes.
	 * @throws CompileException
	 */
	public function parse(string $template, TemplateLexer $lexer): Nodes\TemplateNode
	{
		$this->html = new TemplateParserHtml($this, $this->completeAttrParsers());
		$this->stream = new TokenStream($lexer->tokenize($template, $this->contentType));

		$node = new Nodes\TemplateNode;
		$node->main = $this->parseFragment([$this->html, 'inTextResolve']);
		$node->contentType = $this->contentType;

		if (!$this->stream->peek()->isEnd()) {
			$this->stream->throwUnexpectedException();
		}

		return $node;
	}


	public function parseFragment(callable $resolver): FragmentNode
	{
		$res = new FragmentNode;
		$save = [$this->lastResolver, $this->tag];
		$this->lastResolver = $resolver;
		try {
			while (!$this->stream->peek()->isEnd()) {
				if ($node = $resolver()) {
					$res->append($node);
				} else {
					break;
				}
			}

			return $res;
		} finally {
			[$this->lastResolver, $this->tag] = $save;
		}
	}


	public function inTextResolve(): ?Node
	{
		$token = $this->stream->peek();
		return match ($token->type) {
			Token::TEXT => $this->parseText(),
			Token::MACRO_TAG => $this->parseLatteStatement(),
			Token::COMMENT => $this->parseLatteComment(),
			default => null,
		};
	}


	public function parseText(): Nodes\TextNode
	{
		$token = $this->stream->consume(Token::TEXT);
		if ($this->location === self::LocationHead && trim($token->text) !== '') {
			$this->location = self::LocationText;
		}
		return new Nodes\TextNode($token->text, $token->position);
	}


	public function parseLatteComment(): Node
	{
		$token = $this->stream->consume(Token::COMMENT);
		if ($token->indentation === null && $token->newline) {
			return new Nodes\TextNode("\n", $token->position);
		}
		return new Nodes\NopNode;
	}


	public function parseLatteStatement(): ?Node
	{
		$token = $this->stream->peek();
		if ($token->closing
			|| (isset($this->tag->data->filters) && in_array($token->name, $this->tag->data->filters, true))
		) {
			return null; // go back to previous parseLatteStatement()
		}

		$token = $endToken = $this->stream->consume(Token::MACRO_TAG);
		$startTag = $this->pushTag($this->createTag($token));

		$parser = $this->getTagParser($startTag->name, $token->position);
		$res = $parser($startTag, $this);
		if ($res instanceof \Generator) {
			if (!$res->valid() && !$startTag->void) {
				throw new \LogicException("Incorrect behavior of {{$startTag->name}} parser, yield call is expected (on line {$startTag->position->line})");
			}

			if ($startTag->void) {
				$res->send([new FragmentNode, $startTag]);
			} else {
				while ($res->valid()) {
					$startTag->data->filters = $res->current() ?: null;
					$content = $this->parseFragment($this->lastResolver);

					if ($startTag->outputMode === $startTag::OutputKeepIndentation && $token->newline) {
						array_unshift($content->children, new Nodes\TextNode("\n", $startTag->position));
					}

					$endToken = $this->stream->tryConsume(Token::MACRO_TAG);
					if (!$endToken) {
						$this->checkEndTag($startTag, null);
						$res->send([$content, null]);
						break;
					}

					$tag = $this->createTag($endToken);
					if ($tag->closing) {
						$this->checkEndTag($startTag, $tag);
						$res->send([$content, $tag]);
						break;
					} elseif (in_array($tag->name, $startTag->data->filters ?? [], true)) {
						$this->pushTag($tag);
						$res->send([$content, $tag]);
						$this->popTag();
					} else {
						throw new CompileException('Unexpected tag ' . substr($tag->getNotation(true), 0, -1) . '}', $tag->position);
					}
				}
			}

			if ($res->valid()) {
				throw new \LogicException("Incorrect behavior of {{$startTag->name}} parser, more yield calls than expected (on line {$startTag->position->line})");
			}

			$node = $res->getReturn();

		} elseif ($startTag->void) {
			throw new CompileException('Unexpected /} in tag ' . substr($startTag->getNotation(true), 0, -1) . '/}', $startTag->position);

		} else {
			$node = $res;
		}

		if (!$node instanceof Node) {
			throw new \LogicException("Incorrect behavior of {{$startTag->name}} parser, unexpected returned value (on line {$startTag->position->line})");
		}

		$outputMode = $node instanceof Nodes\StatementNode ? $startTag->outputMode : null;
		if ($outputMode !== $startTag::OutputNone && $this->location === self::LocationHead) {
			$this->location = self::LocationText;
		}

		$this->popTag();

		$node->position = $token->position;
		$replaced = $outputMode === $startTag::OutputKeepIndentation || $outputMode === null;
		$res = new FragmentNode;
		if ($token->indentation && ($replaced || !$token->newline)) {
			$res->append(new Nodes\TextNode($token->indentation, $token->position));
		}

		$res->append($node);

		if ($endToken?->newline && ($replaced || $endToken?->indentation === null)) {
			$res->append(new Nodes\TextNode("\n", $endToken->position));
		}

		return $res;
	}


	private function createTag(Token $token): Tag
	{
		$modifiers = $token->modifiers;

		if (strpbrk($token->name, '=~%^&_')) {
			if (!Helpers::removeFilter($modifiers, 'noescape')) {
				$modifiers .= '|escape';
			} elseif ($this->policy && !$this->policy->isFilterAllowed('noescape')) {
				throw new SecurityViolationException('Filter |noescape is not allowed.');
			}

			if (
				$token->name === '='
				&& $this->html->getElement()
				&& ($prev = $this->stream->peek(-1))
				&& $this->contentType === Escaper::Html
				&& strcasecmp($this->html->getElement()->name, 'script') === 0
				&& preg_match('#["\']$#D', $prev->text)
			) {
				throw new CompileException("Do not place {$token->text} inside quotes in JavaScript.", $token->position);
			}
		}

		return new Tag($token->name, $token->value, $modifiers, $token->empty, $token->closing, $this->location, $this->html->getElement(), position: $token->position);
	}


	/** @param  array<string, \stdClass|callable(Tag, self): (Node|\Generator|void)>  $parsers */
	public function addTags(array $parsers): static
	{
		foreach ($parsers as $name => $info) {
			$info = $info instanceof \stdClass ? $info : Latte\Extension::order($info);
			if (str_starts_with($name, TemplateLexer::NPrefix)) {
				$this->attrParsersInfo[substr($name, 2)] = $info;
			} else {
				$this->tagParsers[$name] = $info->subject;
				if ($info->generator = Helpers::toReflection($info->subject)->isGenerator()) {
					$this->attrParsersInfo[$name] = $info;
				}
			}
		}

		return $this;
	}


	/** @return callable(Tag, self): (Node|\Generator|void) */
	private function getTagParser(string $name, Position $pos): callable
	{
		if (!isset($this->tagParsers[$name])) {
			$hint = ($t = Helpers::getSuggestion(array_keys($this->tagParsers), $name))
				? ", did you mean {{$t}}?"
				: '';
			if ($this->contentType === ContentType::Html
				&& in_array($this->html->getElement()?->name, ['script', 'style'], true)
			) {
				$hint .= ' (in JavaScript or CSS, try to put a space after bracket or use n:syntax=off)';
			}
			throw new CompileException("Unexpected tag {{$name}}$hint", $pos);
		} elseif (!$this->isTagAllowed($name)) {
			throw new SecurityViolationException("Tag {{$name}} is not allowed.");
		}

		return $this->tagParsers[$name];
	}


	private function completeAttrParsers(): array
	{
		$list = Helpers::sortBeforeAfter($this->attrParsersInfo);
		$parsers = [];
		foreach ($list as $name => $info) {
			$parsers[$name] = $info->subject;
			if ($info->generator ?? false) {
				$parsers[Tag::PrefixInner . '-' . $name] = $parsers[Tag::PrefixTag . '-' . $name] = $parsers[$name];
			}
		}

		return $parsers;
	}


	private function checkEndTag(Tag $start, ?Tag $end): void
	{
		if (!$end) {
			if ($start->name !== 'block' || $this->tag->parent) { // TODO: hardcoded
				$this->stream->throwUnexpectedException(expected: ["{/$start->name}"]);
			}

		} elseif (
			($end->name !== $start->name && $end->name !== '')
			|| !$end->closing
			|| $end->void
			|| $end->modifiers
			|| ($end->args !== '' && $start->args !== '' && !str_starts_with($start->args . ' ', $end->args . ' '))
		) {
			$tag = $end->getNotation($end->args !== '');
			throw new CompileException("Unexpected $tag, expecting {/$start->name}", $end->position);
		}
	}


	public function setPolicy(?Policy $policy): static
	{
		$this->policy = $policy;
		return $this;
	}


	public function setContentType(string $type): static
	{
		$this->contentType = $type;
		return $this;
	}


	public function getContentType(): string
	{
		return $this->contentType;
	}


	/** @internal */
	public function getStream(): TokenStream
	{
		return $this->stream;
	}


	public function peekTag(): ?Tag
	{
		return $this->tag;
	}


	public function pushTag(Tag $tag): Tag
	{
		$tag->parent = $this->tag;
		$this->tag = $tag;
		return $tag;
	}


	public function popTag(): void
	{
		$this->tag = $this->tag->parent;
	}


	public function isTagAllowed(string $name): bool
	{
		return !$this->policy || $this->policy->isTagAllowed($name);
	}
}
