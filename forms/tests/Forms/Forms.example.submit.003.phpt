<?php

/**
 * Test: Nette\Forms example.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette\Forms
 * @subpackage UnitTests
 */

/*use Nette\Forms\Form;*/



require dirname(__FILE__) . '/../NetteTest/initialize.php';



$disableExit = TRUE;
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = array('name'=>'�lu&#357;ou&#269;k� k&#367;&#328;','country'=>array(0=>'&#268;esk� republika',1=>'SlovakiaXX',2=>'Japan',),'note'=>'&#1078;&#1077;&#1076;','submit1'=>'Send','userid'=>'k&#367;&#328;',);
/*Nette\*/Debug::$productionMode = FALSE;
/*Nette\*/Debug::$consoleMode = TRUE;

require '../../examples/forms/custom-encoding.php';



__halt_compiler();
