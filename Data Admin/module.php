<?php

// Module Functions
require_once __DIR__ . '/moduleFunctions.php';

// Add module namespace to Gibbon autoloader
$autoloader->addPsr4('Modules\DataAdmin\\', 'modules/Data Admin/src/');
