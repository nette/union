<?php

/**
 * Test: Latte\Compiler and macro methods calling order.
 */

declare(strict_types=1);

use Latte\Compiler\Compiler;
use Latte\Compiler\Macro;
use Latte\Compiler\MacroNode;
use Latte\Compiler\Parser;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class MockMacro implements Macro
{
	public $calls = [];


	public function initialize()
	{
	}


	public function finalize()
	{
	}


	public function nodeOpened(MacroNode $node)
	{
		$this->calls[] = [
			__FUNCTION__,
			isset($node->htmlNode) ? $node->htmlNode->name : null,
			$node->closing,
			$node->prefix,
			$node->content,
			$node->empty,
		];
		$node->empty = false;
	}


	public function nodeClosed(MacroNode $node)
	{
		$this->calls[] = [
			__FUNCTION__,
			isset($node->htmlNode) ? $node->htmlNode->name : null,
			$node->closing,
			$node->prefix,
			preg_replace('#n:\w+#', 'n:#', $node->content),
			$node->empty,
		];
	}
}


function testCalls($template, $calls)
{
	$macro = new MockMacro;
	$parser = new Parser;
	$compiler = new Compiler;
	$compiler->addMacro('foo', $macro);
	$compiler->compile($parser->parse($template), 'Template');
	Assert::same($calls, $macro->calls);
}


testCalls('{foo}Text{/foo}', [
	['nodeOpened', null, false, null, null, false],
	['nodeClosed', null, true, null, 'Text', false],
]);

testCalls('{foo}{/foo}', [
	['nodeOpened', null, false, null, null, false],
	['nodeClosed', null, true, null, '', false],
]);

testCalls('{foo/}', [
	['nodeOpened', null, false, null, null, false],
	['nodeClosed', null, true, null, '', false],
]);

testCalls('<div1>{foo}Text{/foo}</div1>', [
	['nodeOpened', 'div1', false, null, null, false],
	['nodeClosed', 'div1', true, null, 'Text', false],
]);

testCalls("\t<div2 n:foo>Text</div2>\n", [
	['nodeOpened', 'div2', false, 'none', null, false],
	['nodeClosed', 'div2', true, 'none', "\t<div2 n:#><n:#>Text<n:#></div2>\n", false],
]);

testCalls('<div3 n:inner-foo>Text</div3>', [
	['nodeOpened', 'div3', false, 'inner', null, false],
	['nodeClosed', 'div3', true, 'inner', 'Text', false],
]);

testCalls("\t<div4 n:tag-foo>Text</div4>\n", [
	['nodeOpened', 'div4', false, 'tag', null, false],
	['nodeOpened', 'div4', false, 'tag', null, false],
	['nodeClosed', 'div4', true, 'tag', '	<div4 n:#>', false],
	['nodeClosed', 'div4', true, 'tag', "</div4>\n", false],
]);
