<?php

/**
 * Test: Nette\Forms default rendering GET form.
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$form = new Nette\Forms\Form;
$form->setMethod('GET');
$form->setAction('link');
$form->addCheckboxList('list')
	->setItems(['First', 'Second']);
$form->addHidden('userid');
$form->addSubmit('submit', 'Send');

$form->fireEvents();

Assert::match('<form action="link" method="get">

<table>
<tr>
	<th><label></label></th>

	<td><label><input type="checkbox" name="list[]" value="0">First</label><br><label><input type="checkbox" name="list[]" value="1">Second</label></td>
</tr>

<tr>
	<th></th>

	<td><input type="submit" name="_submit" value="Send" class="button"></td>
</tr>
</table>

<input type="hidden" name="userid" value=""><!--[if IE]><input type=IEbug disabled style="display:none"><![endif]-->
</form>', $form->__toString(true));

Assert::same('link', $form->getAction());
