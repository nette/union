<?php

/**
 * @phpini upload_max_filesize=123
 */

require __DIR__ . '/bootstrap.php';


Tester\Assert::same('123', ini_get('upload_max_filesize'));
