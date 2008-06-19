<h1>Nette::Application::TemplateFilters::netteLinks test</h1>

<?php
require_once '../../Nette/loader.php';

class MockPresenterComponent extends /*Nette::Application::*/PresenterComponent
{
	public function link($signal, $args = array())
	{
		$args = http_build_query($args);
		return "#$signal $args";
	}

}

/*use Nette::Debug;*/
/*use Nette::Environment;*/
/*use Nette::Application::Template;*/

Environment::setVariable('tempDir', dirname(__FILE__) . '/tmp');

$template = new Template;
$template->setCache(NULL);
$template->setFile(dirname(__FILE__) . '/templates/nette-links.phtml');
$template->registerFilter(array(/*Nette::Application::*/'TemplateFilters', 'netteLinks'));
$template->component = new MockPresenterComponent;
$template->render();
