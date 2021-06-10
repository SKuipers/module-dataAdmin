<?php

// Include the Module's Composer autoloader
require __DIR__ . '/vendor/autoload.php';

// Module Functions
require_once __DIR__ . '/moduleFunctions.php';

// Add module namespace to Gibbon autoloader
$autoloader->addPsr4('Modules\\DataAdmin\\', $session->get('absolutePath').'/modules/Data Admin/src/');
