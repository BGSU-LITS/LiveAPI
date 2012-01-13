<?php

// Find a better way to do this...
date_default_timezone_set("America/New_York");

define("LIVEAPI_PATH", dirname(__FILE__).DIRECTORY_SEPARATOR);

// Include vendor classes
include LIVEAPI_PATH."vendor/markdown/markdown.php";
include LIVEAPI_PATH."vendor/mustache/Mustache.php";

// Setup autoloader for live API
include LIVEAPI_PATH."classes/LiveAPI/Autoload.php";
$autoload = new LiveAPI_Autoload(LIVEAPI_PATH."classes");
$autoload->register();

$api = new LiveAPI($argv);

// And now create one for the imported classes
$class_autoload = new LiveAPI_Autoload($api->getConfig('dir'), $api->getConfig('namespace'));
$class_autoload->register();

// And away we go...
$api->run();