<h1>Nette::Templates::TemplateFilters::curlyBrackets & snippets test</h1>

<pre>
<?php
require_once '../../Nette/loader.php';

/*use Nette::Debug;*/
/*use Nette::Environment;*/
/*use Nette::Templates::Template;*/


class MockControl implements /*Nette::Application::*/IPartiallyRenderable
{

	function invalidateControl($snippet = NULL)
	{
	}

	function isControlInvalid($snippet = NULL)
	{
	}

	static function isOutputAllowed()
	{
		return FALSE;
	}

	public function beginSnippet($name = 'main', $startTag = 'div')
	{
	}

	public function endSnippet($name = NULL)
	{
	}

}


function printSource(Template $template, $s)
{
	echo $s;
}


$tmpDir = dirname(__FILE__) . '/tmp';
foreach (glob("$tmpDir/*") as $file) unlink($file); // delete all files

Environment::setVariable('tempDir', $tmpDir);

$template = new Template;
$template->setFile(dirname(__FILE__) . '/templates/curly-brackets-snippet.phtml');
$template->registerFilter(/*Nette::Templates::*/'TemplateFilters::curlyBrackets');
$template->registerFilter('printSource');
$template->control = new MockControl;
$template->render();
