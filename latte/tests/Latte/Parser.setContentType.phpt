<?php

/**
 * Test: Latte\Parser::parse()
 */

declare(strict_types=1);

use Latte\Compiler\Token;
use Latte\Engine;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


function parse($s, $contentType = null)
{
	$parser = new \Latte\Compiler\Parser;
	$parser->setContentType($contentType ?: Engine::CONTENT_HTML);
	return array_map(function (Token $token) {
		return [$token->type, $token->text];
	}, $parser->parse($s));
}


Assert::same([
	[Token::HTML_TAG_BEGIN, '<script'],
	[Token::HTML_TAG_END, '>'],
	[Token::TEXT, ' <div /> '],
	[Token::HTML_TAG_BEGIN, '</script'],
	[Token::HTML_TAG_END, '>'],
], parse('<script> <div /> </script>', Engine::CONTENT_HTML));

Assert::same([
	[Token::MACRO_TAG, '{contentType html}'],
	[Token::HTML_TAG_BEGIN, '<script'],
	[Token::HTML_TAG_END, '>'],
	[Token::TEXT, ' <div /> '],
	[Token::HTML_TAG_BEGIN, '</script'],
	[Token::HTML_TAG_END, '>'],
], parse('{contentType html}<script> <div /> </script>'));

Assert::same([
	[Token::HTML_TAG_BEGIN, '<script'],
	[Token::HTML_TAG_END, '>'],
	[Token::TEXT, ' <div /> '],
	[Token::HTML_TAG_BEGIN, '</script'],
	[Token::HTML_TAG_END, '>'],
], parse('<script> <div /> </script>', Engine::CONTENT_XHTML));

Assert::same([
	[Token::MACRO_TAG, '{contentType xhtml}'],
	[Token::HTML_TAG_BEGIN, '<script'],
	[Token::HTML_TAG_END, '>'],
	[Token::TEXT, ' <div /> '],
	[Token::HTML_TAG_BEGIN, '</script'],
	[Token::HTML_TAG_END, '>'],
], parse('{contentType xhtml}<script> <div /> </script>'));

Assert::same([
	[Token::HTML_TAG_BEGIN, '<script'],
	[Token::HTML_TAG_END, '>'],
	[Token::TEXT, ' '],
	[Token::HTML_TAG_BEGIN, '<div'],
	[Token::HTML_TAG_END, ' />'],
	[Token::TEXT, ' '],
	[Token::HTML_TAG_BEGIN, '</script'],
	[Token::HTML_TAG_END, '>'],
], parse('<script> <div /> </script>', Engine::CONTENT_XML));

Assert::same([
	[Token::MACRO_TAG, '{contentType xml}'],
	[Token::HTML_TAG_BEGIN, '<script'],
	[Token::HTML_TAG_END, '>'],
	[Token::TEXT, ' '],
	[Token::HTML_TAG_BEGIN, '<div'],
	[Token::HTML_TAG_END, ' />'],
	[Token::TEXT, ' '],
	[Token::HTML_TAG_BEGIN, '</script'],
	[Token::HTML_TAG_END, '>'],
], parse('{contentType xml}<script> <div /> </script>'));

Assert::same([
	[Token::TEXT, '<script> <div /> </script>'],
], parse('<script> <div /> </script>', Engine::CONTENT_TEXT));

Assert::same([
	[Token::MACRO_TAG, '{contentType text}'],
	[Token::TEXT, '<script> <div /> </script>'],
], parse('{contentType text}<script> <div /> </script>'));

Assert::same([
	[Token::TEXT, '<script> <div /> </script>'],
], parse('<script> <div /> </script>', Engine::CONTENT_ICAL));

Assert::same([
	[Token::MACRO_TAG, '{contentType ical}'],
	[Token::TEXT, '<script> <div /> </script>'],
], parse('{contentType ical}<script> <div /> </script>'));

Assert::same([
	[Token::HTML_TAG_BEGIN, '<script'],
	[Token::HTML_TAG_END, ' />'],
	[Token::TEXT, ' '],
	[Token::HTML_TAG_BEGIN, '<div'],
	[Token::HTML_TAG_END, ' />'],
], parse('<script /> <div />', Engine::CONTENT_HTML));
