<?php

/**
 * Test: Latte\Parser::parse()
 */

declare(strict_types=1);

use Latte\Compiler\Token;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


function parse($s)
{
	$parser = new \Latte\Compiler\Parser;
	return array_map(function (Token $token) {
		return array_filter([$token->type, $token->text, $token->name, $token->value]);
	}, $parser->parse($s));
}


Assert::same([
	[Token::TEXT, '<0>'],
], parse('<0>'));

Assert::same([
	[Token::HTML_TAG_BEGIN, '<x:-._', 'x:-._'],
	[Token::HTML_TAG_END, '>'],
], parse('<x:-._>'));

Assert::same([
	[Token::HTML_TAG_BEGIN, '<?'],
	[Token::TEXT, 'xml encoding="'],
	[Token::MACRO_TAG, '{$enc}', '=', '$enc'],
	[Token::TEXT, '" ?'],
	[Token::HTML_TAG_END, '>'],
	[Token::TEXT, 'text'],
], parse('<?xml encoding="{$enc}" ?>text'));

Assert::same([
	[Token::HTML_TAG_BEGIN, '<?'],
	[Token::TEXT, 'php $abc ?'],
	[Token::HTML_TAG_END, '>'],
	[Token::TEXT, 'text'],
], parse('<?php $abc ?>text'));

Assert::same([
	[Token::HTML_TAG_BEGIN, '<?'],
	[Token::TEXT, '= $abc ?'],
	[Token::HTML_TAG_END, '>'],
	[Token::TEXT, 'text'],
], parse('<?= $abc ?>text'));

Assert::same([
	[Token::HTML_TAG_BEGIN, '<?'],
	[Token::TEXT, 'bogus'],
	[Token::HTML_TAG_END, '>'],
	[Token::TEXT, 'text'],
], parse('<?bogus>text'));

Assert::same([
	[Token::MACRO_TAG, '{contentType xml}', 'contentType', 'xml'],
	[Token::HTML_TAG_BEGIN, '<?'],
	[Token::TEXT, 'bogus>text'],
], parse('{contentType xml}<?bogus>text'));

Assert::same([
	[Token::HTML_TAG_BEGIN, '<!'],
	[Token::TEXT, 'doctype html'],
	[Token::HTML_TAG_END, '>'],
	[Token::TEXT, 'text'],
], parse('<!doctype html>text'));

Assert::same([
	[Token::HTML_TAG_BEGIN, '<!'],
	[Token::TEXT, '--'],
	[Token::HTML_TAG_END, '>'],
	[Token::TEXT, ' text> --> text'],
], parse('<!--> text> --> text'));

Assert::same([
	[Token::HTML_TAG_BEGIN, '<!--'],
	[Token::TEXT, ' text> '],
	[Token::HTML_TAG_END, '-->'],
	[Token::TEXT, ' text'],
], parse('<!-- text> --> text'));

Assert::same([
	[Token::HTML_TAG_BEGIN, '<!'],
	[Token::TEXT, 'bogus'],
	[Token::HTML_TAG_END, '>'],
	[Token::TEXT, 'text'],
], parse('<!bogus>text'));

Assert::same([
	[Token::HTML_TAG_BEGIN, '<div', 'div'],
	[Token::COMMENT, ' n:syntax="off"', 'n:syntax', 'off'],
	[Token::HTML_TAG_END, '>'],
	[Token::HTML_TAG_BEGIN, '<div', 'div'],
	[Token::HTML_TAG_END, '>'],
	[Token::TEXT, '{foo}'],
	[Token::HTML_TAG_BEGIN, '</div', 'div'],
	[Token::HTML_TAG_END, '>'],
	[Token::TEXT, '{bar}'],
	[Token::HTML_TAG_BEGIN, '</div', 'div'],
	[Token::HTML_TAG_END, '>'],
	[Token::MACRO_TAG, '{lorem}', 'lorem'],
], parse('<div n:syntax="off"><div>{foo}</div>{bar}</div>{lorem}'));

// html attributes
Assert::same([
	[Token::HTML_TAG_BEGIN, '<div', 'div'],
	[Token::HTML_ATTRIBUTE_BEGIN, ' a', 'a'],
	[Token::HTML_ATTRIBUTE_BEGIN, ' b', 'b'],
	[Token::HTML_ATTRIBUTE_BEGIN, ' c = d', 'c', 'd'],
	[Token::HTML_ATTRIBUTE_BEGIN, ' e = "', 'e', '"'],
	[Token::TEXT, 'f'],
	[Token::HTML_ATTRIBUTE_END, '"'],
	[Token::HTML_ATTRIBUTE_BEGIN, ' g', 'g'],
	[Token::HTML_TAG_END, '>'],
	[Token::HTML_TAG_BEGIN, '</div', 'div'],
	[Token::HTML_TAG_END, '>'],
], parse('<div a b c = d e = "f" g></div>'));

Assert::same([
	[Token::HTML_TAG_BEGIN, '<div', 'div'],
	[Token::HTML_ATTRIBUTE_BEGIN, ' a', 'a'],
	[Token::TEXT, ' '],
	[Token::MACRO_TAG, '{b}', 'b'],
	[Token::HTML_ATTRIBUTE_BEGIN, ' c', 'c'],
	[Token::TEXT, ' = '],
	[Token::MACRO_TAG, '{d}', 'd'],
	[Token::HTML_ATTRIBUTE_BEGIN, ' e = a', 'e', 'a'],
	[Token::MACRO_TAG, '{b}', 'b'],
	[Token::HTML_ATTRIBUTE_BEGIN, 'c', 'c'],
	[Token::HTML_ATTRIBUTE_BEGIN, ' f = "', 'f', '"'],
	[Token::TEXT, 'a'],
	[Token::MACRO_TAG, '{b}', 'b'],
	[Token::TEXT, 'c'],
	[Token::HTML_ATTRIBUTE_END, '"'],
	[Token::HTML_TAG_END, '>'],
	[Token::HTML_TAG_BEGIN, '</div', 'div'],
	[Token::HTML_TAG_END, '>'],
], parse('<div a {b} c = {d} e = a{b}c f = "a{b}c"></div>'));

// macro attributes
Assert::same([
	[Token::HTML_TAG_BEGIN, '<div', 'div'],
	[Token::HTML_ATTRIBUTE_BEGIN, ' n:a', 'n:a'],
	[Token::HTML_ATTRIBUTE_BEGIN, ' n:b', 'n:b'],
	[Token::HTML_ATTRIBUTE_BEGIN, ' n:c = d', 'n:c', 'd'],
	[Token::HTML_ATTRIBUTE_BEGIN, ' n:e = "f"', 'n:e', 'f'],
	[Token::HTML_ATTRIBUTE_BEGIN, ' n:g', 'n:g'],
	[Token::HTML_TAG_END, '>'],
	[Token::HTML_TAG_BEGIN, '</div', 'div'],
	[Token::HTML_TAG_END, '>'],
], parse('<div n:a n:b n:c = d n:e = "f" n:g></div>'));
